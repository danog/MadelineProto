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
$findDocs = [];
$replaceDocs = [];
foreach ($methods as $methodObj) {
    $method = $methodObj->getName();
    if (\strpos($method, '__') === 0 || $method === 'async') {
        continue;
    }
    $method = Tools::fromCamelCase($method);

    $new = Tools::fromSnakeCase($method);
    $new = \str_ireplace(['mtproto', 'api'], ['MTProto', 'API'], $new);
    $new = \preg_replace('/TL$/i', 'TL', $new);
    if (!\in_array($method, ['discardCallAsync', 'acceptCallAsync', 'requestCallAsync'])) {
        $new = \preg_replace('/async$/i', '', $new);
    }

    $findDocs []= Tools::fromCamelCase($new).'(';
    $replaceDocs []= $new.'(';

    $findDocs []= $method.'(';
    $replaceDocs []= $new.'(';

    $findDocs []= Tools::fromCamelCase($new).'.';
    $replaceDocs []= $new.'.';

    $findDocs []= $method.'.';
    $replaceDocs []= $new.'.';

    $findDocs []= Tools::fromCamelCase($new).']';
    $replaceDocs []= $new.']';

    $findDocs []= $method.']';
    $replaceDocs []= $new.']';

    if (\method_exists((string) $methodObj->getDeclaringClass(), \preg_replace('/async$/i', '', $method))) {
        \var_dump("Skipping $method => $new");
        continue;
    }

    if (!\function_exists($method)) {
        $find[] = "$method";
        $replace[] = "$new";
        continue;
    }
    $find[] = ">$method(";
    $replace[] = ">$new(";

    $find[] = ":$method(";
    $replace[] = ":$new(";

    $find[] = "function $method(";
    $replace[] = "function $new(";
}

foreach (new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(\realpath('.'))), '/\.php$/') as $filename) {
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
    $new = \str_replace($findDocs, $replaceDocs, $old = \file_get_contents($filename));
    do {
        \file_put_contents($filename, $new);
        $new = \str_replace($findDocs, $replaceDocs, $old = \file_get_contents($filename));
    } while ($old !== $new);
}
$filename = 'README.md';

    $new = \str_replace($findDocs, $replaceDocs, $old = \file_get_contents($filename));
    do {
        \file_put_contents($filename, $new);
        $new = \str_replace($findDocs, $replaceDocs, $old = \file_get_contents($filename));
    } while ($old !== $new);
