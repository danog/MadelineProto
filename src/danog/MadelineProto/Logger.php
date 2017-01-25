<?php
/*
Copyright 2016 Daniil Gentili
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
    public static $mode = null;
    public static $optional = null;
    public static $constructed = false;
    public static $prefix = '';

    /*
     * Constructor function
     * Accepts various logger modes:
     * 0 - No logger
     * 1 - Log to the default logger destination
     * 2 - Log to file defined in second parameter
     * 3 - Echo logs
     */
    public static function constructor(&$mode, &$optional = null, $prefix = '')
    {
        if ($mode === null) {
            throw new Exception('No mode was specified!');
        }
        self::$mode = &$mode;
        self::$optional = &$optional;
        self::$constructed = true;
        self::$prefix = $prefix === '' ? '' : ', '.$prefix;
    }

    public static function log(...$params)
    {
        if (!self::$constructed) {
            throw new Exception("The constructor function wasn't called! Please call the constructor function before using this method.");
        }
        foreach ($params as $param) {
            if (!is_string($param)) {
                $param = var_export($param, true);
            }
            $param = str_pad(basename(debug_backtrace()[0]['file'], '.php').self::$prefix.': ', 16 + strlen(self::$prefix))."\t".$param;
            switch (self::$mode) {
                case 1:
                    error_log($param);
                    break;
                case 2:
                    error_log($param.PHP_EOL, 3, self::$optional);
                    break;
                case 3:
                    echo $param.PHP_EOL;
                    break;
                default:
                    break;
            }
        }
    }
}
