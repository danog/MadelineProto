<?php

use danog\MadelineProto\Tools;
use HaydenPierce\ClassFinder\ClassFinder;

\chdir(__DIR__.'/../');

require 'vendor/autoload.php';

$classes = ClassFinder::getClassesInNamespace(danog\MadelineProto::class, ClassFinder::RECURSIVE_MODE);
$methods = [];
foreach ($classes as $class) {
    $class = new \ReflectionClass($class);
    $methods = \array_merge($class->getMethods(), $methods);
}
$methods = \array_unique($methods);

function ssort($a, $b)
{
    return \strlen($b->getName())-\strlen($a->getName());
}

\usort($methods, 'ssort');

$find = [];
$replace = [];
foreach ($methods as $methodObj) {
    $method = $methodObj->getName();
    if (\strpos($method, '__') === 0 || $method === 'async') {
        continue;
    }
    $new = Tools::from_snake_case($method);
    $new = \str_ireplace(['mtproto', 'api'], ['MTProto', 'API'], $new);
    if (!\in_array($method, ['discard_call_async', 'accept_call_async', 'request_call_async'])) {
        $new = \preg_replace('/async$/i', '', $new);
    }

    if (\method_exists((string) $methodObj->getDeclaringClass(), \preg_replace('/async$/i', '', $method))) {
        \var_dump("Skipping $method => $new");
        continue;
    }

    if (!\function_exists($method)) {
        $find[] = "$method(";
        $replace[] = "$new(";
        continue;
    }
    $find[] = ">$method(";
    $replace[] = ">$new(";

    $find[] = ":$method(";
    $replace[] = ":$new(";

    $find[] = "function $method(";
    $replace[] = "function $new(";
}

foreach (new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(\realpath('src'))), '/\.php$/') as $filename) {
    $filename = (string) $filename;
    $new = \str_replace($find, $replace, $old = \file_get_contents($filename));
    do {
        \file_put_contents($filename, $new);
        $new = \str_replace($find, $replace, $old = \file_get_contents($filename));
    } while ($old !== $new);
}
exit;

foreach (new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(\realpath('docs'))), '/\.md$/') as $filename) {
    $filename = (string) $filename;
    $new = \str_replace($find, $replace, $old = \file_get_contents($filename));
    do {
        \file_put_contents($filename, $new);
        $new = \str_replace($find, $replace, $old = \file_get_contents($filename));
    } while ($old !== $new);
}
$filename = 'README.md';

    $new = \str_replace($find, $replace, $old = \file_get_contents($filename));
    do {
        \file_put_contents($filename, $new);
        $new = \str_replace($find, $replace, $old = \file_get_contents($filename));
    } while ($old !== $new);
