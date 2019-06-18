<?php
// Polyfill for some PHP 5 functions
function callMe($allable, ...$args)
{
    return $allable(...$args);
}
function returnMe($res) {
    return $res;
}
if (!class_exists('\Throwable')) {
    class Throwable extends \Exception {}
}
if (!function_exists('is_iterable')) {
    function is_iterable($var) {
        return is_array($var) || $var instanceof Traversable;
    }
}
