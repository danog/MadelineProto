<?php

declare(strict_types=1);

if (defined('MADELINE_POLYFILLED')) {
    return;
}

define('MADELINE_POLYFILLED', true);

if (PHP_VERSION_ID === 80202 || PHP_VERSION_ID === 80115 || PHP_VERSION_ID === 80203 || PHP_VERSION_ID === 80116) {
    echo('PHP 8.2.2, 8.2.3, 8.1.15, 8.1.16 have a critical garbage collector bug, please switch to PHP 8.1.14 or PHP 8.2.1: https://github.com/php/php-src/issues/10496, https://t.me/MadelineProto/605'.PHP_EOL);
    die(1);
}

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
