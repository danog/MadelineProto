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

use Amp\ByteStream\ReadableResourceStream;
use Amp\Parallel\Context\ContextException;
use AssertionError;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use Revolt\EventLoop;
use Throwable;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DIRECTORY_SEPARATOR;
use const PHP_URL_PATH;

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

        $params = [
            'argv' => ['madeline-ipc', $session, $startupId],
            'cwd' => Magic::getcwd(),
            'MadelineSelfRestart' => 1,
        ];
        if (\function_exists('memprof_enabled') && memprof_enabled()) {
            $params['MEMPROF_PROFILE'] = '1';
        }

        self::selfStart(self::$runPath.'?'.http_build_query($params));

        return true;
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

    public static function selfStart(string $uri): void
    {
        // Without Connection: close, the server keeps the connection open after the response, until its idle timeout
        $payload = "GET $uri HTTP/1.1\r\nHost: {$_SERVER['SERVER_NAME']}\r\nConnection: close\r\n\r\n";

        // The request must be sent once: each one that reaches the web server starts a script.
        foreach (self::getSelfAddresses($_SERVER) as $address) {
            try {
                $context = stream_context_create(['ssl' => ['peer_name' => $_SERVER['SERVER_NAME']]]);
                $res = stream_socket_client($address, $errno, $errstr, self::CONNECT_TIMEOUT, STREAM_CLIENT_CONNECT, $context);
                if (!$res) {
                    throw new Exception("$errstr ($errno)");
                }
            } catch (Throwable $e) {
                Logger::log("Error while connecting to $address: $e");
                continue;
            }
            Logger::log("Successfully connected to $address!");
            Logger::log("Sending payload: $payload");
            // We don't care for results or timeouts here, PHP doesn't count IOwait time as execution time anyway
            // Technically should use amphp/socket, but I guess it's OK to not introduce another dependency just for a socket that will be used once.
            fwrite($res, $payload);
            // Closing the connection could abort the request of the worker: it's closed once the response
            // ends (the worker exited, or another server answered).
            EventLoop::queue(self::drain(...), new ReadableResourceStream($res));
            return;
        }
        throw new Exception('Could not connect to ourselves, please check the server configuration!');
    }

    /**
     * Timeout for connecting to ourselves, in seconds.
     */
    private const CONNECT_TIMEOUT = 5.0;

    /**
     * The addresses to send self-requests to, in order of preference.
     *
     * The local address of the web server, if known, comes first: the public name may resolve to a proxy (a CDN, a
     * load balancer) that the request would go through and back. TLS is only tried on the HTTPS port, and plain HTTP
     * only on the others.
     *
     * @internal
     *
     * @param  array<string, mixed> $server The $_SERVER of the request
     * @return list<string>
     */
    public static function getSelfAddresses(array $server): array
    {
        $https = !empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off';
        $port = (int) ($server['SERVER_PORT'] ?? 0) ?: ($https ? 443 : 80);
        $hosts = [];
        if (isset($server['SERVER_ADDR']) && \is_string($server['SERVER_ADDR']) && $server['SERVER_ADDR'] !== '') {
            $hosts[] = str_contains($server['SERVER_ADDR'], ':') ? "[{$server['SERVER_ADDR']}]" : $server['SERVER_ADDR'];
        }
        $hosts[] = (string) $server['SERVER_NAME'];
        $candidates = [];
        foreach ($hosts as $host) {
            $candidates[] = [$https, $host, $port];
        }
        // The usual ports of the public name, if the server port is another one (behind a proxy, for example)
        $candidates[] = [$https, $hosts[array_key_last($hosts)], $https ? 443 : 80];
        $candidates[] = [!$https, $hosts[array_key_last($hosts)], $https ? 80 : 443];
        $addresses = [];
        foreach ($candidates as [$tls, $host, $port]) {
            if ($port === ($tls ? 80 : 443)) {
                continue;
            }
            $addresses[] = ($tls ? 'tls' : 'tcp')."://$host:$port";
        }
        return array_values(array_unique($addresses));
    }

    /**
     * Read the response to a self-request until it ends without keeping the loop alive, then close it.
     */
    private static function drain(ReadableResourceStream $stream): void
    {
        $stream->unreference();
        try {
            while ($stream->read() !== null);
        } catch (Throwable $e) {
            Logger::log("An error occurred while reading the response to a self-request: $e");
        } finally {
            $stream->close();
        }
    }
}
