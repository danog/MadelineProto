<?php

declare(strict_types=1);

use Amp\Http\Client\Cookie\InMemoryCookieJar;
use Amp\Http\Client\Cookie\LocalCookieJar;
use Amp\Socket\EncryptableSocket;
use Amp\Socket\ResourceSocket;

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
$ampFilePolyfill .= '}';
eval($ampFilePolyfill);
unset($ampFilePolyfill);

class_alias(LocalCookieJar::class, InMemoryCookieJar::class);
class_alias(ResourceSocket::class, EncryptableSocket::class);
