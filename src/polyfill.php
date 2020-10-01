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
function __destructure($list, $value): array
{
    $res = [];
    foreach ($list as $key) {
        if (\is_string($key)) {
            $res[] = $value[$key];
        } else {
            $res = \array_merge($res, __destructure($key, $value[$key]));
        }
    }
    return $res;
}

trait MyCallableMaker
{
    use \Amp\CallableMaker {
        callableFromInstanceMethod as public;
        callableFromStaticMethod as public;
    }
}
