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
        return $resto > 0 ? $resto + abs($b) : $resto;
    }

    public function utf8ize($d)
    {
        if ($this->is_array($d)) {
            foreach ($d as $k => $v) {
                if ($k === 'bytes' || $this->is_array($v)) {
                    $d[$k] = $this->utf8ize($v);
                }
            }
        } elseif (is_string($d)) {
            return utf8_encode($d);
        }

        return $d;
    }

    public function is_array($elem)
    {
        return is_array($elem) || ($elem instanceof \Volatile);
    }

    public function __call($method, $params)
    {
        return class_exists('\Thread') ? $method(...$this->array_cast_recursive($params)) : $method(...$params);
    }

    public function array_cast_recursive($array)
    {
        if (!class_exists('\Thread')) {
            return $array;
        }
        if ($this->is_array($array)) {
            if (!is_array($array)) {
                $array = (array) $array;
            }
            foreach ($array as $key => $value) {
                $array[$key] = $this->array_cast_recursive($value);
            }
        }

        return $array;
    }
    public function unpack_signed_int($value) {
        return unpack('l', $this->BIG_ENDIAN ? strrev($value) : $value)[1];
    }
    public function unpack_signed_long($value) {
        return unpack('q', $this->BIG_ENDIAN ? strrev($value) : $value)[1];
    }
    public function pack_signed_int($value) {
        if ($value > 2147483647) throw new TL\Exception('Provided value '.$value.' is bigger than 2147483647');
        if ($value < -2147483648) throw new TL\Exception('Provided value '.$value.' is smaller than -2147483648');
        $res = pack('l', $value);
        return $this->BIG_ENDIAN ? strrev($res) : $res;
    }
    public function pack_signed_long($value) {
        if ($value > 9223372036854775807) throw new TL\Exception('Provided value '.$value.' is bigger than 9223372036854775807');
        if ($value < -9223372036854775808) throw new TL\Exception('Provided value '.$value.' is smaller than -9223372036854775808');
        $res = pack('q', $value);
        return $this->BIG_ENDIAN ? strrev($res) : $res;
    }

}
