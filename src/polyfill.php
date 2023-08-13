<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

if (defined('MADELINE_POLYFILLED')) {
    return;
}

define('MADELINE_POLYFILLED', true);

if ((PHP_MINOR_VERSION === 2 && PHP_VERSION_ID < 80204)
    || (PHP_MINOR_VERSION === 1 && PHP_VERSION_ID < 80117)
) {
    echo('MadelineProto requires PHP 8.2.4+ (recommended) or 8.1.17+.'.PHP_EOL);
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
