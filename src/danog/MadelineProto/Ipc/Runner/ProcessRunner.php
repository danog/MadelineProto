<?php

namespace danog\MadelineProto\Ipc\Runner;

use Amp\Deferred;
use Amp\Process\Internal\Posix\Runner;
use Amp\Process\Internal\Windows\Runner as WindowsRunner;
use Amp\Process\ProcessInputStream;
use Amp\Promise;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Tools;

use const Amp\Process\IS_WINDOWS;

final class ProcessRunner extends RunnerAbstract
{
    private const CGI_VARS = [
        "AUTH_TYPE",
        "CONTENT_LENGTH",
        "CONTENT_TYPE",
        "GATEWAY_INTERFACE",
        "PATH_INFO",
        "PATH_TRANSLATED",
        "QUERY_STRING",
        "REMOTE_ADDR",
        "REMOTE_HOST",
        "REMOTE_IDENT",
        "REMOTE_USER",
        "REQUEST_METHOD",
        "SCRIPT_NAME",
        "SERVER_NAME",
        "SERVER_PORT",
        "SERVER_PROTOCOL",
        "SERVER_SOFTWARE"
    ];

    /** @var string|null Cached path to located PHP binary. */
    private static $binaryPath;

    /**
     * Runner.
     *
     * @param string $session Session path
     *
     * @return Promise<true>
     */
    public static function start(string $session, int $startupId): Promise
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

        $command = [
            $binary,
            ...self::formatOptions($options),
            $runner,
            'madeline-ipc',
            $session,
            $startupId
        ];
        $command = \implode(" ", \array_map('Amp\\Process\\escapeArguments', $command));
        Logger::log("Starting process with $command");

        $params = [
            'argv' => ['madeline-ipc', $session, $startupId],
            'cwd' => Magic::getcwd()
        ];
        $envVars = \array_merge(
            \array_filter($_SERVER, fn ($v, $k): bool => \is_string($v) && !\in_array($k, self::CGI_VARS), ARRAY_FILTER_USE_BOTH),
            ['QUERY_STRING' => \http_build_query($params)]
        );

        $resDeferred = new Deferred;

        $runner = IS_WINDOWS ? new WindowsRunner : new Runner;
        $handle = $runner->start($command, null, $envVars);
        $handle->pidDeferred->promise()->onResolve(function (?\Throwable $e, ?int $pid) use ($handle, $runner, $resDeferred) {
            if ($e) {
                Logger::log("Got exception while starting process worker: $e");
                $resDeferred->resolve($e);
                return;
            }
            Tools::callFork(self::readUnref($handle->stdout));
            Tools::callFork(self::readUnref($handle->stderr));

            $runner->join($handle)->onResolve(function (?\Throwable $e, ?int $res) use ($runner, $handle, $resDeferred) {
                $runner->destroy($handle);
                if ($e) {
                    Logger::log("Got exception from process worker: $e");
                    $resDeferred->fail($e);
                } else {
                    Logger::log("Process worker exited with $res!");
                    $resDeferred->fail(new Exception("Process worker exited with $res!"));
                }
            });
        });
        return $resDeferred->promise();
    }
    /**
     * Unreference and read data from fd, logging results.
     *
     * @param ProcessInputStream $stream
     * @return \Generator
     */
    private static function readUnref(ProcessInputStream $stream): \Generator
    {
        $stream->unreference();
        $lastLine = '';
        try {
            while (($chunk = yield $stream->read()) !== null) {
                $chunk = \explode("\n", \str_replace(["\r", "\n\n"], "\n", $chunk));
                $lastLine .= \array_shift($chunk);
                while ($chunk) {
                    Logger::log("Got message from worker: $lastLine");
                    $lastLine = \array_shift($chunk);
                }
            }
        } catch (\Throwable $e) {
            Logger::log("An error occurred while reading the process status: $e");
        } finally {
            Logger::log("Got final message from worker: $lastLine");
        }
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

    /**
     * Format PHP options.
     *
     * @param array $options
     * @return list<string>
     */
    private static function formatOptions(array $options): array
    {
        $result = [];

        foreach ($options as $option => $value) {
            $result[] = \sprintf("-d%s=%s", $option, $value);
        }

        return $result;
    }
}
