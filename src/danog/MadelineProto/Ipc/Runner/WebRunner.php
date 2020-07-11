<?php

namespace danog\MadelineProto\Ipc\Runner;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Parallel\Context\ContextException;
use danog\MadelineProto\Magic;

final class WebRunner extends RunnerAbstract
{
    /** @var string|null Cached path to the runner script. */
    private static $runPath;
    /**
     * Initialization payload.
     *
     * @var array
     */
    private $params;

    /**
     * Socket.
     *
     * @var ResourceOutputStream
     */
    private $res;
    /**
     * Start.
     *
     * @param string $session Session path
     */
    public static function start(string $session): void
    {
        if (!isset($_SERVER['SERVER_NAME'])) {
            throw new ContextException("Could not initialize web runner!");
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
            $rootDir = \dirname($rootDir);
            $uriDir = \dirname($uri);

            if (\substr($rootDir, -\strlen($uriDir)) !== $uriDir) {
                throw new ContextException("Mismatch between absolute root dir ($rootDir) and URI dir ($uriDir)");
            }

            // Absolute root of (presumably) readable document root
            $localRootDir = \substr($rootDir, 0, \strlen($rootDir)-\strlen($uriDir)).DIRECTORY_SEPARATOR;

            $runPath = self::getScriptPath($localRootDir);

            if (\substr($runPath, 0, \strlen($localRootDir)) === $localRootDir) { // Process runner is within readable document root
                self::$runPath = \substr($runPath, \strlen($localRootDir)-1);
            } else {
                $contents = \file_get_contents(self::SCRIPT_PATH);
                $contents = \str_replace("__DIR__", \var_export($localRootDir, true), $contents);
                $suffix = \bin2hex(\random_bytes(10));
                $runPath = $localRootDir."/madeline-ipc-".$suffix.".php";
                \file_put_contents($runPath, $contents);

                self::$runPath = \substr($runPath, \strlen($localRootDir)-1);

                \register_shutdown_function(static function () use ($runPath): void {
                    @\unlink($runPath);
                });
            }

            self::$runPath = \str_replace(DIRECTORY_SEPARATOR, '/', self::$runPath);
            self::$runPath = \str_replace('//', '/', self::$runPath);
        }

        $this->params = [
            'argv' => ['pony', 'madeline-ipc', $session],
            'cwd' => Magic::getcwd()
        ];

        $params = \http_build_query($this->params);

        $address = ($_SERVER['HTTPS'] ?? false ? 'tls' : 'tcp').'://'.$_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];

        $uri = self::$runPath.'?'.$params;

        $payload = "GET $uri HTTP/1.1\r\nHost: ${_SERVER['SERVER_NAME']}\r\n\r\n";

        // We don't care for results or timeouts here, PHP doesn't count IOwait time as execution time anyway
        // Technically should use amphp/socket, but I guess it's OK to not introduce another dependency just for a socket that will be used once.
        \fwrite($res = \fsockopen($address, $port), $payload);
        \fclose($res);
    }
}
