<?php

namespace danog\MadelineProto\Ipc\Runner;

use danog\MadelineProto\Logger;

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
    public static function start(string $session, int $startupId): void
    {
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

        if (\strtolower(\substr(PHP_OS, 0, 3)) === 'win') {
            $binary = \str_replace("Program Files", "PROGRA~1", $binary);
        // Pray there are no spaces in the name, escapeshellarg would help but windows doesn't want quotes in the program name
        } else {
            $binary = \escapeshellarg($binary);
        }
        $command = \implode(" ", [
            $binary,
            self::formatOptions($options),
            $runner,
            'madeline-ipc',
            \escapeshellarg($session),
            $startupId
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
