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

namespace danog\MadelineProto\Ipc\Runner;

use Amp\ByteStream\ResourceStream;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Parallel\Context\ContextException;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Amp\TimeoutCancellation;
use AssertionError;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use Revolt\EventLoop;
use Throwable;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DIRECTORY_SEPARATOR;
use const PHP_URL_PATH;

use function Amp\async;
use function Amp\Future\awaitAll;
use function Amp\Socket\connect;
use function Amp\Socket\connectTls;

/**
 * @internal
 */
final class WebRunner extends RunnerAbstract
{
    /** @var string|null Cached path to the runner script. */
    private static ?string $runPath = null;

    /**
     * Start.
     *
     * @param string $session Session path
     */
    #[\Override]
    public static function start(string $session, int $startupId): bool
    {
        if (!isset($_SERVER['SERVER_NAME']) || !$_SERVER['SERVER_NAME']) {
            return false;
        }

        $params = [
            'argv' => ['madeline-ipc', $session, $startupId],
            'cwd' => Magic::getcwd(),
            'MadelineSelfRestart' => 1,
        ];
        if (\function_exists('memprof_enabled') && memprof_enabled()) {
            $params['MEMPROF_PROFILE'] = '1';
        }

        self::selfStart(self::getRunPath().'?'.http_build_query($params));

        return true;
    }

    /**
     * Get the URI path of the entry script ({@see entry.php}), to start workers with {@see selfStart()}.
     */
    public static function getRunPath(): string
    {
        if (!self::$runPath) {
            $absoluteRootDir = self::getAbsoluteRootDir();
            $runPath = self::getScriptPath($absoluteRootDir);

            if (substr($runPath, 0, \strlen($absoluteRootDir)) === $absoluteRootDir) { // Process runner is within readable document root
                self::$runPath = substr($runPath, \strlen($absoluteRootDir)-1);
            } else {
                throw new AssertionError("$runPath is not within absolute document root $absoluteRootDir!");
            }

            self::$runPath = str_replace(DIRECTORY_SEPARATOR, '/', self::$runPath);
            self::$runPath = str_replace('//', '/', self::$runPath);
        }

        return self::$runPath;
    }

    private static ?string $absoluteRootDir = null;
    public static function getAbsoluteRootDir(): string
    {
        if (self::$absoluteRootDir) {
            return self::$absoluteRootDir;
        }

        $uri = parse_url('tcp://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (substr($uri, -1) === '/') { // http://example.com/path/ (assumed index.php)
            $uri .= 'index'; // Add fake file name
        }
        $uri = str_replace('//', '/', $uri);

        $rootDir = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $rootDir = end($rootDir)['file'] ?? '';
        if (!$rootDir) {
            throw new ContextException('Could not get entry file!');
        }
        $rootDir = \dirname($rootDir).DIRECTORY_SEPARATOR;
        $uriDir = str_replace('/', DIRECTORY_SEPARATOR, \dirname($uri));
        if ($uriDir !== '/' && $uriDir !== '\\') {
            $uriDir .= DIRECTORY_SEPARATOR;
        }

        if (substr($rootDir, -\strlen($uriDir)) !== $uriDir) {
            throw new ContextException("Mismatch between absolute root dir ($rootDir) and URI dir ($uriDir)");
        }

        // Absolute root of (presumably) readable document root
        return self::$absoluteRootDir = substr($rootDir, 0, \strlen($rootDir)-\strlen($uriDir)).DIRECTORY_SEPARATOR;
    }

    /**
     * The endpoints where the webserver may be listening: the scheme and port of the current request first.
     *
     * All of them are tried on purpose, plain HTTP on the HTTPS port and TLS on the plain one included: on some hosts
     * only one of them reaches the webserver.
     *
     * @return list<array{'tcp'|'tls', int}>
     */
    public static function getSelfStartEndpoints(): array
    {
        $endpoints = [];
        foreach (($_SERVER['HTTPS'] ?? 'off') === 'on' ? ['tls', 'tcp'] : ['tcp', 'tls'] as $proto) {
            foreach (array_unique([(int) $_SERVER['SERVER_PORT'], ($proto === 'tls' ? 443 : 80)]) as $port) {
                $endpoints []= [$proto, $port];
            }
        }
        return $endpoints;
    }

    /**
     * Send a request for $uri to every endpoint of the webserver (see {@see getSelfStartEndpoints()}), to run a
     * script in a request of its own.
     *
     * Returns once the request was sent to one endpoint, while it's sent to the others in the background.
     *
     * @param bool $blocking Whether to send the requests without the event loop, one after the other: for shutdown
     *                       functions, where it can't run after a fatal error (the time limit) or an exit() in a fiber.
     *
     * @throws Exception If no endpoint could be sent the request.
     */
    public static function selfStart(string $uri, bool $blocking = false): void
    {
        if ($blocking) {
            $sent = false;
            foreach (self::getSelfStartEndpoints() as [$proto, $port]) {
                $sent = self::sendSelfRequest($proto, $port, $uri, blocking: true) || $sent;
            }
        } else {
            $deferred = new DeferredFuture();
            $requests = array_map(
                static fn (array $endpoint): Future => async(self::sendSelfRequest(...), $endpoint[0], $endpoint[1], $uri)
                    ->map(static function (bool $sent) use ($deferred): void {
                        if ($sent && !$deferred->isComplete()) {
                            $deferred->complete(true);
                        }
                    }),
                self::getSelfStartEndpoints(),
            );
            async(awaitAll(...), $requests)->map(static function () use ($deferred): void {
                if (!$deferred->isComplete()) {
                    $deferred->complete(false);
                }
            })->ignore();
            $sent = $deferred->getFuture()->await();
        }
        if (!$sent) {
            throw new Exception('Could not connect to ourselves, please check the server configuration!');
        }
    }

    /** @var list<resource> Connections of the requests sent without the event loop, kept until exit. */
    private static array $blockingRequests = [];

    /**
     * Send a request for $uri to an endpoint of the webserver.
     *
     * Without $blocking, the event loop keeps running meanwhile: the webserver may only answer (a TLS handshake with
     * its plain HTTP port, for example) once one of its workers is free, and all of them may be busy with requests
     * waiting for this process.
     *
     * The connection is closed once the response ends (the script exited, or ended the request), not before: closing
     * it could abort the request.
     *
     * @param 'tcp'|'tls' $proto
     * @param float       $timeout Seconds to connect and send the request.
     *
     * @return bool Whether the request was sent.
     */
    public static function sendSelfRequest(string $proto, int $port, string $uri, bool $blocking = false, float $timeout = 5): bool
    {
        $host = $_SERVER['SERVER_NAME'];
        $address = "$proto://$host:$port";
        // Without Connection: close, the server keeps the connection open after the response, until its idle timeout
        $payload = "GET $uri HTTP/1.1\r\nHost: $host\r\nConnection: close\r\n\r\n";
        if ($blocking) {
            try {
                $socket = stream_socket_client(
                    $address,
                    $errno,
                    $errstr,
                    $timeout,
                    STREAM_CLIENT_CONNECT,
                    stream_context_create(['ssl' => ['peer_name' => $host]]),
                );
                if ($socket === false) {
                    throw new Exception("($errno) $errstr");
                }
                stream_set_timeout($socket, (int) ceil($timeout));
                fwrite($socket, $payload);
            } catch (Throwable $e) {
                Logger::log("Could not send the request to $address: {$e->getMessage()}");
                return false;
            }
            // Closed on exit: this is only used in shutdown functions
            self::$blockingRequests []= $socket;
            Logger::log("Sent the request to $address: $payload");
            return true;
        }
        $context = (new ConnectContext)->withConnectTimeout($timeout);
        $cancellation = new TimeoutCancellation($timeout);
        try {
            $socket = $proto === 'tls'
                ? connectTls("tcp://$host:$port", $context->withTlsContext(new ClientTlsContext($host)), $cancellation)
                : connect("tcp://$host:$port", $context, $cancellation);
        } catch (Throwable $e) {
            Logger::log("Could not connect to $address: {$e->getMessage()}");
            return false;
        }
        try {
            $socket->write($payload);
        } catch (Throwable $e) {
            Logger::log("Could not send the request to $address: {$e->getMessage()}");
            $socket->close();
            return false;
        }
        Logger::log("Sent the request to $address: $payload");
        EventLoop::queue(self::drain(...), $socket);
        return true;
    }

    /**
     * Read the response to a self-request until it ends without keeping the loop alive, then close it.
     */
    private static function drain(Socket $socket): void
    {
        if ($socket instanceof ResourceStream) {
            $socket->unreference();
        }
        try {
            while ($socket->read() !== null);
        } catch (Throwable $e) {
            Logger::log("An error occurred while reading the response to a self-request: {$e->getMessage()}");
        } finally {
            $socket->close();
        }
    }
}
