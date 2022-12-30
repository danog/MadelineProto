<?php

declare(strict_types=1);

if (PHP_OS_FAMILY === 'Windows') {
    //echo(PHP_EOL.'========='.PHP_EOL.'WARNING: MadelineProto does not support Windows, please use Linux or another UNIX system (WSLv2 on Windows, Mac OS, BSD, etc).'.PHP_EOL.'========='.PHP_EOL.PHP_EOL);
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
