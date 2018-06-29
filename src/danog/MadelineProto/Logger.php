<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/
/*
 * Logger class
 */

namespace danog\MadelineProto;

class Logger
{
    const foreground = ['default' => 39, 'black' => 30, 'red' => 31, 'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35, 'cyan' => 36, 'light_gray' => 37, 'dark_gray' => 90, 'light_red' => 91, 'light_green' => 92, 'light_yellow' => 93, 'light_blue' => 94, 'light_magenta' => 95, 'light_cyan' => 96, 'white' => 97];
    const background = ['default' => 49, 'black' => 40, 'red' => 41, 'magenta' => 45, 'yellow' => 43, 'green' => 42, 'blue' => 44, 'cyan' => 46, 'light_gray' => 47, 'dark_gray' => 100, 'light_red' => 101, 'light_green' => 102, 'light_yellow' => 103, 'light_blue' => 104, 'light_magenta' => 105, 'light_cyan' => 106, 'white' => 107];
    const set = ['bold' => 1, 'dim' => 2, 'underlined' => 3, 'blink' => 4, 'reverse' => 5, 'hidden' => 6];
    const reset = ['all' => 0, 'bold' => 21, 'dim' => 22, 'underlined' => 24, 'blink' => 25, 'reverse' => 26, 'hidden' => 28];

    public $mode = 0;
    public $optional = null;
    public $prefix = '';
    public $level = 3;
    public $colors = [];

    public static $default;
    public static $printed = false;

    const ULTRA_VERBOSE = 5;
    const VERBOSE = 4;
    const NOTICE = 3;
    const WARNING = 2;
    const ERROR = 1;
    const FATAL_ERROR = 0;

    /*
     * Constructor function
     * Accepts various logger modes:
     * 0 - No logger
     * 1 - Log to the default logger destination
     * 2 - Log to file defined in second parameter
     * 3 - Echo logs
     * 4 - Call callable provided in logger_param. logger_param must accept two parameters: array $message, int $level
     *     $message is an array containing the messages the log, $level, is the logging level
     */
    public static function constructor($mode, $optional = null, $prefix = '', $level = self::NOTICE, $max_size = 100 * 1024 * 1024)
    {
        self::$default = new self($mode, $optional, $prefix, $level, $max_size);
    }

    public function __construct($mode, $optional = null, $prefix = '', $level = self::NOTICE, $max_size = 100 * 1024 * 1024)
    {
        if ($mode === null) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['no_mode_specified']);
        }
        $this->mode = $mode;
        $this->optional = $mode == 2 ? Absolute::absolute($optional) : $optional;
        $this->prefix = $prefix === '' ? '' : ', '.$prefix;
        $this->level = $level;

        if ($mode === 2 && $max_size !== -1 && file_exists($this->optional) && filesize($this->optional) > $max_size) {
            unlink($this->optional);
        }

        $this->colors[self::ULTRA_VERBOSE] = implode(';', [self::foreground['light_gray'], self::set['dim']]);
        $this->colors[self::VERBOSE] = implode(';', [self::foreground['green'], self::set['bold']]);
        $this->colors[self::NOTICE] = implode(';', [self::foreground['yellow'], self::set['bold']]);
        $this->colors[self::WARNING] = implode(';', [self::foreground['white'], self::set['dim'], self::background['red']]);
        $this->colors[self::ERROR] = implode(';', [self::foreground['white'], self::set['bold'], self::background['red']]);
        $this->colors[self::FATAL_ERROR] = implode(';', [self::foreground['red'], self::set['bold'], self::background['light_gray']]);
    }

    public static function log($param, $level = self::NOTICE)
    {
        if (!is_null(self::$default)) {
            self::$default->logger($param, $level, basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php'));
        }
    }

    public function logger($param, $level = self::NOTICE, $file = null)
    {
        if ($level > $this->level || $this->mode === 0) {
            return false;
        }
        if (!self::$printed) {
            self::$printed = true;
            $this->colors[self::NOTICE] = implode(';', [self::foreground['light_gray'], self::set['bold'], self::background['blue']]);

            $this->logger('MadelineProto');
            $this->logger('Copyright (C) 2016-2018 Daniil Gentili');
            $this->logger('Licensed under AGPLv3');
            $this->logger('https://github.com/danog/MadelineProto');

            $this->colors[self::NOTICE] = implode(';', [self::foreground['yellow'], self::set['bold']]);
        }
        if ($this->mode === 4) {
            return call_user_func_array($this->optional, [$param, $level]);
        }
        $prefix = $this->prefix;
        if (\danog\MadelineProto\Magic::$has_thread && is_object(\Thread::getCurrentThread())) {
            $prefix .= ' (t)';
        }
        if (\danog\MadelineProto\Magic::is_fork()) {
            $prefix .= ' (p)';
        }
        if (!is_string($param)) {
            $param = json_encode($param, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if ($file === null) {
            $file = basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        $param = str_pad($file.$prefix.': ', 16 + strlen($prefix))."\t".$param;
        switch ($this->mode) {
                case 1:
                    error_log($param);
                    break;
                case 2:
                    error_log($param.PHP_EOL, 3, $this->optional);
                    break;
                case 3:
                    echo Magic::$isatty ? "\33[".$this->colors[$level].'m'.$param."\33[0m".PHP_EOL : $param.PHP_EOL;
                    break;
            }
    }
}
