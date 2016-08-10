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

class DebugTools
{
    /**
     * Function to dump the hex version of a string.
     *
     * @param $what What to dump.
     */
    public static function hex_dump(...$what)
    {
        foreach ($what as $w) {
            var_dump(bin2hex($w));
        }
    }

    /**
     * Function to visualize byte streams. Split into bytes, print to console.
     * :param bs: BYTE STRING.
     */
    public static function vis($bs)
    {
        $bs = str_split($bs);
        $symbols_in_one_line = 8;
        $n = floor(len($bs) / $symbols_in_one_line);
        $i = 0;
        foreach (pyjslib_range($n) as $i) {
            echo $i * $symbols_in_one_line.' | '.implode(' ',
                array_map(function ($el) {
                    return bin2hex($el);
                }, array_slice($bs, $i * $symbols_in_one_line, ($i + 1) * $symbols_in_one_line))
            ).PHP_EOL;
        }
        if (len($bs) % $symbols_in_one_line != 0) {
            echo($i + 1) * $symbols_in_one_line.' | '.implode(' ',
                array_map(function ($el) {
                    return bin2hex($el);
                }, array_slice($bs, ($i + 1) * $symbols_in_one_line))
            ).PHP_EOL;
        }
    }
}
