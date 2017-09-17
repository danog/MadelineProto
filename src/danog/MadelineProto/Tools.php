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
            throw new TL\Exception('Length is not equal to 4');
        }

        return unpack('l', \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public function unpack_signed_long($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception('Length is not equal to 8');
        }

        return unpack('q', \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public function pack_signed_int($value)
    {
        if ($value > 2147483647) {
            throw new TL\Exception('Provided value '.$value.' is bigger than 2147483647');
        }
        if ($value < -2147483648) {
            throw new TL\Exception('Provided value '.$value.' is smaller than -2147483648');
        }
        $res = pack('l', $value);

        return \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($res) : $res;
    }

    public function pack_signed_long($value)
    {
        if ($value > 9223372036854775807) {
            throw new TL\Exception('Provided value '.$value.' is bigger than 9223372036854775807');
        }
        if ($value < -9223372036854775808) {
            throw new TL\Exception('Provided value '.$value.' is smaller than -9223372036854775808');
        }
        $res = \danog\MadelineProto\Logger::$bigint ? ($this->pack_signed_int($value)."\0\0\0\0") : (\danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev(pack('q', $value)) : pack('q', $value));

        return $res;
    }

    public function pack_unsigned_int($value)
    {
        if ($value > 4294967295) {
            throw new TL\Exception('Provided value '.$value.' is bigger than 4294967296');
        }
        if ($value < 0) {
            throw new TL\Exception('Provided value '.$value.' is smaller than 0');
        }

        return pack('V', $value);
    }

    public function pack_double($value)
    {
        $res = pack('d', $value);
        if (strlen($res) !== 8) {
            throw new TL\Exception('Could not properly encode double');
        }

        return \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($res) : $res;
    }

    public function unpack_double($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception('Length is not equal to 8');
        }

        return unpack('d', \danog\MadelineProto\Logger::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }
}
