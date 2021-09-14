<?php

namespace danog\MadelineProto\Ipc\Runner;

use Amp\Failure;
use Amp\Parallel\Context\ContextException;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;

final class WebRunner extends RunnerAbstract
{
    /** @var string|null Cached path to the runner script. */
    private static $runPath;

    /**
     * Resources.
     */
    private static array $resources = [];

    /**
     * Start.
     *
     * @param string $session Session path
     *
     * @return Promise<bool>
     */
    public static function start(string $session, int $startupId): Promise
    {
        if (!isset($_SERVER['SERVER_NAME']) || !$_SERVER['SERVER_NAME']) {
            return new Failure(new \Exception("Can't start the web runner!"));
        }

        if (!self::$runPath) {
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
            $absoluteRootDir = \substr($rootDir, 0, \strlen($rootDir)-\strlen($uriDir)).DIRECTORY_SEPARATOR;
            $runPath = self::getScriptPath($absoluteRootDir);

            if (\substr($runPath, 0, \strlen($absoluteRootDir)) === $absoluteRootDir) { // Process runner is within readable document root
                self::$runPath = \substr($runPath, \strlen($absoluteRootDir)-1);
            } else {
                $contents = \file_get_contents(self::SCRIPT_PATH);
                $contents = \str_replace("__DIR__", \var_export($absoluteRootDir, true), $contents);
                $suffix = \bin2hex(\random_bytes(10));
                $runPath = $absoluteRootDir."/madeline-ipc-".$suffix.".php";
                \file_put_contents($runPath, $contents);

                self::$runPath = \substr($runPath, \strlen($absoluteRootDir)-1);

                \register_shutdown_function(static function () use ($runPath): void {
                    @\unlink($runPath);
                });
            }

            self::$runPath = \str_replace(DIRECTORY_SEPARATOR, '/', self::$runPath);
            self::$runPath = \str_replace('//', '/', self::$runPath);
        }

        $params = [
            'argv' => ['madeline-ipc', $session, $startupId],
            'cwd' => Magic::getcwd()
        ];

        $params = \http_build_query($params);

        foreach (($_SERVER['HTTPS'] ?? 'off') === 'on' ? ['tls', 'tcp'] : ['tcp', 'tls'] as $proto) {
            try {
                $address = $proto.'://'.$_SERVER['SERVER_NAME'];
                $port = $_SERVER['SERVER_PORT'];
                $res = \fsockopen($address, $port);
                break;
            } catch (\Throwable $e) {
                Logger::log("Error while connecting to ourselves: $e");
            }
        }
        if (!isset($res)) {
            throw new Exception("Could not connect to ourselves, please check the server configuration!");
        }

        $uri = self::$runPath.'?'.$params;

        $payload = "GET $uri HTTP/1.1\r\nHost: ${_SERVER['SERVER_NAME']}\r\n\r\n";

        // We don't care for results or timeouts here, PHP doesn't count IOwait time as execution time anyway
        // Technically should use amphp/socket, but I guess it's OK to not introduce another dependency just for a socket that will be used once.
        \fwrite($res, $payload);
        self::$resources []= $res;

        return new Success(true);
    }
}
