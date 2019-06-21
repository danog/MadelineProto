<?php
// Polyfill for some PHP 5 functions
function callMe($allable, ...$args)
{
    return $allable(...$args);
}
function returnMe($res) {
    return $res;
}
if (!function_exists('is_iterable')) {
    function is_iterable($var) {
        return is_array($var) || $var instanceof Traversable;
    }
}
if (!function_exists('error_clear_last')) {
    function error_clear_last() {
        @trigger_error("");
    }
}
