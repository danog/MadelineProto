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

use Amp\CancelledException;
use Amp\DeferredCancellation;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\ResumableStream;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\delay;

/**
 * Resumable download streams, resumable uploads, and cancellations passed to downloads.
 *
 * The API instance is an IPC client, so streams, callbacks and cancellations all cross the IPC boundary.
 *
 * @internal
 */
final class ResumableStreamTest extends MadelineTestCase
{
    private const PART_SIZE = 512 * 1024;

    /** Contents of the test file. */
    private static string $data;
    /** Path of the test file. */
    private static string $path;
    /** The test file, uploaded to Telegram. */
    private static array $media;

    public static function setUpBeforeClass(): void
    {
        foreach (['API_ID', 'API_HASH', 'BOT_TOKEN', 'DEST'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        parent::setUpBeforeClass();
        self::$data = random_bytes(3_500_000);
        self::$path = tempnam(sys_get_temp_dir(), 'madeline-resumable-');
        file_put_contents(self::$path, self::$data);
        self::$media = self::uploadMedia(self::$MadelineProto->upload(new LocalFile(self::$path)));
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$path)) {
            @unlink(self::$path);
        }
        parent::tearDownAfterClass();
    }

    /**
     * Upload a document without sending it, returning its MessageMedia.
     */
    private static function uploadMedia(array $inputFile): array
    {
        return self::$MadelineProto->messages->uploadMedia(
            peer: getenv('DEST'),
            media: [
                '_' => 'inputMediaUploadedDocument',
                'file' => $inputFile,
                'mime_type' => 'application/octet-stream',
                'force_file' => true,
                'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'test.bin']],
            ],
        );
    }

    private static function download(array $media): string
    {
        return buffer(self::$MadelineProto->downloadToReturnedStream($media));
    }

    private static function size(): int
    {
        return \strlen(self::$data);
    }

    /* -------------------------------------------------------------------- *
     *  Download streams.
     * -------------------------------------------------------------------- */

    public function testTheReturnedStreamIsResumable(): void
    {
        $progress = 0.0;
        $stream = self::$MadelineProto->downloadToReturnedStream(self::$media, static function (float $p) use (&$progress): void {
            $progress = $p;
        });
        self::assertInstanceOf(ResumableStream::class, $stream);
        self::assertSame(self::$data, buffer($stream));
        delay(0.1);
        self::assertSame(100.0, $progress);
    }

    public function testResumingAfterOneChunk(): void
    {
        $stream = self::$MadelineProto->downloadToReturnedStream(self::$media);
        $first = $stream->read();
        $serialized = serialize($stream);
        $stream->close();
        self::assertFalse($stream->isReadable());
        self::assertNull($stream->read());
        self::assertSame(self::$data, $first.buffer(unserialize($serialized)));
    }

    public function testChainedResumesWithOffsetAndEnd(): void
    {
        $offset = 1234567;
        $end = 3000000;
        $stream = self::$MadelineProto->downloadToReturnedStream(self::$media, offset: $offset, end: $end);
        $out = $stream->read();
        $stream2 = unserialize(serialize($stream));
        $stream = null;
        $out .= $stream2->read();
        $stream3 = unserialize(serialize($stream2));
        $stream2->close();
        $out .= buffer($stream3);
        self::assertSame(substr(self::$data, $offset, $end - $offset), $out);

        $eof = unserialize(serialize($stream3));
        self::assertFalse($eof->isReadable());
        self::assertNull($eof->read());
    }

    public function testClosingMidDownload(): void
    {
        $stream = self::$MadelineProto->downloadToReturnedStream(self::$media);
        $stream->read();
        $closed = false;
        $stream->onClose(static function () use (&$closed): void {
            $closed = true;
        });
        $stream->close();
        delay(0.5);
        self::assertTrue($closed);
        self::assertSame(self::$data, self::download(self::$media));
    }

    public function testMediaGetStream(): void
    {
        $stream = self::$MadelineProto->wrapMedia(self::$media)->getStream();
        $first = $stream->read();
        $resumed = unserialize(serialize($stream));
        $stream->close();
        self::assertSame(self::$data, $first.buffer($resumed));
    }

    public function testChunkedDownloadsInARow(): void
    {
        // Used to hang after the last chunk over IPC.
        for ($x = 0; $x < 3; $x++) {
            $stream = self::$MadelineProto->downloadToReturnedStream(self::$media);
            $n = 0;
            while (($chunk = $stream->read()) !== null) {
                $n += \strlen($chunk);
            }
            self::assertSame(self::size(), $n);
        }
    }

    /* -------------------------------------------------------------------- *
     *  Cancellations.
     * -------------------------------------------------------------------- */

    public function testCancellingMidDownload(): void
    {
        $deferred = new DeferredCancellation;
        $n = 0;
        try {
            self::$MadelineProto->downloadToCallable(self::$media, static function (string $payload) use (&$n, $deferred): int {
                $n += \strlen($payload);
                $deferred->cancel();
                return \strlen($payload);
            }, null, false, 0, -1, null, $deferred->getCancellation());
            self::fail('The download was not cancelled');
        } catch (CancelledException) {
        }
        self::assertLessThan(self::size(), $n);
    }

    public function testAnAlreadyCancelledDownloadIsAborted(): void
    {
        $deferred = new DeferredCancellation;
        $deferred->cancel();
        $n = 0;
        try {
            self::$MadelineProto->downloadToCallable(self::$media, static function (string $payload) use (&$n): int {
                $n += \strlen($payload);
                return \strlen($payload);
            }, null, false, 0, -1, null, $deferred->getCancellation());
            self::fail('The download was not cancelled');
        } catch (CancelledException) {
        }
        self::assertLessThan(self::size(), $n);
    }

    public function testSlowCallbackWithACancellation(): void
    {
        $deferred = new DeferredCancellation;
        $n = 0;
        self::$MadelineProto->downloadToCallable(self::$media, static function (string $payload) use (&$n): int {
            delay(0.2);
            $n += \strlen($payload);
            return \strlen($payload);
        }, null, false, 0, -1, null, $deferred->getCancellation());
        self::assertSame(self::size(), $n);
    }

    public function testDownloadWithACancellationFromAnotherFiber(): void
    {
        $deferred = new DeferredCancellation;
        $n = 0;
        async(self::$MadelineProto->downloadToCallable(...), self::$media, static function (string $payload) use (&$n): int {
            $n += \strlen($payload);
            return \strlen($payload);
        }, null, false, 0, -1, null, $deferred->getCancellation())->await();
        self::assertSame(self::size(), $n);
    }

    /* -------------------------------------------------------------------- *
     *  Resumable uploads.
     * -------------------------------------------------------------------- */

    /**
     * Start uploading from a callable, and interrupt the upload once the first three parts were uploaded.
     */
    private static function interruptedUpload(string $resumeKey, bool $seekable, bool $encrypted): void
    {
        $data = self::$data;
        $deferred = new DeferredCancellation;
        $parts = 0;
        try {
            self::$MadelineProto->uploadFromCallable(
                static function (int $offset, int $size) use ($data, &$parts, $deferred): string {
                    if ($offset === 3 * self::PART_SIZE) {
                        // Parts are uploaded in parallel and can complete out of order: wait for the
                        // first three (for a sequential upload, the only ones read so far) before interrupting.
                        for ($x = 0; $parts < 3 && $x < 600; $x++) {
                            delay(0.05);
                        }
                        $deferred->cancel();
                    }
                    return substr($data, $offset, $size);
                },
                \strlen($data),
                'application/octet-stream',
                'test.bin',
                static function () use (&$parts): void {
                    $parts++;
                },
                $seekable,
                $encrypted,
                $deferred->getCancellation(),
                $resumeKey,
            );
            self::fail('The upload was not interrupted');
        } catch (CancelledException) {
        }
    }

    /**
     * Upload from a callable, recording the requested offsets.
     *
     * @param list<int> $requested
     */
    private static function resumedUpload(string $resumeKey, bool $seekable, bool $encrypted, array &$requested): array
    {
        $data = self::$data;
        return self::$MadelineProto->uploadFromCallable(
            static function (int $offset, int $size) use ($data, &$requested): string {
                $requested[] = $offset;
                return substr($data, $offset, $size);
            },
            \strlen($data),
            'application/octet-stream',
            'test.bin',
            null,
            $seekable,
            $encrypted,
            null,
            $resumeKey,
        );
    }

    public function testResumingASeekableUpload(): void
    {
        $key = 'test:'.bin2hex(random_bytes(8));
        self::interruptedUpload($key, true, false);
        $requested = [];
        $file = self::resumedUpload($key, true, false, $requested);

        self::assertLessThan((int) ceil(self::size() / self::PART_SIZE), \count($requested), 'Every part was uploaded again');
        self::assertSame(self::$data, self::download(self::uploadMedia($file)));
    }

    public function testResumingASequentialUpload(): void
    {
        $key = 'test:'.bin2hex(random_bytes(8));
        self::interruptedUpload($key, false, false);
        $requested = [];
        $file = self::resumedUpload($key, false, false, $requested);

        self::assertGreaterThan(0, $requested[0], 'The upload restarted from the beginning');
        self::assertSame(range($requested[0], $requested[0] + (\count($requested) - 1) * self::PART_SIZE, self::PART_SIZE), $requested);
        self::assertSame(self::$data, self::download(self::uploadMedia($file)));
    }

    public function testResumingAnEncryptedUpload(): void
    {
        $key = 'test:'.bin2hex(random_bytes(8));
        self::interruptedUpload($key, false, true);
        $requested = [];
        $file = self::resumedUpload($key, false, true, $requested);

        // Encrypted files can only be checked by sending them to a secret chat.
        self::assertGreaterThan(0, $requested[0], 'The upload restarted from the beginning');
        self::assertSame(range($requested[0], $requested[0] + (\count($requested) - 1) * self::PART_SIZE, self::PART_SIZE), $requested);
        self::assertSame(self::size(), $file['size']);
    }

    public function testResumingAFileUpload(): void
    {
        $deferred = new DeferredCancellation;
        try {
            self::$MadelineProto->upload(new LocalFile(self::$path), cb: static function (float $progress) use ($deferred): void {
                if ($progress >= 40) {
                    $deferred->cancel();
                }
            }, cancellation: $deferred->getCancellation());
            self::fail('The upload was not interrupted');
        } catch (CancelledException) {
        }
        $file = self::$MadelineProto->upload(new LocalFile(self::$path));
        self::assertSame(self::$data, self::download(self::uploadMedia($file)));
    }

    public function testResumingAStreamUpload(): void
    {
        $deferred = new DeferredCancellation;
        try {
            self::$MadelineProto->upload(self::$MadelineProto->downloadToReturnedStream(self::$media), cb: static function (float $progress) use ($deferred): void {
                if ($progress >= 40) {
                    $deferred->cancel();
                }
            }, cancellation: $deferred->getCancellation());
            self::fail('The upload was not interrupted');
        } catch (CancelledException) {
        }
        $file = self::$MadelineProto->upload(self::$MadelineProto->downloadToReturnedStream(self::$media));
        self::assertSame(self::$data, self::download(self::uploadMedia($file)));
    }
}
