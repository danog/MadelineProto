<?php

namespace danog\MadelineProto\Ipc\Runner;

use Amp\Process\Process as BaseProcess;
use Amp\Promise;
use danog\MadelineProto\Magic;

final class ProcessRunner extends RunnerAbstract
{
    /** @var string|null Cached path to located PHP binary. */
    private static $binaryPath;

    /** @var \Amp\Process\Process */
    private $process;
    /**
     * Constructor.
     *
     * @param string $session Session path
     */
    public function __construct(string $session)
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

        $command = \implode(" ", [
            'nohup',
            \escapeshellarg($binary),
            self::formatOptions($options),
            $runner,
            'madeline-ipc',
            \escapeshellarg($session),
            '&'
        ]);
        var_dump($command);

        $this->process = new BaseProcess($command, Magic::getcwd());
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

    /**
     * Starts the process.
     *
     * @return Promise<int> Resolved with the PID
     */
    public function start(): Promise
    {
        return $this->process->start();
    }
}
