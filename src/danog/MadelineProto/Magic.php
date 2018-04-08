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

namespace danog\MadelineProto;

class Magic
{
    public static $storage = [];
    public static $has_thread = false;
    public static $BIG_ENDIAN = false;
    public static $bigint = true;
    public static $isatty = false;
    public static $is_fork = false;
    public static $can_getmypid = true;
    public static $processed_fork = false;
    public static $ipv6;
    private static $pid;

    public static $inited = false;

    public static function class_exists()
    {
        if (!self::$inited) {
            self::$has_thread = class_exists('\\Thread') && method_exists('\\Thread', 'getCurrentThread');
            self::$BIG_ENDIAN = pack('L', 1) === pack('N', 1);
            self::$bigint = PHP_INT_SIZE < 8;
            self::$ipv6 = (bool) strlen(@file_get_contents('http://v6.ipv6-test.com/api/myip.php', false, stream_context_create(['http' => ['timeout' => 1]]))) > 0;
            preg_match('/const V = (\\d+);/', @file_get_contents('https://raw.githubusercontent.com/danog/MadelineProto/master/src/danog/MadelineProto/MTProto.php'), $matches);
            if (isset($matches[1]) && \danog\MadelineProto\MTProto::V < (int) $matches[1]) {
                throw new \danog\MadelineProto\Exception(hex2bin(\danog\MadelineProto\Lang::$current_lang['v_error']), 0, null, 'MadelineProto', 1);
            }
            if (class_exists('\\danog\\MadelineProto\\VoIP')) {
                if (!defined('\\danog\\MadelineProto\\VoIP::PHP_LIBTGVOIP_VERSION') || \danog\MadelineProto\VoIP::PHP_LIBTGVOIP_VERSION !== '1.1.2') {
                    throw new \danog\MadelineProto\Exception(hex2bin(\danog\MadelineProto\Lang::$current_lang['v_tgerror']), 0, null, 'MadelineProto', 1);
                }
            }

            try {
                self::$isatty = defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT);
            } catch (\danog\MadelineProto\Exception $e) {
            }
            self::$can_getmypid = !(isset($_SERVER['SERVER_ADMIN']) && strpos($_SERVER['SERVER_ADMIN'], 'altervista.org'));
            self::$inited = true;
        }
    }

    public static function is_fork()
    {
        if (self::$is_fork) {
            return true;
        }
        if (!self::$can_getmypid) {
            return false;
        }

        try {
            if (self::$pid === null) {
                self::$pid = getmypid();
            }

            return self::$is_fork = self::$pid !== getmypid();
        } catch (\danog\MadelineProto\Exception $e) {
            return self::$can_getmypid = false;
        }
    }
}
