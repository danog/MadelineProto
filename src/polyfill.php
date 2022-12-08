<?php

if (PHP_OS_FAMILY === 'Windows') {
    //echo(PHP_EOL.'========='.PHP_EOL.'WARNING: MadelineProto does not support Windows, please use Linux or another UNIX system (WSLv2 on Windows, Mac OS, BSD, etc).'.PHP_EOL.'========='.PHP_EOL.PHP_EOL);
}

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
        if (is_string($key)) {
            $res[] = $value[$key];
        } else {
            $res = array_merge($res, __destructure($key, $value[$key]));
        }
    }
    return $res;
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
