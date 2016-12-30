<?php
/*
Copyright 2016 Daniil Gentili
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
    /**
     * posmod(numeric,numeric) : numeric
     * Works just like the % (modulus) operator, only returns always a postive number.
     */
    public static function posmod($a, $b)
    {
        $resto = $a % $b;
        if ($resto < 0) {
            $resto += abs($b);
        }

        return $resto;
    }

    public static function fread_all($handle)
    {
        $pos = ftell($handle);
        fseek($handle, 0);
        $content = stream_get_contents($handle, fstat($handle)['size']);
        fseek($handle, $pos);

        return $content;
    }

    public static function fopen_and_write($filename, $mode, $data)
    {
        $handle = fopen($filename, $mode);
        fwrite($handle, $data);
        rewind($handle);

        return $handle;
    }

    public static function string2bin($string)
    {
        $res = null;
        foreach (explode('\\', $string) as $s) {
            if ($s != null && strlen($s) == 3) {
                $res .= hex2bin(substr($s, 1));
            }
        }

        return $res;
    }

    // taken from mochikit: range( [start,] stop[, step] )
    public static function range($start, $stop = null, $step = 1)
    {
        if ($stop === null) {
            $stop = $start;
            $start = 0;
        }
        if ($stop <= $start && $step < 0) {
            $arr = range($stop, $start, -$step);
            array_pop($arr);

            return array_reverse($arr, false);
        }
        if ($step > 1 && $step > ($stop - $start)) {
            $arr = [$start];
        } else {
            $arr = range($start, $stop, $step);
            array_pop($arr);
        }

        return $arr;
    }
}
