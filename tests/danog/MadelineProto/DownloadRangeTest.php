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
    private function downloader(string $file, int $hashSize = 0, bool $corrupt = false, bool $redirectHashes = true): object
    {
        return new class($file, $hashSize, $corrupt, $redirectHashes) {
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

            public function __construct(public string $file, private int $hashSize, private bool $corrupt, private bool $redirectHashes)
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
                    $hashes[] = ['offset' => $offset, 'limit' => \strlen($block), 'hash' => hash('sha256', $block, true)];
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

    private function download(object $downloader, int $offset, int $length, bool $knownSize = true): string
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
        );
        return $received;
    }

    /** @return iterable<string, array{int, int, int, int}> */
    public static function ranges(): iterable
    {
        yield 'origin Safari probe' => [0, 2, 0, 4096];
        yield 'origin block boundary' => [4095, 2, 0, 8192];
        yield 'origin metadata near EOF' => [270000, 30000, 0, 64 * 1024];
        yield 'CDN Safari probe' => [0, 2, 131072, 131072];
        yield 'CDN hash lookup at a nonzero offset' => [140000, 2, 65536, 65536];
        yield 'CDN range across hash blocks' => [126000, 16000, 131072, 262144];
        yield 'CDN multiple hash blocks in one part' => [0, 20000, 8192, 32768];
        yield 'CDN hash requiring multiple requests' => [200000, 2, 98304, 98304];
        yield 'CDN hash larger than one request' => [0, 2, 2097152, 2097152];
        yield 'CDN partial final hash block' => [299998, 2, 131072, 37856];
    }

    /** @dataProvider ranges */
    public function testRangeReturnsRequestedBytesWithinDownloadBudget(int $offset, int $length, int $hashSize, int $budget): void
    {
        $file = random_bytes(max(300000, $hashSize));
        $downloader = $this->downloader($file, $hashSize, redirectHashes: $offset === 0);
        self::assertSame(substr($file, $offset, $length), $this->download($downloader, $offset, $length));
        self::assertLessThanOrEqual($budget, $downloader->fetched);
    }

    public static function fullDownloads(): iterable
    {
        yield 'origin' => [0];
        yield 'CDN' => [131072];
    }

    /** @dataProvider fullDownloads */
    public function testFullDownloadKeepsLargeParts(int $hashSize): void
    {
        $file = random_bytes(2 * 1024 * 1024);
        $downloader = $this->downloader($file, $hashSize);
        self::assertSame($file, $this->download($downloader, 0, \strlen($file)));
        self::assertSame(2, $downloader->requests);
    }

    public static function invalidBlocks(): iterable
    {
        yield 'corrupt short range' => [null, 2];
        yield 'incomplete short range' => [4096, 2];
        yield 'empty short range' => [0, 2];
        yield 'corrupt large range' => [null, 1048576];
        yield 'incomplete large range' => [4096, 1048576];
    }

    /** @dataProvider invalidBlocks */
    public function testInvalidCdnBlockIsRejectedBeforeDelivery(?int $truncate, int $length): void
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
        );
    }

    public function testCdnReuploadContinuesTheDownload(): void
    {
        $file = random_bytes(131072);
        $downloader = $this->downloader($file, 131072);
        $downloader->reupload = true;
        self::assertSame(substr($file, 0, 2), $this->download($downloader, 0, 2));
    }

    public function testCdnRangeCanEndAtAnUnknownFileSize(): void
    {
        $file = random_bytes(300000);
        $downloader = $this->downloader($file, 131072);
        self::assertSame(substr($file, 299998), $this->download($downloader, 299998, 12, knownSize: false));
    }
}
