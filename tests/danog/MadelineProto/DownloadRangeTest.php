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
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;

final class DownloadRangeTest extends TestCase
{
    private function downloader(string $file): object
    {
        return new class($file) {
            use Files;
            public Settings $settings;
            public object $datacenter;
            public object $logger;
            public int $fetched = 0;
            public int $requests = 0;

            public function __construct(private string $file)
            {
                $this->settings = new Settings;
                $this->datacenter = new class {
                    public function has(int $dc): bool
                    {
                        return false;
                    }
                };
                $this->logger = new class {
                    public function logger(mixed $message, int $level = 0): void
                    {
                    }
                };
            }

            public function isTestMode(): bool
            {
                return false;
            }

            public function methodCallAsyncRead(string $method, array $args, int $dc): array
            {
                $bytes = substr($this->file, $args['offset'], $args['limit']);
                $this->fetched += \strlen($bytes);
                $this->requests++;
                return ['_' => 'upload.file', 'type' => ['_' => 'storage.fileMp4'], 'bytes' => $bytes];
            }
        };
    }

    /** @return iterable<string, array{int, int, int}> */
    public static function ranges(): iterable
    {
        yield 'Safari probe' => [0, 2, 4096];
        yield 'probe across a block boundary' => [4095, 2, 8192];
        yield 'video metadata near EOF' => [2 * 1024 * 1024 - 100000, 100000, 128 * 1024];
    }

    /** @dataProvider ranges */
    public function testShortRangeReturnsOnlyRequestedBytesWithoutFetchingLargeParts(int $offset, int $length, int $budget): void
    {
        $file = str_repeat('0123456789abcdef', 128 * 1024);
        $downloader = $this->downloader($file);
        $received = '';
        $downloader->downloadToCallable(
            ['InputFileLocation' => ['dc_id' => 2], 'size' => \strlen($file)],
            static function (string $bytes) use (&$received): void {
                $received .= $bytes;
            },
            seekable: false,
            offset: $offset,
            end: $offset + $length,
        );
        self::assertSame(substr($file, $offset, $length), $received);
        self::assertLessThanOrEqual($budget, $downloader->fetched);
    }

    public function testFullDownloadKeepsLargeParts(): void
    {
        $file = random_bytes(2 * 1024 * 1024);
        $downloader = $this->downloader($file);
        $received = '';
        $downloader->downloadToCallable(
            ['InputFileLocation' => ['dc_id' => 2], 'size' => \strlen($file)],
            static function (string $bytes) use (&$received): void {
                $received .= $bytes;
            },
            seekable: false,
        );
        self::assertSame($file, $received);
        self::assertSame(2, $downloader->requests);
    }
}
