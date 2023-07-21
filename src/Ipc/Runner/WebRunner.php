<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Runner;

use Amp\Parallel\Context\ContextException;
use AssertionError;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
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
     * Resources.
     */
    private static array $resources = [];

    /**
     * Start.
     *
     * @param string $session Session path
     */
    public static function start(string $session, int $startupId): bool
    {
        if (!isset($_SERVER['SERVER_NAME']) || !$_SERVER['SERVER_NAME']) {
            return false;
        }

        if (!self::$runPath) {
            $absoluteRootDir = self::getAbsoluteRootDir();
            $runPath = self::getScriptPath($absoluteRootDir);

            if (\substr($runPath, 0, \strlen($absoluteRootDir)) === $absoluteRootDir) { // Process runner is within readable document root
                self::$runPath = \substr($runPath, \strlen($absoluteRootDir)-1);
            } else {
                throw new AssertionError("$runPath is not within absolute document root $absoluteRootDir!");
            }

            self::$runPath = \str_replace(DIRECTORY_SEPARATOR, '/', self::$runPath);
            self::$runPath = \str_replace('//', '/', self::$runPath);
        }

        $params = [
            'argv' => ['madeline-ipc', $session, $startupId],
            'cwd' => Magic::getcwd(),
            'MadelineSelfRestart' => 1
        ];
        if (\function_exists('memprof_enabled') && \memprof_enabled()) {
            $params['MEMPROF_PROFILE'] = '1';
        }

        self::selfStart(self::$runPath.'?'.\http_build_query($params));

        return true;
    }

    private static ?string $absoluteRootDir = null;
    final public static function getAbsoluteRootDir(): string
    {
        if (self::$absoluteRootDir) {
            return self::$absoluteRootDir;
        }

        $uri = \parse_url('tcp://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (\substr($uri, -1) === '/') { // http://example.com/path/ (assumed index.php)
            $uri .= 'index'; // Add fake file name
        }
        $uri = \str_replace('//', '/', $uri);

        $rootDir = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $rootDir = \end($rootDir)['file'] ?? '';
        if (!$rootDir) {
            throw new ContextException('Could not get entry file!');
        }
        $rootDir = \dirname($rootDir).DIRECTORY_SEPARATOR;
        $uriDir = \str_replace('/', DIRECTORY_SEPARATOR, \dirname($uri));
        if ($uriDir !== '/' && $uriDir !== '\\') {
            $uriDir .= DIRECTORY_SEPARATOR;
        }

        if (\substr($rootDir, -\strlen($uriDir)) !== $uriDir) {
            throw new ContextException("Mismatch between absolute root dir ($rootDir) and URI dir ($uriDir)");
        }

        // Absolute root of (presumably) readable document root
        return self::$absoluteRootDir = \substr($rootDir, 0, \strlen($rootDir)-\strlen($uriDir)).DIRECTORY_SEPARATOR;
    }

    public static function selfStart(string $uri): void
    {
        $payload = "GET $uri HTTP/1.1\r\nHost: {$_SERVER['SERVER_NAME']}\r\n\r\n";

        foreach (($_SERVER['HTTPS'] ?? 'off') === 'on' ? ['tls', 'tcp'] : ['tcp', 'tls'] as $proto) {
            foreach (\array_unique([(int) $_SERVER['SERVER_PORT'], ($proto === 'tls' ? 443 : 80)]) as $port) {
                try {
                    $address = $proto.'://'.$_SERVER['SERVER_NAME'];
                    $res = \fsockopen($address, (int) $port);
                    Logger::log("Successfully connected to {$address}:{$port}!");
                    Logger::log("Sending payload: $payload");
                    // We don't care for results or timeouts here, PHP doesn't count IOwait time as execution time anyway
                    // Technically should use amphp/socket, but I guess it's OK to not introduce another dependency just for a socket that will be used once.
                    \fwrite($res, $payload);
                    self::$resources []= $res;
                } catch (Throwable $e) {
                    Logger::log("Error while sending to {$address}:{$port}: $e");
                }
            }
        }
        if (!isset($res)) {
            throw new Exception('Could not connect to ourselves, please check the server configuration!');
        }
    }
}
