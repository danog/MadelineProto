<?php

declare(strict_types=1);

/**
 * Logger module.
 *
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

namespace danog\MadelineProto;

use Amp\ByteStream\WritableResourceStream;
use Amp\ByteStream\WritableStream;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DIRECTORY_SEPARATOR;

use const E_ALL;
use const FILE_APPEND;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PATHINFO_DIRNAME;
use const PHP_EOL;
use const PHP_SAPI;

use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;

/**
 * Logger class.
 */
final class Logger
{
    /**
     * @internal ANSI foreground color escapes
     */
    const FOREGROUND = ['default' => 39, 'black' => 30, 'red' => 31, 'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35, 'cyan' => 36, 'light_gray' => 37, 'dark_gray' => 90, 'light_red' => 91, 'light_green' => 92, 'light_yellow' => 93, 'light_blue' => 94, 'light_magenta' => 95, 'light_cyan' => 96, 'white' => 97];
    /**
     * @internal ANSI background color escapes
     */
    const BACKGROUND = ['default' => 49, 'black' => 40, 'red' => 41, 'magenta' => 45, 'yellow' => 43, 'green' => 42, 'blue' => 44, 'cyan' => 46, 'light_gray' => 47, 'dark_gray' => 100, 'light_red' => 101, 'light_green' => 102, 'light_yellow' => 103, 'light_blue' => 104, 'light_magenta' => 105, 'light_cyan' => 106, 'white' => 107];
    /**
     * @internal ANSI modifier escapes
     */
    const SET = ['bold' => 1, 'dim' => 2, 'underlined' => 3, 'blink' => 4, 'reverse' => 5, 'hidden' => 6];
    /**
     * @internal ANSI reset modifier escapes
     */
    const RESET = ['all' => 0, 'bold' => 21, 'dim' => 22, 'underlined' => 24, 'blink' => 25, 'reverse' => 26, 'hidden' => 28];
    /**
     * Logging mode.
     *
     */
    private readonly int $mode;
    /**
     * Optional logger parameter.
     *
     * @var null|string|callable
     */
    private readonly mixed $optional;
    /**
     * Logger prefix.
     *
     */
    private readonly string $prefix;
    /**
     * Logging level.
     *
     */
    private readonly int $level;
    /**
     * Logging colors.
     *
     */
    private array $colors;
    /**
     * Newline.
     *
     */
    private readonly string $newline;
    /**
     * Logfile.
     *
     */
    private readonly WritableStream $stdout;
    /**
     * Log rotation loop ID.
     */
    private ?string $rotateId = null;
    /**
     * PSR logger.
     */
    private readonly PsrLogger $psr;
    /**
     * Default logger instance.
     */
    public static ?self $default = null;
    /**
     * Whether the AGPL notice was printed.
     *
     */
    private static bool $printed = false;
    /**
     * Ultra verbose logging.
     *
     * @internal
     */
    const ULTRA_VERBOSE = 5;
    /**
     * Verbose logging.
     *
     * @internal
     */
    const VERBOSE = 4;
    /**
     * Notice logging.
     *
     * @internal
     */
    const NOTICE = 3;
    /**
     * Warning logging.
     *
     * @internal
     */
    const WARNING = 2;
    /**
     * Error logging.
     *
     * @internal
     */
    const ERROR = 1;
    /**
     * Log only fatal errors.
     *
     * @internal
     */
    const FATAL_ERROR = 0;

    /**
     * Default logger (syslog).
     *
     * @internal
     */
    const DEFAULT_LOGGER = 1;
    /**
     * File logger.
     *
     * @internal
     */
    const FILE_LOGGER = 2;
    /**
     * Echo logger.
     *
     * @internal
     */
    const ECHO_LOGGER = 3;
    /**
     * Callable logger.
     *
     * @internal
     */
    const CALLABLE_LOGGER = 4;

    /**
     * Ultra verbose level.
     */
    const LEVEL_ULTRA_VERBOSE = self::ULTRA_VERBOSE;
    /**
     * Verbose level.
     */
    const LEVEL_VERBOSE = self::VERBOSE;
    /**
     * Notice level.
     */
    const LEVEL_NOTICE = self::NOTICE;
    /**
     * Warning level.
     */
    const LEVEL_WARNING = self::WARNING;
    /**
     * Error level.
     */
    const LEVEL_ERROR = self::ERROR;
    /**
     * Fatal error level.
     */
    const LEVEL_FATAL = self::FATAL_ERROR;

    /**
     * Default logger (syslog).
     */
    const LOGGER_DEFAULT = self::DEFAULT_LOGGER;
    /**
     * Echo logger.
     */
    const LOGGER_ECHO = self::ECHO_LOGGER;
    /**
     * File logger.
     */
    const LOGGER_FILE = self::FILE_LOGGER;
    /**
     * Callable logger.
     */
    const LOGGER_CALLABLE = self::CALLABLE_LOGGER;

    /**
     * Construct global static logger from MadelineProto settings.
     *
     * @param SettingsLogger $settings Settings instance
     */
    public static function constructorFromSettings(SettingsLogger $settings): self
    {
        return self::$default = new self($settings);
    }

    /**
     * Construct logger.
     */
    public function __construct(SettingsLogger $settings, string $prefix = '')
    {
        $this->psr = new PsrLogger($this);
        $this->prefix = $prefix === '' ? '' : ', '.$prefix;

        $this->mode = $settings->getType();
        $this->level = $settings->getLevel();

        $optional = $settings->getExtra();

        $maxSize = $settings->getMaxSize();

        if ($this->mode === self::FILE_LOGGER) {
            if (!$optional || !\file_exists(\pathinfo($optional, PATHINFO_DIRNAME))) {
                $optional = Magic::$script_cwd.DIRECTORY_SEPARATOR.'MadelineProto.log';
            }
            if (!\str_ends_with($optional, '.log')) {
                $optional .= '.log';
            }
            if ($maxSize !== -1 && \file_exists($optional) && \filesize($optional) > $maxSize) {
                \file_put_contents($optional, '');
            }
        }
        $this->optional = $optional;
        $this->colors[self::ULTRA_VERBOSE] = \implode(';', [self::FOREGROUND['light_gray'], self::SET['dim']]);
        $this->colors[self::VERBOSE] = \implode(';', [self::FOREGROUND['green'], self::SET['bold']]);
        $this->colors[self::NOTICE] = \implode(';', [self::FOREGROUND['yellow'], self::SET['bold']]);
        $this->colors[self::WARNING] = \implode(';', [self::FOREGROUND['white'], self::SET['dim'], self::BACKGROUND['red']]);
        $this->colors[self::ERROR] = \implode(';', [self::FOREGROUND['white'], self::SET['bold'], self::BACKGROUND['red']]);
        $this->colors[self::FATAL_ERROR] = \implode(';', [self::FOREGROUND['red'], self::SET['bold'], self::BACKGROUND['light_gray']]);
        $this->newline = PHP_EOL;
        if ($this->mode === self::ECHO_LOGGER) {
            $this->stdout = getStdout();
            if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
                $this->newline = '<br>'.$this->newline;
            }
        } elseif ($this->mode === self::FILE_LOGGER) {
            $this->stdout = new WritableResourceStream(\fopen($this->optional, 'a'));
            if ($maxSize !== -1) {
                $optional = $this->optional;
                $stdout = $this->stdout;
                $this->rotateId = EventLoop::repeat(
                    10,
                    static function () use ($maxSize, $optional, $stdout): void {
                        \clearstatcache(true, $optional);
                        if (\file_exists($optional) && \filesize($optional) >= $maxSize) {
                            \ftruncate($stdout->getResource(), 0);
                            self::log("Automatically truncated logfile to $maxSize");
                        }
                    },
                );
                EventLoop::unreference($this->rotateId);
            }
        } elseif ($this->mode === self::DEFAULT_LOGGER) {
            $result = @\ini_get('error_log');
            if ($result === 'syslog') {
                $this->stdout = getStderr();
            } elseif ($result) {
                $this->stdout = new WritableResourceStream(\fopen($result, 'a+'));
            } else {
                $this->stdout = getStderr();
            }
        }

        self::$default = $this;
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            try {
                \error_reporting(E_ALL);
                \ini_set('log_errors', '1');
                \ini_set('error_log', $this->mode === self::FILE_LOGGER
                    ? $this->optional
                    : Magic::$script_cwd.DIRECTORY_SEPARATOR.'MadelineProto.log');
            } catch (Exception) {
                $this->logger('Could not enable PHP logging');
            }
        }

        if (!self::$printed) {
            self::$printed = true;
            $this->colors[self::NOTICE] = \implode(';', [self::FOREGROUND['light_gray'], self::SET['bold'], self::BACKGROUND['blue']]);
            $this->logger('MadelineProto '.\danog\MadelineProto\API::RELEASE);
            $this->logger('Copyright (C) 2016-'.\date('Y').' Daniil Gentili');
            $this->logger('Licensed under AGPLv3');
            $this->logger('https://github.com/danog/MadelineProto');
            $this->colors[self::NOTICE] = \implode(';', [self::FOREGROUND['yellow'], self::SET['bold']]);
        }
    }
    /**
     * Truncate logfile.
     */
    public function truncate(): void
    {
        if ($this->mode === self::FILE_LOGGER) {
            Assert::true($this->stdout instanceof WritableResourceStream);
            \ftruncate($this->stdout->getResource(), 0);
        }
    }
    /**
     * Destructor function.
     */
    public function __destruct()
    {
        if ($this->rotateId) {
            EventLoop::cancel($this->rotateId);
        }
    }
    /**
     * Log a message.
     *
     * @param mixed $param Message
     * @param int   $level Logging level
     */
    public static function log(mixed $param, int $level = self::NOTICE): void
    {
        if (!\is_null(self::$default)) {
            self::$default->logger($param, $level, \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php'));
        } else {
            echo $param.PHP_EOL;
        }
    }
    /**
     * Log a message.
     *
     * @param mixed  $param Message to log
     * @param int    $level Logging level
     * @param string $file  File that originated the message
     */
    public function logger(mixed $param, int $level = self::NOTICE, string $file = ''): void
    {
        if ($level > $this->level) {
            return;
        }
        if (Magic::$suspendPeriodicLogging) {
            Magic::$suspendPeriodicLogging->getFuture()->map(fn () => $this->logger($param, $level, $file));
            return;
        }

        if ($this->mode === self::CALLABLE_LOGGER) {
            \call_user_func_array($this->optional, [$param, $level]);
            return;
        }
        $prefix = $this->prefix;
        if ($param instanceof Throwable) {
            $param = (string) $param;
        } elseif (!\is_string($param)) {
            $param = \json_encode($param, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        if (empty($file)) {
            $file = \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        $param = \str_pad($file.$prefix.': ', 16 + \strlen($prefix))."\t".$param;
        if ($this->mode === self::DEFAULT_LOGGER) {
            try {
                $this->stdout->write($param.$this->newline);
            } catch (\Throwable) {
                \error_log($param);
            }
            return;
        }
        $param = Magic::$isatty ? "\33[".$this->colors[$level].'m'.$param."\33[0m".$this->newline : $param.$this->newline;
        try {
            $this->stdout->write($param);
        } catch (\Throwable) {
            switch ($this->mode) {
                case self::ECHO_LOGGER:
                    echo $param;
                    break;
                case self::FILE_LOGGER:
                    \file_put_contents($this->optional, $param, FILE_APPEND);
                    break;
            }
        }
    }

    /**
     * Get PSR logger.
     */
    public function getPsrLogger(): LoggerInterface
    {
        return $this->psr;
    }
}
