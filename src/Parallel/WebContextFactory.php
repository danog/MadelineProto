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
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Parallel\Internal\FallbackContextFactory;
use danog\MadelineProto\Parallel\Internal\OnDemandWorkerFactory;
use ReflectionException;
use ReflectionFunction;
use Revolt\EventLoop;
use Throwable;
use WeakMap;

use function Amp\Parallel\Context\contextFactory;
use function Amp\Parallel\Worker\workerPool;

/**
 * Starts amphp/parallel contexts on webhosts, where processes can't be started: each context is a request to the
 * webserver, like the self-restarts of MadelineProto (see WebRunner::selfStart()).
 *
 * The context talks to the parent with danog/ipc (UNIX sockets or FIFOs, in a directory private to this user).
 *
 * Every context occupies a worker of the webserver (a PHP-FPM child) for as long as it runs, and is subject to the
 * time limits of the webserver: keep the worker pool small.
 */
final class WebContextFactory implements ContextFactory
{
    use ForbidCloning;
    use ForbidSerialization;

    /**
     * Limit of the worker pool installed by {@see install()}.
     */
    public const DEFAULT_WORKER_LIMIT = 4;

    /**
     * Seconds after which an idle worker of the pool installed by {@see install()} is stopped.
     */
    public const WORKER_IDLE_TIMEOUT = 10;

    /**
     * @param positive-int $childConnectTimeout Seconds the request has to connect back.
     */
    public function __construct(
        private readonly int $childConnectTimeout = 10,
    ) {
    }

    /**
     * Under a webserver, where threads aren't supported, start the contexts of amphp/parallel with requests to the
     * webserver where processes can't be started ({@see FallbackContextFactory}), unless the global context factory
     * of amphp/parallel was already set.
     *
     * Unless the global worker pool was already set, it's replaced with one of at most DEFAULT_WORKER_LIMIT workers,
     * each stopped once idle for WORKER_IDLE_TIMEOUT seconds and started again when needed (amphp's default pool has
     * up to 32 workers, never stopped): a web context occupies a worker of the webserver (a PHP-FPM child, of which
     * there may be as few as 5) for as long as it runs. Starting a worker costs a request to the webserver (or a
     * process), and running ones are subject to the time limits of the webserver.
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
        if (!self::isGlobalSet('Amp\\Parallel\\Context\\contextFactory')) {
            contextFactory(new FallbackContextFactory());
        }
        if (!self::isGlobalSet('Amp\\Parallel\\Worker\\workerPool')) {
            workerPool(new ContextWorkerPool(
                self::DEFAULT_WORKER_LIMIT,
                new OnDemandWorkerFactory(self::WORKER_IDLE_TIMEOUT),
            ));
        }
    }

    /**
     * Whether a global of amphp/parallel (contextFactory(), workerPool()) was already set for the current event
     * loop, by the user or by using it.
     *
     * Its getter creates the default one when called, and keeps them in a static map: read it without calling it.
     * If the map can't be read (another version of amphp/parallel), it's considered set, never to replace it.
     *
     * @param callable-string $function
     */
    private static function isGlobalSet(string $function): bool
    {
        try {
            $map = (new ReflectionFunction($function))->getStaticVariables()['map'] ?? null;
        } catch (ReflectionException) {
            return true;
        }
        if ($map === null) {
            return false;
        }
        return !$map instanceof WeakMap || isset($map[EventLoop::getDriver()]);
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
        $uid = self::getUid();
        if (fileowner($path) !== $uid || (fileperms($path) & 0o077) !== 0) {
            Logger::log("$path is not private to user $uid", Logger::ERROR);
            return false;
        }
        return true;
    }

    private static ?int $uid = null;

    /**
     * The user ID of the files this process creates: its effective user ID.
     *
     * Without ext-posix, it's the owner of a temporary file this process creates: getmyuid() is the owner of the
     * script, not the user running it (www-data with mod_php, or with a PHP-FPM pool running the scripts of
     * several users).
     *
     * @internal
     */
    public static function getUid(): int
    {
        if (self::$uid !== null) {
            return self::$uid;
        }
        if (\function_exists('posix_geteuid')) {
            return self::$uid = posix_geteuid();
        }
        try {
            $file = tmpfile();
        } catch (Throwable $e) {
            throw new ContextException('Could not create a temporary file to get the user ID of this process', previous: $e);
        }
        if ($file === false) {
            throw new ContextException('Could not create a temporary file to get the user ID of this process');
        }
        try {
            return self::$uid = fstat($file)['uid'];
        } finally {
            fclose($file);
        }
    }

    /**
     * Create a directory only the user of the webserver can access.
     *
     * mkdir() fails if anything exists at the path, a symlink included: what it creates belongs to this user.
     */
    private static function createPrivateDirectory(string $path): void
    {
        $umask = umask(0o077);
        try {
            mkdir($path, 0o700);
        } catch (Throwable $e) {
            throw new ContextException("Could not create the private directory $path", previous: $e);
        } finally {
            umask($umask);
        }
        if (!self::isPrivate($path)) {
            self::remove($path);
            throw new ContextException("Could not create the private directory $path");
        }
    }

    /**
     * The longest path of a UNIX socket: sun_path is 108 bytes long on Linux, 104 on macOS and the BSDs, NUL
     * included.
     */
    private const MAX_SOCKET_PATH = PHP_OS_FAMILY === 'Linux' ? 107 : 103;

    /**
     * Listen on the IPC endpoint of a context, in its private directory.
     *
     * Not on TCP: anyone could connect, and the key is checked after unserializing.
     *
     * The path of a UNIX socket can't be longer than MAX_SOCKET_PATH, which the directory of a context may exceed (in
     * the temporary directory of macOS, the endpoint is 104 characters long): the socket is then in a private
     * directory of its own in /tmp. FIFOs (used on Linux only) have no such limit: they're used if /tmp can't be.
     *
     * @return array{IpcServer, ?string} The server, and the directory created for its socket.
     */
    private static function listen(string $dir): array
    {
        $path = $dir.DIRECTORY_SEPARATOR.'ipc';
        $socketDir = null;
        if (\strlen($path) > self::MAX_SOCKET_PATH) {
            try {
                self::createPrivateDirectory($socketDir = '/tmp/madeline-'.bin2hex(random_bytes(8)));
                $path = "$socketDir/ipc";
            } catch (ContextException $e) {
                if (PHP_OS_FAMILY !== 'Linux') {
                    throw new ContextException(
                        "The temporary directory is too long for the UNIX socket of the context, and /tmp can't be used: set a shorter sys_temp_dir",
                        previous: $e,
                    );
                }
                $socketDir = null;
            }
        }
        $umask = umask(0o077);
        try {
            $server = new IpcServer(
                $path,
                \strlen($path) > self::MAX_SOCKET_PATH ? IpcServer::TYPE_FIFO : IpcServer::TYPE_UNIX | IpcServer::TYPE_FIFO,
            );
        } catch (Throwable $e) {
            if ($socketDir !== null) {
                self::remove($socketDir);
            }
            throw new ContextException('Could not create the IPC server of the context', previous: $e);
        } finally {
            umask($umask);
        }
        return [$server, $socketDir];
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

        self::createPrivateDirectory($dir);
        try {
            [$server, $socketDir] = self::listen($dir);
        } catch (Throwable $e) {
            self::remove($dir);
            throw $e;
        }
        try {
            self::writeRequest($request, [
                'uri' => $server->getUri(),
                'key' => $key,
                'script' => $script,
                'cwd' => Magic::getcwd(),
                'timeout' => $this->childConnectTimeout,
            ]);
            $socket = $this->connect($server, $id, $key, $cancellation);
        } catch (CancelledException|ContextException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContextException('Starting the context failed: '.$e->getMessage(), previous: $e);
        } finally {
            $server->close();
            // The request didn't get to read it
            self::remove($request);
            self::remove($dir);
            if ($socketDir !== null) {
                self::remove($socketDir);
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
     * Seconds to connect to an endpoint of the webserver, and send it the request of a context.
     */
    private const ENDPOINT_TIMEOUT = 2;

    /**
     * Seconds to wait for the context to connect before sending its request to the next endpoint too.
     */
    private const ENDPOINT_GRACE = 1;

    /**
     * The endpoint (index in WebRunner::getSelfStartEndpoints()) whose request started the last context: the
     * request of the next one goes there first.
     */
    private static ?int $endpoint = null;

    /**
     * Send the request of the context to the webserver, and accept the connection of the context, which
     * authenticates with the key.
     *
     * The request goes to one endpoint of the webserver at a time (see WebRunner::getSelfStartEndpoints()), until
     * the context connects: every request reaching the webserver occupies one of its workers, if only for a moment
     * (the request file is only served once). An endpoint accepting the connection doesn't mean that it reaches the
     * webserver (a CDN, a port mapped elsewhere), so the next one is tried if the context doesn't connect within
     * ENDPOINT_GRACE seconds.
     *
     * The connections of the requests are closed once their response ends (see WebRunner::sendSelfRequest()): for
     * the one that started the context, once its request ends.
     */
    private function connect(IpcServer $server, string $id, string $key, ?Cancellation $cancellation): ChannelledSocket
    {
        $timeout = new TimeoutCancellation(
            $this->childConnectTimeout,
            "The context didn't connect within {$this->childConnectTimeout} seconds: check that requests to the entry script of MadelineProto succeed",
        );
        $startCancellation = $cancellation ? new CompositeCancellation($cancellation, $timeout) : $timeout;
        $endpoints = WebRunner::getSelfStartEndpoints();

        /** @var DeferredFuture<array{ChannelledSocket, ?int}> */
        $deferred = new DeferredFuture();
        /** @var array<int, ChannelledSocket> */
        $pending = [];
        EventLoop::queue(static function () use ($server, $key, $endpoints, $deferred, &$pending): void {
            while ($socket = $server->accept()) {
                // Read the key of each connection on its own: one that sends nothing must not hold up the others
                $pending[$socketId = spl_object_id($socket)] = $socket;
                EventLoop::queue(static function () use ($socket, $socketId, $key, $endpoints, $deferred, &$pending): void {
                    try {
                        $received = $socket->receive();
                    } catch (Throwable) {
                        $received = null;
                    }
                    unset($pending[$socketId]);
                    if (\is_array($received)
                        && \is_string($received['key'] ?? null)
                        && hash_equals($key, $received['key'])
                        && !$deferred->isComplete()
                    ) {
                        $endpoint = $received['endpoint'] ?? null;
                        $deferred->complete([$socket, \is_int($endpoint) && isset($endpoints[$endpoint]) ? $endpoint : null]);
                    } else {
                        $socket->disconnect();
                    }
                });
            }
        });
        $connected = $deferred->getFuture();

        try {
            $order = array_keys($endpoints);
            if (self::$endpoint !== null && isset($endpoints[self::$endpoint])) {
                $order = [self::$endpoint, ...array_diff($order, [self::$endpoint])];
            }
            $runPath = WebRunner::getRunPath();
            $sent = false;
            foreach ($order as $index) {
                [$proto, $port] = $endpoints[$index];
                $uri = $runPath.'?'.http_build_query(['argv' => ['madeline-parallel', $id, $index]]);
                if (!WebRunner::sendSelfRequest($proto, $port, $uri, timeout: self::ENDPOINT_TIMEOUT)) {
                    continue;
                }
                $sent = true;
                try {
                    $connected->await(new CompositeCancellation(
                        $startCancellation,
                        new TimeoutCancellation(self::ENDPOINT_GRACE),
                    ));
                    break;
                } catch (CancelledException $e) {
                    if ($startCancellation->isRequested()) {
                        throw $e;
                    }
                    // Not served (yet): try the next endpoint too
                }
            }
            if (!$sent) {
                throw new ContextException('Could not send the request of the context to the webserver: check the logs and the server configuration');
            }
            [$socket, $endpoint] = $connected->await($startCancellation);
            self::$endpoint = $endpoint;
            return $socket;
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
