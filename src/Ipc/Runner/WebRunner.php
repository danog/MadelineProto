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
use danog\MadelineProto\Tools;
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
     * Header for the web-runner startup token file, preventing direct HTTP execution or disclosure.
     */
    public const TOKEN_HEADER = '<?php __HALT_COMPILER();';
    /**
     * Filename of the one-time startup token inside the session directory.
     */
    public const TOKEN_FILE = 'ipcStart.php';
    /**
     * Maximum age of a web-runner startup token, in seconds.
     */
    public const TOKEN_TTL = 60;

    /**
     * Start.
     *
     * @param string $session Session path
     */
    #[\Override]
    public static function start(string $session, int $startupId): bool
    {
        if (!isset($_SERVER['SERVER_NAME']) || $_SERVER['SERVER_NAME'] === '') {
            return false;
        }
        Tools::validateServerName($_SERVER['SERVER_NAME']);

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
            'token' => self::createStartupToken($session, $startupId),
            'MadelineSelfRestart' => 1,
        ];
        if (\function_exists('memprof_enabled') && memprof_enabled()) {
            $params['MEMPROF_PROFILE'] = '1';
        }

        self::selfStart(self::$runPath.'?'.http_build_query($params));

        return true;
    }

    /**
     * Startup tokens by session and startup ID: the runners of a startup share one, so a runner doesn't replace the
     * token of the worker of the other.
     *
     * @var array<string, string>
     */
    private static array $startupTokens = [];
    /**
     * Get the one-time token that lets entry.php start a worker that didn't get its arguments from the command line
     * of the CLI binary: one started over HTTP, a CGI binary started by the process runner, a self-restart.
     *
     * It's stored in the session directory with the working directory of the worker, which a request can't choose.
     *
     * @internal
     */
    public static function createStartupToken(string $session, int $startupId): string
    {
        $key = "$session\0$startupId";
        if (isset(self::$startupTokens[$key])) {
            return self::$startupTokens[$key];
        }
        $token = bin2hex(random_bytes(32));
        $payload = self::TOKEN_HEADER.json_encode([
            'token' => $token,
            // Not necessarily UTF-8
            'cwd' => base64_encode(Magic::getcwd()),
            'startupId' => $startupId,
            'time' => time(),
        ], JSON_THROW_ON_ERROR);
        // Renamed into place: entry.php never reads a partially written file
        $temp = $session.DIRECTORY_SEPARATOR.'ipcStart.'.bin2hex(random_bytes(8)).'.php';
        file_put_contents($temp, $payload);
        rename($temp, $session.DIRECTORY_SEPARATOR.self::TOKEN_FILE);
        return self::$startupTokens[$key] = $token;
    }

    private static ?string $absoluteRootDir = null;
    public static function getAbsoluteRootDir(): string
    {
        if (self::$absoluteRootDir) {
            return self::$absoluteRootDir;
        }

        // Only the path of the request: the host is a placeholder
        $uri = parse_url('tcp://localhost'.($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
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
        $serverName = Tools::validateServerName($_SERVER['SERVER_NAME'] ?? '');
        if (preg_match('/[\r\n\0]/', $uri)) {
            throw new AssertionError('Invalid self-start URI!');
        }
        // Whether SERVER_NAME already has a port (not an IPv6 address)
        $hasPort = preg_match('/^(\[[^\]]+\]|[^:]+):\d+$/', $serverName) === 1;

        foreach (($_SERVER['HTTPS'] ?? 'off') === 'on' ? ['tls', 'tcp'] : ['tcp', 'tls'] as $proto) {
            foreach (array_unique([(int) $_SERVER['SERVER_PORT'], ($proto === 'tls' ? 443 : 80)]) as $port) {
                // With the port, unless it's the default one: Caddy (and Apache, by default) take SERVER_PORT from the
                // Host header, and the worker connects to itself there to restart.
                $host = $hasPort || $port === ($proto === 'tls' ? 443 : 80) ? $serverName : "$serverName:$port";
                // Without Connection: close, the server keeps the connection open after the response, until its idle timeout
                $payload = "GET $uri HTTP/1.1\r\nHost: $host\r\nConnection: close\r\n\r\n";
                try {
                    $address = $proto.'://'.$serverName;
                    $res = fsockopen($address, (int) $port);
                    Logger::log("Successfully connected to {$address}:{$port}!");
                    Logger::log("Sending payload: $payload");
                    // We don't care for results or timeouts here, PHP doesn't count IOwait time as execution time anyway
                    // Technically should use amphp/socket, but I guess it's OK to not introduce another dependency just for a socket that will be used once.
                    fwrite($res, $payload);
                    // Closing the connection could abort the request of the worker: it's closed once the response
                    // ends (the worker exited, or another server answered).
                    EventLoop::queue(self::drain(...), new ReadableResourceStream($res));
                } catch (Throwable $e) {
                    Logger::log("Error while sending to {$address}:{$port}: $e");
                }
            }
        }
        if (!isset($res)) {
            throw new Exception('Could not connect to ourselves, please check the server configuration!');
        }
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
