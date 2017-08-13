<?php
/*
Copyright 2016-2017 Daniil Gentili
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
    const foreground = [
        'default' => 39,

        'black'      => 30,
        'red'        => 31,
        'green'      => 32,
        'yellow'     => 33,
        'blue'       => 34,
        'magenta'    => 35,
        'cyan'       => 36,
        'light_gray' => 37,

        'dark_gray'     => 90,
        'light_red'     => 91,
        'light_green'   => 92,
        'light_yellow'  => 93,
        'light_blue'    => 94,
        'light_magenta' => 95,
        'light_cyan'    => 96,
        'white'         => 97,
    ];

    const background = [
        'default' => 49,

        'black'      => 40,
        'red'        => 41,
        'magenta'    => 45,
        'yellow'     => 43,
        'green'      => 42,
        'blue'       => 44,
        'cyan'       => 46,
        'light_gray' => 47,

        'dark_gray'     => 100,
        'light_red'     => 101,
        'light_green'   => 102,
        'light_yellow'  => 103,
        'light_blue'    => 104,
        'light_magenta' => 105,
        'light_cyan'    => 106,
        'white'         => 107,
    ];

    const set = [
        'bold'       => 1,
        'dim'        => 2,
        'underlined' => 3,
        'blink'      => 4,
        'reverse'    => 5,
        'hidden'     => 6,
    ];

    const reset = [
        'all'        => 0,
        'bold'       => 21,
        'dim'        => 22,
        'underlined' => 24,
        'blink'      => 25,
        'reverse'    => 26,
        'hidden'     => 28,
    ];

    public static $storage = [];
    public static $mode = null;
    public static $optional = null;
    public static $constructed = false;
    public static $prefix = '';
    public static $level = 3;
    public static $has_thread = false;
    public static $BIG_ENDIAN = false;
    public static $bigint = true;
    public static $colors = [];
    public static $isatty = false;

    const ULTRA_VERBOSE = 5;
    const VERBOSE = 4;
    const NOTICE = 3;
    const WARNING = 2;
    const ERROR = 1;
    const FATAL_ERROR = 0;

    public static function class_exists()
    {
        self::$has_thread = class_exists('\Thread') && method_exists('\Thread', 'getCurrentThread');
        self::$BIG_ENDIAN = (pack('L', 1) === pack('N', 1));
        self::$bigint = PHP_INT_SIZE < 8;

        preg_match('/const V = (\d+);/', file_get_contents('https://raw.githubusercontent.com/danog/MadelineProto/master/src/danog/MadelineProto/MTProto.php'), $matches);

        if (isset($matches[1]) && \danog\MadelineProto\MTProto::V < (int) $matches[1]) {
            throw new \danog\MadelineProto\Exception(hex2bin('506c656173652075706461746520746f20746865206c61746573742076657273696f6e206f66204d6164656c696e6550726f746f2e'), 0, null, 'MadelineProto', 1);
        }
        if (class_exists('\danog\MadelineProto\VoIP')) {
            if (!defined('\danog\MadelineProto\VoIP::PHP_LIBTGVOIP_VERSION') || \danog\MadelineProto\VoIP::PHP_LIBTGVOIP_VERSION !== '1.1.2') {
                throw new \danog\MadelineProto\Exception(hex2bin('506c6561736520757064617465207068702d6c69627467766f6970'), 0, null, 'MadelineProto', 1);
            }

            try {
                \Threaded::extend('\danog\MadelineProto\VoIP');
            } catch (\RuntimeException $e) {
            }
        }
        self::$colors[self::ULTRA_VERBOSE] = implode(';', [self::foreground['light_gray'], self::set['dim']]);
        self::$colors[self::VERBOSE] = implode(';', [self::foreground['green'], self::set['bold']]);
        self::$colors[self::NOTICE] = implode(';', [self::foreground['yellow'], self::set['bold']]);
        self::$colors[self::WARNING] = implode(';', [self::foreground['white'], self::set['dim'], self::background['red']]);
        self::$colors[self::ERROR] = implode(';', [self::foreground['white'], self::set['bold'], self::background['red']]);
        self::$colors[self::FATAL_ERROR] = implode(';', [self::foreground['red'], self::set['bold'], self::background['light_gray']]);

        try {
            self::$isatty = defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT);
        } catch (\danog\MadelineProto\Exception $e) {
        }
    }

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
    public static function constructor($mode, $optional = null, $prefix = '', $level = self::NOTICE)
    {
        if ($mode === null) {
            throw new Exception('No mode was specified!');
        }
        self::$mode = $mode;
        self::$optional = $optional;
        self::$constructed = true;
        self::$prefix = $prefix === '' ? '' : ', '.$prefix;
        self::$level = $level;
        self::class_exists();
    }

    public static function log($params, $level = self::NOTICE)
    {
        if (self::$mode === 4) {
            return call_user_func_array(self::$optional, [is_array($params) ? $params : [$params], $level]);
        }
        if ($level > self::$level) {
            return false;
        }
        if (!self::$constructed) {
            throw new Exception("The constructor function wasn't called! Please call the constructor function before using this method.");
        }
        $prefix = self::$prefix;
        if (\danog\MadelineProto\Logger::$has_thread && is_object(\Thread::getCurrentThread())) {
            $prefix .= ' (t)';
        }
        foreach (is_array($params) ? $params : [$params] as $param) {
            if (!is_string($param)) {
                $param = json_encode($param, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $param = str_pad(basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['file'], '.php').$prefix.': ', 16 + strlen($prefix))."\t".$param;
            switch (self::$mode) {
                case 1:
                    error_log($param);
                    break;
                case 2:
                    error_log($param.PHP_EOL, 3, self::$optional);
                    break;
                case 3:
                    echo self::$isatty ? "\033[".self::$colors[$level].'m'.$param."\033[0m".PHP_EOL : $param.PHP_EOL;
                    break;
                default:
                    break;
            }
        }
    }
}
