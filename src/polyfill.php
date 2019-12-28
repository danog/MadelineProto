<?php
// Polyfill for some PHP 5 functions
function callMe($allable, ...$args)
{
    return $allable(...$args);
}
function returnMe($res)
{
    return $res;
}
function __coalesce($ifNotNull, $then)
{
    return $ifNotNull ?: $then;
}
function __destructure($list, $value)
{
    $res = [];
    foreach ($list as $key) {
        if (\is_string($key)) {
            $res []= $value[$key];
        } else {
            $res = \array_merge($res, __destructure($key, $value[$key]));
        }
    }
    return $res;
}
if (!\function_exists('is_iterable')) {
    function is_iterable($var)
    {
        return \is_array($var) || $var instanceof Traversable;
    }
}
if (!\function_exists('error_clear_last')) {
    function error_clear_last()
    {
        @\trigger_error("");
    }
}

if (!\defined('MADELINEPROTO_TEST')) {
    \define('MADELINEPROTO_TEST', 'NOT PONY');
}
trait MyCallableMaker
{
    use \Amp\CallableMaker {
        callableFromInstanceMethod as public;
        callableFromStaticMethod as public;
    }
}
