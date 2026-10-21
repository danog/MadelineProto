<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Parallel;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\CompositeCancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Context\ThreadContext;
use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Socket\Socket;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use Revolt\EventLoop;
use Throwable;

use function Amp\Parallel\Context\contextFactory;
use function Amp\Parallel\Worker\workerPool;

/**
 * Starts amphp/parallel contexts on webhosts, where processes can't be started: each context is a request to the
 * webserver, like the self-restarts of MadelineProto (see WebRunner::selfStart()).
 *
 * The context talks to the parent with danog/ipc (UNIX sockets, FIFOs or TCP, whatever works).
 *
 * Every context occupies a worker of the webserver (a PHP-FPM child) for as long as it runs, and is subject to the
 * time limits of the webserver: keep the worker pool small.
 */
final class WebContextFactory implements ContextFactory
{
    use ForbidCloning;
    use ForbidSerialization;

    /**
     * Default limit of the worker pool installed by {@see install()}.
     */
    public const DEFAULT_WORKER_LIMIT = 4;

    /**
     * @param positive-int $childConnectTimeout Seconds the request has to connect back.
     */
    public function __construct(
        private readonly int $childConnectTimeout = 10,
    ) {
    }

    /**
     * Use this factory for the global context factory and worker pool of amphp/parallel, if running in a webserver
     * where threads aren't supported.
     *
     * @internal
     */
    public static function install(): void
    {
        if (PHP_SAPI === 'cli'
            || PHP_SAPI === 'phpdbg'
            || !isset($_SERVER['SERVER_NAME'])
            || !$_SERVER['SERVER_NAME']
            || ThreadContext::isSupported()
            || PHP_OS_FAMILY === 'Windows'
        ) {
            return;
        }
        $factory = new self();
        contextFactory($factory);
        workerPool(new ContextWorkerPool(
            self::DEFAULT_WORKER_LIMIT,
            new ContextWorkerFactory(contextFactory: $factory),
        ));
    }

    /**
     * The directory of the request file and the IPC endpoint of a context, private to the user of the webserver.
     *
     * The temporary directory may be shared with other users (on shared hosting): they must not get to choose what
     * a context runs, nor to send to the IPC endpoint, which unserializes what it receives before the key is checked.
     * Every context gets its own directory, so that there's no predictable path for others to take first.
     *
     * The webserver request only gets the ID: whoever can request the entry script must not be able to choose what
     * it runs.
     *
     * @internal
     */
    public static function getDirectory(string $id): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR."madeline-parallel-$id";
    }

    /**
     * Path of the request file of a context.
     *
     * @internal
     */
    public static function getRequestPath(string $id): string
    {
        return self::getDirectory($id).DIRECTORY_SEPARATOR.'request.json';
    }

    /**
     * Whether only the user of the webserver can access a directory (or file).
     *
     * @internal
     */
    public static function isPrivate(string $path): bool
    {
        clearstatcache(true, $path);
        if (is_link($path) || !file_exists($path)) {
            return false;
        }
        $uid = \function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
        if (fileowner($path) !== $uid || (fileperms($path) & 0o077) !== 0) {
            Logger::log("$path is not private to user $uid", Logger::ERROR);
            return false;
        }
        return true;
    }

    /**
     * @param string|non-empty-list<string> $script Path to the script, or a list with the path and its arguments.
     */
    #[\Override]
    public function start(string|array $script, ?Cancellation $cancellation = null): WebContext
    {
        $script = \is_array($script) ? array_values($script) : [$script];
        if (!$script) {
            throw new \ValueError('Empty script array provided to web context');
        }
        if (PHP_OS_FAMILY === 'Windows') {
            // Owners and permissions can't be checked (IIS may use the temporary directory of the system)
            throw new ContextException('Web contexts are not supported on Windows');
        }

        $id = bin2hex(random_bytes(16));
        $key = bin2hex(random_bytes(16));
        $dir = self::getDirectory($id);
        $request = self::getRequestPath($id);

        $umask = umask(0o077);
        try {
            if (!mkdir($dir, 0o700) || !self::isPrivate($dir)) {
                throw new ContextException("Could not create the private directory $dir");
            }
            // Not TCP: anyone could connect, and the key is checked after unserializing
            $server = new IpcServer($dir.DIRECTORY_SEPARATOR.'ipc', IpcServer::TYPE_UNIX | IpcServer::TYPE_FIFO);
        } catch (Throwable $e) {
            self::remove($dir);
            throw $e instanceof ContextException
                ? $e
                : new ContextException('Could not create the IPC server of the context', previous: $e);
        } finally {
            umask($umask);
        }
        try {
            self::writeRequest($request, [
                'uri' => $server->getUri(),
                'key' => $key,
                'script' => $script,
                'cwd' => Magic::getcwd(),
                'timeout' => $this->childConnectTimeout,
            ]);
            $requests = WebRunner::selfStartAsync(WebRunner::getRunPath().'?'.http_build_query([
                'argv' => ['madeline-parallel', $id],
            ]), $this->childConnectTimeout);
            $socket = $this->accept($server, $key, $cancellation);
        } catch (CancelledException|ContextException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContextException('Starting the context failed: '.$e->getMessage(), previous: $e);
        } finally {
            $server->close();
            // The request didn't get to read it
            self::remove($request);
            self::remove($dir);
            // The request already ignores the abort (or it never came)
            foreach ($requests ?? [] as $connection) {
                $connection->map(static fn (?Socket $socket) => $socket?->close())->ignore();
            }
        }

        Logger::log("Started web context $id");
        return new WebContext($socket);
    }

    /**
     * Remove a file or an empty directory, if it exists.
     */
    private static function remove(string $path): void
    {
        clearstatcache(true, $path);
        try {
            if (is_dir($path) && !is_link($path)) {
                rmdir($path);
            } elseif (file_exists($path) || is_link($path)) {
                unlink($path);
            }
        } catch (Throwable $e) {
            Logger::log("Could not remove $path: {$e->getMessage()}", Logger::ERROR);
        }
    }

    /**
     * Write the request file, readable only by the user of the webserver.
     */
    private static function writeRequest(string $path, array $request): void
    {
        $umask = umask(0o077);
        try {
            if (file_put_contents($path, json_encode($request, JSON_THROW_ON_ERROR)) === false) {
                throw new ContextException("Could not write $path");
            }
        } finally {
            umask($umask);
        }
    }

    /**
     * Accept the connection of the context, which authenticates with the key.
     */
    private function accept(IpcServer $server, string $key, ?Cancellation $cancellation): ChannelledSocket
    {
        $timeout = new TimeoutCancellation(
            $this->childConnectTimeout,
            "The context didn't connect within {$this->childConnectTimeout} seconds: check that requests to the entry script of MadelineProto succeed",
        );
        $deferred = new DeferredFuture();
        /** @var array<int, ChannelledSocket> */
        $pending = [];

        EventLoop::queue(static function () use ($server, $key, $deferred, &$pending): void {
            while ($socket = $server->accept()) {
                // Read the key of each connection on its own: one that sends nothing must not hold up the others
                $pending[$id = spl_object_id($socket)] = $socket;
                EventLoop::queue(static function () use ($socket, $id, $key, $deferred, &$pending): void {
                    try {
                        $received = $socket->receive();
                    } catch (Throwable) {
                        $received = null;
                    }
                    unset($pending[$id]);
                    if (\is_string($received) && hash_equals($key, $received) && !$deferred->isComplete()) {
                        $deferred->complete($socket);
                    } else {
                        $socket->disconnect();
                    }
                });
            }
        });

        try {
            return $deferred->getFuture()->await(
                $cancellation ? new CompositeCancellation($cancellation, $timeout) : $timeout
            );
        } catch (CancelledException $e) {
            $cancellation?->throwIfRequested();
            throw new ContextException('Starting the context failed', previous: $e);
        } finally {
            // Ends the accept loop
            $server->close();
            foreach ($pending as $socket) {
                $socket->disconnect();
            }
        }
    }
}
