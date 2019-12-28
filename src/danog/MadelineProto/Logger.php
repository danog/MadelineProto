<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Failure;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;

/**
 * Logger class.
 */
class Logger
{
    use Tools;

    const FOREGROUND = ['default' => 39, 'black' => 30, 'red' => 31, 'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35, 'cyan' => 36, 'light_gray' => 37, 'dark_gray' => 90, 'light_red' => 91, 'light_green' => 92, 'light_yellow' => 93, 'light_blue' => 94, 'light_magenta' => 95, 'light_cyan' => 96, 'white' => 97];
    const BACKGROUND = ['default' => 49, 'black' => 40, 'red' => 41, 'magenta' => 45, 'yellow' => 43, 'green' => 42, 'blue' => 44, 'cyan' => 46, 'light_gray' => 47, 'dark_gray' => 100, 'light_red' => 101, 'light_green' => 102, 'light_yellow' => 103, 'light_blue' => 104, 'light_magenta' => 105, 'light_cyan' => 106, 'white' => 107];
    const SET = ['bold' => 1, 'dim' => 2, 'underlined' => 3, 'blink' => 4, 'reverse' => 5, 'hidden' => 6];
    const RESET = ['all' => 0, 'bold' => 21, 'dim' => 22, 'underlined' => 24, 'blink' => 25, 'reverse' => 26, 'hidden' => 28];

    /**
     * Logging mode.
     *
     * @var integer
     */
    public $mode = 0;
    /**
     * Optional logger parameter.
     *
     * @var mixed
     */
    public $optional = null;
    /**
     * Logger prefix.
     *
     * @var string
     */
    public $prefix = '';
    /**
     * Logging level.
     *
     * @var integer
     */
    public $level = self::NOTICE;
    /**
     * Logging colors.
     *
     * @var array
     */
    public $colors = [];
    /**
     * Newline.
     *
     * @var string
     */
    public $newline = "\n";

    /**
     * Default logger instance.
     *
     * @var self
     */
    public static $default;
    /**
     * Whether the AGPL notice was printed.
     *
     * @var boolean
     */
    public static $printed = false;

    const ULTRA_VERBOSE = 5;
    const VERBOSE = 4;
    const NOTICE = 3;
    const WARNING = 2;
    const ERROR = 1;
    const FATAL_ERROR = 0;

    const NO_LOGGER = 0;
    const DEFAULT_LOGGER = 1;
    const FILE_LOGGER = 2;
    const ECHO_LOGGER = 3;
    const CALLABLE_LOGGER = 4;

    /**
     * Construct global static logger from MadelineProto settings.
     *
     * @param array $settings Settings array
     *
     * @return void
     */
    public static function constructorFromSettings(array $settings)
    {
        if (!self::$default) {
            // The getLogger function will automatically init the static logger, but we'll do it again anyway
            self::$default = self::getLoggerFromSettings(MTProto::getSettings($settings));
        }
    }
    /**
     * Get logger from MadelineProto settings.
     *
     * @param array  $settings Settings array
     * @param string $prefix   Optional prefix for log messages
     *
     * @return self
     */
    public static function getLoggerFromSettings(array $settings, string $prefix = ''): self
    {
        if (isset($settings['logger']['rollbar_token']) && $settings['logger']['rollbar_token'] !== '' && \class_exists(\Rollbar\Rollbar::class)) {
            @\Rollbar\Rollbar::init(['environment' => 'production', 'root' => __DIR__, 'access_token' => isset($settings['logger']['rollbar_token']) && !\in_array($settings['logger']['rollbar_token'], ['f9fff6689aea4905b58eec73f66c791d', '300afd7ccef346ea84d0c185ae831718', '11a8c2fe4c474328b40a28193f8d63f5', 'beef2d426496462ba34dcaad33d44a14']) || $settings['pwr']['pwr'] ? $settings['logger']['rollbar_token'] : 'c07d9b2f73c2461297b0beaef6c1662f'], false, false);
        } else {
            Exception::$rollbar = false;
            RPCErrorException::$rollbar = false;
        }
        if (!isset($settings['logger']['logger_param']) && isset($settings['logger']['param'])) {
            $settings['logger']['logger_param'] = $settings['logger']['param'];
        }
        if (PHP_SAPI !== 'cli' && isset($settings['logger']['logger_param']) && $settings['logger']['logger_param'] === 'MadelineProto.log') {
            $settings['logger']['logger_param'] = Magic::$script_cwd.'/MadelineProto.log';
        }

        $logger = new self($settings['logger']['logger'], $settings['logger']['logger_param'] ?? '', $prefix, $settings['logger']['logger_level'] ?? Logger::VERBOSE, $settings['logger']['max_size'] ?? 100 * 1024 * 1024);
        if (!self::$default) {
            self::$default = $logger;
        }

        if (PHP_SAPI !== 'cli') {
            try {
                \error_reporting(E_ALL);
                \ini_set('log_errors', 1);
                \ini_set('error_log', $settings['logger']['logger'] === self::FILE_LOGGER ? $settings['logger']['logger_param'] : Magic::$script_cwd.'/MadelineProto.log');
                \error_log('Enabled PHP logging');
            } catch (\danog\MadelineProto\Exception $e) {
                $logger->logger('Could not enable PHP logging');
            }
        }

        return $logger;
    }

    /**
     * Construct global logger.
     *
     * @param int    $mode     One of the logger constants
     * @param mixed  $optional Optional parameter for logger
     * @param string $prefix   Prefix for log messages
     * @param int    $level    Default logging level
     * @param int    $max_size Maximum size for logfile
     *
     * @return void
     */
    public static function constructor(int $mode, $optional = null, string $prefix = '', int $level = self::NOTICE, int $max_size = 100 * 1024 * 1024)
    {
        self::$default = new self($mode, $optional, $prefix, $level, $max_size);
    }

    /**
     * Construct global logger.
     *
     * @param int    $mode     One of the logger constants
     * @param mixed  $optional Optional parameter for logger
     * @param string $prefix   Prefix for log messages
     * @param int    $level    Default logging level
     * @param int    $max_size Maximum size for logfile
     *
     * @return void
     */
    public function __construct(int $mode, $optional = null, string $prefix = '', int $level = self::NOTICE, int $max_size = 100 * 1024 * 1024)
    {
        if ($mode === null) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['no_mode_specified']);
        }
        $this->mode = $mode;
        $this->optional = $mode == 2 ? Absolute::absolute($optional) : $optional;
        $this->prefix = $prefix === '' ? '' : ', '.$prefix;
        $this->level = $level;

        if ($this->mode === 2 && !\file_exists(\pathinfo($this->optional, PATHINFO_DIRNAME))) {
            $this->optional = Magic::$script_cwd.'/MadelineProto.log';
        }

        if ($this->mode === 2 && !\preg_match('/\.log$/', $this->optional)) {
            $this->optional .= '.log';
        }

        if ($mode === 2 && $max_size !== -1 && \file_exists($this->optional) && \filesize($this->optional) > $max_size) {
            \unlink($this->optional);
        }

        $this->colors[self::ULTRA_VERBOSE] = \implode(';', [self::FOREGROUND['light_gray'], self::SET['dim']]);
        $this->colors[self::VERBOSE] = \implode(';', [self::FOREGROUND['green'], self::SET['bold']]);
        $this->colors[self::NOTICE] = \implode(';', [self::FOREGROUND['yellow'], self::SET['bold']]);
        $this->colors[self::WARNING] = \implode(';', [self::FOREGROUND['white'], self::SET['dim'], self::BACKGROUND['red']]);
        $this->colors[self::ERROR] = \implode(';', [self::FOREGROUND['white'], self::SET['bold'], self::BACKGROUND['red']]);
        $this->colors[self::FATAL_ERROR] = \implode(';', [self::FOREGROUND['red'], self::SET['bold'], self::BACKGROUND['light_gray']]);
        $this->newline = PHP_EOL;

        if ($this->mode === 3) {
            $this->stdout = getStdout();
            if (PHP_SAPI !== 'cli') {
                $this->newline = '<br>'.$this->newline;
            }
        } elseif ($this->mode === 2) {
            $this->stdout = new ResourceOutputStream(\fopen($this->optional, 'a+'));
        } elseif ($this->mode === 1) {
            $result = @\ini_get('error_log');
            if ($result === 'syslog') {
                $this->stdout = getStderr();
            } elseif ($result) {
                $this->stdout = new ResourceOutputStream(\fopen($result, 'a+'));
            } else {
                $this->stdout = getStderr();
            }
        }
    }

    /**
     * Log a message.
     *
     * @param mixed $param Message
     * @param int   $level Logging level
     *
     * @return void
     */
    public static function log($param, int $level = self::NOTICE)
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
     *
     * @return void
     */
    public function logger($param, int $level = self::NOTICE, string $file = ''): void
    {
        if ($level > $this->level || $this->mode === 0) {
            return;
        }
        if (!self::$printed) {
            self::$printed = true;
            $this->colors[self::NOTICE] = \implode(';', [self::FOREGROUND['light_gray'], self::SET['bold'], self::BACKGROUND['blue']]);

            $this->logger('MadelineProto');
            $this->logger('Copyright (C) 2016-2019 Daniil Gentili');
            $this->logger('Licensed under AGPLv3');
            $this->logger('https://github.com/danog/MadelineProto');

            $this->colors[self::NOTICE] = \implode(';', [self::FOREGROUND['yellow'], self::SET['bold']]);
        }
        if ($this->mode === 4) {
            \call_user_func_array($this->optional, [$param, $level]);
            return;
        }
        $prefix = $this->prefix;
        if (\danog\MadelineProto\Magic::$has_thread && \is_object(\Thread::getCurrentThread())) {
            $prefix .= ' (t)';
        }
        if ($param instanceof \Throwable) {
            $param = (string) $param;
        } elseif (!\is_string($param)) {
            $param = \json_encode($param, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if (empty($file)) {
            $file = \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        $param = \str_pad($file.$prefix.': ', 16 + \strlen($prefix))."\t".$param;
        switch ($this->mode) {
                case 1:
                    if ($this->stdout->write($param.$this->newline) instanceof Failure) {
                        \error_log($param);
                    }
                    break;
                default:
                    $param = Magic::$isatty ? "\33[".$this->colors[$level].'m'.$param."\33[0m".$this->newline : $param.$this->newline;
                    if ($this->stdout->write($param) instanceof Failure) {
                        switch ($this->mode) {
                            case 3: echo $param; break;
                            case 2: \file_put_contents($this->optional, $param, FILE_APPEND); break;
                        }
                    }
                    break;
            }
    }
}
