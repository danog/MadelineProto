<?php

namespace danog\MadelineProto\Ipc\Runner;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Tools;

final class ProcessRunner extends RunnerAbstract
{
    /** @var string|null Cached path to located PHP binary. */
    private static $binaryPath;

    /**
     * Resources.
     */
    private static array $resources = [];
    /**
     * Runner.
     *
     * @param string $session Session path
     *
     * @return void
     */
    public static function start(string $session, int $request): void
    {
        $request = Tools::randomInt();
        if (\PHP_SAPI === "cli") {
            $binary = \PHP_BINARY;
        } else {
            $binary = self::$binaryPath ?? self::locateBinary();
        }

        $options = [
            "html_errors" => "0",
            "display_errors" => "0",
            "log_errors" => "1",
        ];

        $runner = self::getScriptPath();

        $command = \implode(" ", [
            \escapeshellarg($binary),
            self::formatOptions($options),
            $runner,
            'madeline-ipc',
            \escapeshellarg($session),
            $request
        ]);
        Logger::log("Starting process with $command");

        self::$resources []= \proc_open($command, [], $foo);
    }
    private static function locateBinary(): string
    {
        $executable = \strncasecmp(\PHP_OS, "WIN", 3) === 0 ? "php.exe" : "php";

        $paths = \array_filter(\explode(\PATH_SEPARATOR, \getenv("PATH")));
        $paths[] = \PHP_BINDIR;
        $paths = \array_unique($paths);

        foreach ($paths as $path) {
            $path .= \DIRECTORY_SEPARATOR.$executable;
            if (\is_executable($path)) {
                return self::$binaryPath = $path;
            }
        }

        throw new \Error("Could not locate PHP executable binary");
    }

    private static function formatOptions(array $options): string
    {
        $result = [];

        foreach ($options as $option => $value) {
            $result[] = \sprintf("-d%s=%s", $option, $value);
        }

        return \implode(" ", $result);
    }
}
