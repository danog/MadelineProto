<?php

use danog\MadelineProto\Tools;

\chdir(__DIR__.'/../');

require 'vendor/autoload.php';

$class = new \ReflectionClass(Tools::class);
$methods = $class->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);

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

    $find[] = "\$this->$method(";
    $replace[] = "\\danog\\MadelineProto\\Tools::$method(";
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
