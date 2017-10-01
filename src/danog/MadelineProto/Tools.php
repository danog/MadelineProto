<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Some tools.
 */
trait Tools
{
    public function random($length)
    {
        return $length === 0 ? '' : \phpseclib\Crypt\Random::string($length);
    }

    /**
     * posmod(numeric,numeric) : numeric
     * Works just like the % (modulus) operator, only returns always a postive number.
     */
    public function posmod($a, $b)
    {
        $resto = $a % $b;

        return $resto < 0 ? ($resto + abs($b)) : $resto;
    }

    public function array_cast_recursive($array, $force = false)
    {
        if (!\danog\MadelineProto\Logger::$has_thread && !$force) {
            return $array;
        }
        if (is_array($array)) {
            if (!is_array($array)) {
                $array = (array) $array;
            }
            foreach ($array as $key => $value) {
                $array[$key] = $this->array_cast_recursive($value, $force);
            }
        }

        return $array;
    }

    public function unpack_signed_int($value)
    {
        if (strlen($value) !== 4) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_4']);
        }

        return unpack('l', \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public function unpack_signed_long($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return unpack('q', \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public function pack_signed_int($value)
    {
        if ($value > 2147483647) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_2147483647'], $value));
        }
        if ($value < -2147483648) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_2147483648'], $value));
        }
        $res = pack('l', $value);

        return \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($res) : $res;
    }

    public function pack_signed_long($value)
    {
        if ($value > 9223372036854775807) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_9223372036854775807'], $value));
        }
        if ($value < -9223372036854775808) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_9223372036854775808'], $value));
        }
        $res = \danog\MadelineProto\Logger::$bigint ? ($this->pack_signed_int($value)."\0\0\0\0") : (\danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev(pack('q', $value)) : pack('q', $value));

        return $res;
    }

    public function pack_unsigned_int($value)
    {
        if ($value > 4294967295) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_4294967296'], $value));
        }
        if ($value < 0) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_0'], $value));
        }

        return pack('V', $value);
    }

    public function pack_double($value)
    {
        $res = pack('d', $value);
        if (strlen($res) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['encode_double_error']);
        }

        return \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($res) : $res;
    }

    public function unpack_double($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return unpack('d', \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }
}
