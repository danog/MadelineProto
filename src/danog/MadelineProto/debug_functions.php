<?php

/**
 * Function to dump the hex version of a string.
 *
 * @param $what What to dump.
 */
function hex_dump(...$what)
{
    foreach ($what as $w) {
        var_dump(bin2hex($w));
    }
}

/**
 * Function to visualize byte streams. Split into bytes, print to console.
 * :param bs: BYTE STRING.
 */
function vis($bs)
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