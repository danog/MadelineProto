<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Tgcalls\TgcallsTools;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * MadelineProto's error handler throws for every warning, @ included: an @ suppresses nothing, and code that relied
 * on it threw instead (e.g. DoHConnector's failed stream_socket_client() ended the connection attempt instead of
 * trying the remaining addresses).
 *
 * @internal
 */
final class ErrorSuppressionTest extends TestCase
{
    /** Where @ works: before the error handler is installed, or on warnings it doesn't throw for (phpseclib) */
    private const ALLOWED = [
        'Ipc/Runner/entry.php' => 3,
        'MTProtoTools/Crypt.php' => 1,
    ];

    public function testSrcCatchesWarningsInsteadOfSuppressingThem(): void
    {
        $src = \dirname(__DIR__, 3).'/src/';
        $found = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $count = 0;
            foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
                $count += $token === '@' ? 1 : 0;
            }
            $path = substr($file->getPathname(), \strlen($src));
            if ($count !== (self::ALLOWED[$path] ?? 0)) {
                $found[$path] = $count;
            }
        }
        self::assertSame([], $found, 'Catch the warnings in try/catch instead of using @');
    }

    public function testGunzipReturnsCorruptDataAsIs(): void
    {
        set_error_handler(Exception::exceptionErrorHandler(...));
        try {
            self::assertSame("\x1f\x8bnot gzip", TgcallsTools::gunzip("\x1f\x8bnot gzip"));
            self::assertSame("\x78\x9cnot zlib", TgcallsTools::gunzip("\x78\x9cnot zlib"));
            self::assertSame('hello', TgcallsTools::gunzip(gzencode('hello')));
        } finally {
            restore_error_handler();
        }
    }
}
