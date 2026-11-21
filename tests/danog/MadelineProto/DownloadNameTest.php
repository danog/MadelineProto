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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Amp\Cancellation;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\MTProtoTools\Files;
use PHPUnit\Framework\TestCase;

/**
 * The name downloadToDir() gives a file comes from attributes the sender controls: it must stay inside the directory.
 */
final class DownloadNameTest extends TestCase
{
    private function downloader(): object
    {
        return new class {
            use Files;
            public ?string $file = null;
            public function downloadToFile(mixed $messageMedia, string|FileCallbackInterface $file, ?callable $cb = null, ?Cancellation $cancellation = null): string
            {
                return $this->file = $file;
            }
        };
    }

    /**
     * Secret chat media: every attribute comes from the peer, unfiltered by Telegram's servers.
     */
    private static function secretDocument(array $attributes, string $mime = 'application/x-php'): array
    {
        return [
            '_' => 'decryptedMessageMediaDocument',
            'file' => ['id' => 1, 'access_hash' => 2, 'dc_id' => 4, 'key_fingerprint' => 3],
            'key' => str_repeat("\0", 32),
            'iv' => str_repeat("\0", 32),
            'size' => 10,
            'mime_type' => $mime,
            'attributes' => $attributes,
        ];
    }

    public static function maliciousNames(): iterable
    {
        yield 'audio title' => [[['_' => 'documentAttributeAudio', 'duration' => 1, 'title' => '../../../../tmp/x']]];
        yield 'audio performer' => [[['_' => 'documentAttributeAudio', 'duration' => 1, 'title' => 'a', 'performer' => '/../../../tmp/x']]];
        yield 'backslashes' => [[['_' => 'documentAttributeAudio', 'duration' => 1, 'title' => '..\\..\\x']]];
        yield 'NUL' => [[['_' => 'documentAttributeAudio', 'duration' => 1, 'title' => "x\0/../y"]]];
        yield 'parent file name' => [[['_' => 'documentAttributeFilename', 'file_name' => '..']]];
        yield 'current file name' => [[['_' => 'documentAttributeFilename', 'file_name' => '.']]];
        yield 'dots title' => [[['_' => 'documentAttributeAudio', 'duration' => 1, 'title' => '..']], ''];
    }

    /**
     * @dataProvider maliciousNames
     */
    public function testDownloadToDirStaysInDirectory(array $attributes, string $mime = 'application/x-php'): void
    {
        $downloader = $this->downloader();
        $dir = '/srv/downloads';
        $downloader->downloadToDir(self::secretDocument($attributes, $mime), $dir);
        $name = substr($downloader->file, \strlen("$dir/"));
        $this->assertStringStartsWith("$dir/", $downloader->file);
        $this->assertStringNotContainsString('/', $name);
        $this->assertStringNotContainsString('\\', $name);
        $this->assertStringNotContainsString("\0", $name);
        $this->assertNotSame('', trim($name, '.'), "'$name' names the directory or its parent");
    }

    public function testNormalNamesAreKept(): void
    {
        $downloader = $this->downloader();
        $info = $downloader->getDownloadInfo(self::secretDocument([['_' => 'documentAttributeAudio', 'duration' => 1, 'title' => 'Song', 'performer' => 'AC.DC']], 'audio/mpeg'));
        $this->assertSame('Song - AC.DC', $info['name']);

        $info = $downloader->getDownloadInfo(self::secretDocument([['_' => 'documentAttributeFilename', 'file_name' => 'report.v2.pdf']], 'application/pdf'));
        $this->assertSame('report.v2', $info['name']);
        $this->assertSame('.pdf', $info['ext']);
    }
}
