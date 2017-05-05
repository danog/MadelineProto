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
        if ($length === 0) {
            return '';
        }

        return \phpseclib\Crypt\Random::string($length);
    }

    /**
     * posmod(numeric,numeric) : numeric
     * Works just like the % (modulus) operator, only returns always a postive number.
     */
    public function posmod($a, $b)
    {
        $resto = $a % $b;
        if ($resto < 0) {
            $resto += abs($b);
        }

        return $resto;
    }

    public function utf8ize($d)
    {
        if ($this->is_array($d)) {
            foreach ($d as $k => $v) {
                if ($k === 'bytes') {
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
        return $method(...$this->array_cast_recursive($params));
    }

    public function array_cast_recursive($array)
    {
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
}
