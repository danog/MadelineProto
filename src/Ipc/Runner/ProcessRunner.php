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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Ipc\Runner;

use Amp\ByteStream\ReadableResourceStream;
use Amp\Process\Internal\Posix\Runner;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use Error;
use Revolt\EventLoop;
use Throwable;

use const ARRAY_FILTER_USE_BOTH;
use const DIRECTORY_SEPARATOR;
use const PATH_SEPARATOR;
use const PHP_BINARY;
use const PHP_BINDIR;
use const PHP_OS;
use const PHP_SAPI;
use function Amp\Process\escapeArgument;

/**
 * @internal
 */
final class ProcessRunner extends RunnerAbstract
{
    private const CGI_VARS = [
        'AUTH_TYPE',
        'CONTENT_LENGTH',
        'CONTENT_TYPE',
        'GATEWAY_INTERFACE',
        'PATH_INFO',
        'PATH_TRANSLATED',
        'QUERY_STRING',
        'REMOTE_ADDR',
        'REMOTE_HOST',
        'REMOTE_IDENT',
        'REMOTE_USER',
        'REQUEST_METHOD',
        'SCRIPT_NAME',
        'SERVER_NAME',
        'SERVER_PORT',
        'SERVER_PROTOCOL',
        'SERVER_SOFTWARE',
    ];

    /** @var string|null Cached path to located PHP binary. */
    private static ?string $binaryPath = null;

    /**
     * Resources.
     */
    private static array $resources = [];

    /**
     * Runner.
     *
     * @psalm-suppress InternalMethod, InternalProperty, InternalClass
     *
     * @param string $session Session path
     */
    public static function start(string $session, int $startupId): bool
    {
        if (PHP_SAPI === 'cli') {
            $binary = PHP_BINARY;
        } else {
            $binary = self::$binaryPath ?? self::locateBinary();
        }

        $options = [
            'html_errors' => '0',
            'display_errors' => '0',
            'log_errors' => '1',
        ];

        $runner = self::getScriptPath();

        $command = [
            $binary,
            ...self::formatOptions($options),
            $runner,
            'madeline-ipc',
            $session,
            (string) $startupId,
        ];
        $command = implode(' ', array_map(escapeArgument(...), $command));
        Logger::log("Starting process with $command");

        $params = [
            'argv' => ['madeline-ipc', $session, $startupId],
            'cwd' => Magic::getcwd(),
        ];
        $root = '';
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            try {
                $root = WebRunner::getAbsoluteRootDir();
            } catch (Throwable) {
            }
        }
        $envVars = array_merge(
            array_filter($_SERVER, static fn ($v, $k): bool => \is_string($v) && !\in_array($k, self::CGI_VARS, true), ARRAY_FILTER_USE_BOTH),
            [
                'QUERY_STRING' => http_build_query($params),
                'absoluteRootDir' => $root,
                'serverName' => $_SERVER['SERVER_NAME'] ?? '',
            ],
        );

        self::$resources []= proc_open(
            $command,
            [
                ["pipe", "r"],
                ["pipe", "w"],
                ["pipe", "w"],
            ],
            $pipes,
            null,
            $envVars
        );
        $stdout = new ReadableResourceStream($pipes[1]);
        $stderr = new ReadableResourceStream($pipes[2]);

        EventLoop::queue(self::readUnref(...), $stdout);
        EventLoop::queue(self::readUnref(...), $stderr);
        return true;
    }
    /**
     * Unreference and read data from fd, logging results.
     */
    private static function readUnref(ReadableResourceStream $stream): void
    {
        $stream->unreference();
        $lastLine = '';
        try {
            while (($chunk = $stream->read()) !== null) {
                $chunk = explode("\n", str_replace(["\r", "\n\n"], "\n", $chunk));
                $lastLine .= array_shift($chunk);
                while ($chunk) {
                    Logger::log("Got message from worker: $lastLine");
                    $lastLine = array_shift($chunk);
                }
            }
        } catch (Throwable $e) {
            Logger::log("An error occurred while reading the process status: $e");
        } finally {
            Logger::log("Got final message from worker: $lastLine");
        }
    }
    private static function locateBinary(): string
    {
        $executable = strncasecmp(PHP_OS, 'WIN', 3) === 0 ? 'php.exe' : 'php';

        $paths = array_filter(explode(PATH_SEPARATOR, getenv('PATH') ?: ''));
        $paths[] = PHP_BINDIR;
        $paths = array_unique($paths);

        foreach ($paths as $path) {
            $path .= DIRECTORY_SEPARATOR.$executable;
            if (is_executable($path)) {
                return self::$binaryPath = $path;
            }
        }

        throw new Error('Could not locate PHP executable binary');
    }

    /**
     * Format PHP options.
     *
     * @return list<string>
     */
    private static function formatOptions(array $options): array
    {
        $result = [];

        foreach ($options as $option => $value) {
            $result[] = sprintf('-d%s=%s', $option, $value);
        }

        return $result;
    }
}
