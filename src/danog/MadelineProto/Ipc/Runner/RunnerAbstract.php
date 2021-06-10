<?php

namespace danog\MadelineProto\Ipc\Runner;

use Amp\Promise;

abstract class RunnerAbstract
{
    const SCRIPT_PATH = __DIR__."/entry.php";

    /** @var string|null External version of SCRIPT_PATH if inside a PHAR. */
    protected static $pharScriptPath;

    /** @var string|null PHAR path with a '.phar' extension. */
    protected static $pharCopy;

    protected static function getScriptPath(string $alternateTmpDir = '')
    {
        /**
         * If using madeline.php, simply return madeline.php path.
         */
        if (\defined('MADELINE_PHP')) {
            return \MADELINE_PHP;
        }
        // Write process runner to external file if inside a PHAR different from madeline.phar,
        // because PHP can't open files inside a PHAR directly except for the stub.
        if (\strpos(self::SCRIPT_PATH, "phar://") === 0) {
            $alternateTmpDir = $alternateTmpDir ?: \sys_get_temp_dir();

            if (self::$pharScriptPath) {
                $scriptPath = self::$pharScriptPath;
            } else {
                $path = \dirname(self::SCRIPT_PATH);

                if (\substr(\Phar::running(false), -5) !== ".phar") {
                    self::$pharCopy = $alternateTmpDir."/phar-".\bin2hex(\random_bytes(10)).".phar";
                    \copy(\Phar::running(false), self::$pharCopy);

                    \register_shutdown_function(static function (): void {
                        @\unlink(self::$pharCopy);
                    });

                    $path = "phar://".self::$pharCopy."/".\substr($path, \strlen(\Phar::running(true)));
                }

                $contents = \file_get_contents(self::SCRIPT_PATH);
                $contents = \str_replace("__DIR__", \var_export($path, true), $contents);
                $suffix = \bin2hex(\random_bytes(10));
                self::$pharScriptPath = $scriptPath = $alternateTmpDir."/madeline-ipc-".$suffix.".php";
                \file_put_contents($scriptPath, $contents);

                \register_shutdown_function(static function (): void {
                    @\unlink(self::$pharScriptPath);
                });
            }
        } else {
            $scriptPath = self::SCRIPT_PATH;
        }
        return $scriptPath;
    }
    /**
     * Runner.
     *
     * @param string   $session Session path
     * @param int      $startup ID
     *
     * @return Promise<true>
     */
    abstract public static function start(string $session, int $startupId): Promise;
}
