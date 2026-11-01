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
 * @author    Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProtoTools\Files;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;

final class DownloadRangeTest extends TestCase
{
    private function downloader(string $file, int $hashSize = 0, bool $corrupt = false, bool $redirectHashes = true, bool $fullLastHashLimit = false): object
    {
        return new class($file, $hashSize, $corrupt, $redirectHashes, $fullLastHashLimit) {
            use Files;
            public Settings $settings;
            public object $datacenter;
            public object $logger;
            public object $loginState;
            public array $config = [];
            public int $fetched = 0;
            public int $requests = 0;
            public bool $reupload = false;
            public ?int $truncate = null;

            public function __construct(public string $file, private int $hashSize, private bool $corrupt, private bool $redirectHashes, private bool $fullLastHashLimit)
            {
                $this->settings = new Settings;
                $this->loginState = new class {
                    public function getState(): object
                    {
                        return (object) ['authorizedDc' => 2];
                    }
                };
                $this->datacenter = new class {
                    public function has(int $dc): bool
                    {
                        return $dc === 4;
                    }
                };
                $this->logger = new class {
                    public function logger(mixed $message, int $level = 0): void
                    {
                    }
                };
            }

            public function getConfig(): void
            {
            }

            private function hashes(): array
            {
                $hashes = [];
                for ($offset = 0; $offset < \strlen($this->file); $offset += $this->hashSize) {
                    $block = substr($this->file, $offset, $this->hashSize);
                    // Telegram can advertise the full limit for the last, shorter block of the file.
                    $hashes[] = ['offset' => $offset, 'limit' => $this->fullLastHashLimit ? $this->hashSize : \strlen($block), 'hash' => hash('sha256', $block, true)];
                }
                return $hashes;
            }

            public function isTestMode(): bool
            {
                return false;
            }

            public function methodCallAsyncRead(string $method, array $args, int $dc): array
            {
                if ($this->hashSize) {
                    if ($method === 'upload.getFile') {
                        TestCase::assertTrue($args['cdn_supported']);
                        return ['_' => 'upload.fileCdnRedirect', 'file_token' => 'token', 'encryption_key' => str_repeat('k', 32), 'encryption_iv' => str_repeat('i', 16), 'dc_id' => 4, 'file_hashes' => $this->redirectHashes ? $this->hashes() : []];
                    }
                    if ($method === 'upload.getCdnFileHashes' || $method === 'upload.reuploadCdnFile') {
                        return $this->hashes();
                    }
                    if ($this->reupload) {
                        $this->reupload = false;
                        return ['_' => 'upload.cdnFileReuploadNeeded', 'request_token' => 'reupload'];
                    }
                    TestCase::assertSame('upload.getCdnFile', $method);
                }
                $bytes = substr($this->file, $args['offset'], $args['limit']);
                if ($this->truncate !== null) {
                    $bytes = substr($bytes, 0, $this->truncate);
                }
                $this->fetched += \strlen($bytes);
                $this->requests++;
                if ($this->hashSize) {
                    if ($this->corrupt && $bytes !== '') {
                        $bytes[0] = $bytes[0] ^ "\x01";
                    }
                    return ['_' => 'upload.cdnFile', 'bytes' => openssl_encrypt($bytes, 'aes-256-ctr', str_repeat('k', 32), OPENSSL_RAW_DATA, str_repeat('i', 12).pack('N', $args['offset'] >> 4))];
                }
                return ['_' => 'upload.file', 'type' => ['_' => 'storage.fileMp4'], 'bytes' => $bytes];
            }
        };
    }

    private function download(object $downloader, int $offset, int $length, bool $knownSize = true, ?int $partSize = null): string
    {
        $received = '';
        $media = ['InputFileLocation' => ['dc_id' => 2]];
        if ($knownSize) {
            $media['size'] = \strlen($downloader->file);
        }
        $downloader->downloadToCallable(
            $media,
            static function (string $bytes) use (&$received): void {
                $received .= $bytes;
            },
            seekable: false,
            offset: $offset,
            end: $offset + $length,
            part_size: $partSize,
        );
        return $received;
    }

    /** @return iterable<string, array{int, int, int, int}> */
    public static function shortCdnParts(): iterable
    {
        yield 'probe at a hash block' => [0, 2, 131072, 4096];
        yield 'probe inside a hash block' => [140000, 2, 65536, 4096];
        yield 'range across hash blocks' => [126000, 16000, 131072, 4096];
        yield 'multiple hash blocks in one part' => [0, 20000, 8192, 32768];
        yield 'hash block needing multiple requests' => [200000, 2, 98304, 4096];
        yield 'hash block larger than one request' => [0, 2, 2097152, 4096];
        yield 'partial final hash block' => [299998, 2, 131072, 4096];
    }

    /**
     * A part that doesn't cover whole hash blocks is fetched from the CDN as the blocks containing it, which are
     * verified, and then cut down to the part (redirected to the CDN or not, whatever the part size).
     *
     * @dataProvider shortCdnParts
     */
    public function testShortCdnPartsAreVerifiedOnWholeBlocks(int $offset, int $length, int $hashSize, int $partSize): void
    {
        $file = random_bytes(max(300000, $hashSize));
        $downloader = $this->downloader($file, $hashSize, redirectHashes: $offset === 0);
        self::assertSame(substr($file, $offset, $length), $this->download($downloader, $offset, $length, partSize: $partSize));
    }

    public static function invalidBlocks(): iterable
    {
        yield 'corrupt short part' => [null, 2, 4096];
        yield 'incomplete short part' => [4096, 2, 4096];
        yield 'empty short part' => [0, 2, 4096];
        yield 'corrupt large range' => [null, 1048576, null];
        yield 'incomplete large range' => [4096, 1048576, null];
    }

    /** @dataProvider invalidBlocks */
    public function testInvalidCdnBlockIsRejectedBeforeDelivery(?int $truncate, int $length, ?int $partSize): void
    {
        $size = max(131072, $length);
        $downloader = $this->downloader(random_bytes($size), 131072, corrupt: $truncate === null);
        $downloader->truncate = $truncate;
        $this->expectException(SecurityException::class);
        $downloader->downloadToCallable(
            ['InputFileLocation' => ['dc_id' => 2], 'size' => $size],
            static function (): void {
                self::fail('Unverified bytes must not be delivered');
            },
            end: $length,
            part_size: $partSize,
        );
    }

    public function testCdnReuploadContinuesTheDownload(): void
    {
        $file = random_bytes(131072);
        $downloader = $this->downloader($file, 131072);
        $downloader->reupload = true;
        self::assertSame(substr($file, 0, 2), $this->download($downloader, 0, 2, partSize: 4096));
    }

    public static function shortLastBlocks(): iterable
    {
        yield 'full download' => [0, 300000, null];
        yield 'range in the last block' => [299998, 2, 4096];
        yield 'range across the last block' => [260000, 40000, 4096];
    }

    /** @dataProvider shortLastBlocks */
    public function testCdnLastBlockCanBeShorterThanItsHashLimit(int $offset, int $length, ?int $partSize): void
    {
        $file = random_bytes(300000);
        $downloader = $this->downloader($file, 131072, fullLastHashLimit: true);
        self::assertSame(substr($file, $offset, $length), $this->download($downloader, $offset, $length, partSize: $partSize));
    }

    public function testCdnRangeCanEndAtAnUnknownFileSize(): void
    {
        $file = random_bytes(300000);
        $downloader = $this->downloader($file, 131072);
        self::assertSame(substr($file, 299998), $this->download($downloader, 299998, 12, knownSize: false, partSize: 4096));
    }
}
