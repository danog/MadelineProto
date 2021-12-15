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


$ampFilePolyfill = 'namespace Amp\\File {';
foreach ([
    'open' => 'openFile',
    'stat' => 'getStatus',
    'lstat' => 'getLinkStatus',
    'size' => 'getSize',
    'isdir' => 'isDirectory',
    'mtime' => 'getModificationTime',
    'atime' => 'getAccessTime',
    'ctime' => 'getCreationTime',
    'symlink' => 'createSymlink',
    'link' => 'createHardlink',
    'readlink' => 'resolveSymlink',
    'rename' => 'move',
    'unlink' => 'deleteFile',
    'rmdir' => 'deleteDirectory',
    'scandir' => 'listFiles',
    'chmod' => 'changePermissions',
    'chown' => 'changeOwner',
    'get' => 'read',
    'put' => 'write',
    'mkdir' => 'createDirectory',
] as $old => $new) {
    $ampFilePolyfill .= "function $old(...\$args) { return $new(...\$args); }";
}
$ampFilePolyfill .= "}";
eval($ampFilePolyfill);
unset($ampFilePolyfill);
