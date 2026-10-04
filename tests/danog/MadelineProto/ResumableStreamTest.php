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

use Amp\ByteStream\ReadableIterableStream;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use danog\DialogId\DialogId;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\ResumableStream;
use Psr\Log\NullLogger;

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
     *  Resumable uploads and calls.
     * -------------------------------------------------------------------- */

    /** Log of the IPC server, as configured by MadelineTestCase. */
    private const LOG = __DIR__.'/../../MadelineProto.log';

    /**
     * What was logged after the specified offset of the log.
     */
    private static function logSince(int $offset): string
    {
        clearstatcache();
        $log = (string) file_get_contents(self::LOG);
        // The logger truncates the file once it gets too big.
        return \strlen($log) >= $offset ? substr($log, $offset) : $log;
    }

    private static function logSize(): int
    {
        clearstatcache();
        return file_exists(self::LOG) ? (int) filesize(self::LOG) : 0;
    }

    /**
     * Assert that an upload was resumed, with at least one part already uploaded.
     */
    private static function assertUploadResumed(string $log, string $fileName): void
    {
        self::assertMatchesRegularExpression(
            '/Resuming upload of '.preg_quote($fileName, '/').': [1-9]\d* of \d+ parts were already uploaded/',
            $log,
            'The upload was not resumed'
        );
    }

    /**
     * Run an upload, interrupting it (as a restart would) once it's halfway through.
     *
     * @param callable(callable, \Amp\Cancellation): mixed $upload
     */
    private static function interrupt(callable $upload): void
    {
        $deferred = new DeferredCancellation;
        try {
            $upload(static function (float $progress) use ($deferred): void {
                if ($progress >= 50) {
                    $deferred->cancel();
                }
            }, $deferred->getCancellation());
            self::fail('The upload was not interrupted');
        } catch (CancelledException) {
        }
    }

    public function testResumingAFileUpload(): void
    {
        $name = basename(self::$path);
        self::interrupt(static fn (callable $cb, $cancellation) => self::$MadelineProto->upload(new LocalFile(self::$path), cb: $cb, cancellation: $cancellation));
        $offset = self::logSize();
        $file = self::$MadelineProto->upload(new LocalFile(self::$path));

        self::assertUploadResumed(self::logSince($offset), $name);
        self::assertSame(self::$data, self::download(self::uploadMedia($file)));
    }

    public function testResumingAStreamUpload(): void
    {
        // Resumable streams are read sequentially.
        self::interrupt(static fn (callable $cb, $cancellation) => self::$MadelineProto->upload(self::$MadelineProto->downloadToReturnedStream(self::$media), 'stream.bin', $cb, cancellation: $cancellation));
        $offset = self::logSize();
        $file = self::$MadelineProto->upload(self::$MadelineProto->downloadToReturnedStream(self::$media), 'stream.bin');

        self::assertUploadResumed(self::logSince($offset), 'stream.bin');
        self::assertSame(self::$data, self::download(self::uploadMedia($file)));
    }

    public function testResumingAnEncryptedUpload(): void
    {
        self::interrupt(static fn (callable $cb, $cancellation) => self::$MadelineProto->uploadEncrypted(new LocalFile(self::$path), 'encrypted.bin', $cb, $cancellation));
        $offset = self::logSize();
        $file = self::$MadelineProto->uploadEncrypted(new LocalFile(self::$path), 'encrypted.bin');

        // Encrypted files can only be checked by sending them to a secret chat.
        self::assertUploadResumed(self::logSince($offset), 'encrypted.bin');
        self::assertSame(self::size(), $file['size']);
    }

    public function testAnInterruptedSendIsResumedAfterARestart(): void
    {
        // Serve the file from a local server that stalls after the first four parts, until released.
        $stallAt = 4 * 512 * 1024;
        $release = new DeferredFuture;
        $data = self::$data;
        $server = SocketHttpServer::createForDirectAccess(new NullLogger);
        $server->expose('127.0.0.1:0');
        $server->start(new ClosureRequestHandler(static function () use ($data, $stallAt, $release): Response {
            $body = (static function () use ($data, $stallAt, $release): \Generator {
                yield substr($data, 0, $stallAt);
                $release->getFuture()->await();
                yield substr($data, $stallAt);
            })();
            return new Response(HttpStatus::OK, [
                'content-type' => 'application/octet-stream',
                'content-length' => (string) \strlen($data),
                'etag' => '"resumable-test"',
            ], new ReadableIterableStream($body));
        }), new DefaultErrorHandler);
        $url = 'http://'.$server->getServers()[0]->getAddress()->toString().'/resumed.bin';

        try {
            $caption = 'Resumed send '.bin2hex(random_bytes(8));
            $progress = 0.0;
            // Not inside the arrow function, which would capture $progress by value.
            $callback = static function (float $p) use (&$progress): void {
                $progress = $p;
            };
            $send = async(static fn () => self::$MadelineProto->sendDocument(
                peer: getenv('DEST'),
                file: new RemoteUrl($url),
                caption: $caption,
                fileName: 'resumed.bin',
                callback: $callback,
            ));
            $send->ignore();
            // Wait for the four parts before the stall to be uploaded.
            for ($x = 0; $progress < 57 && !$send->isComplete() && $x < 1200; $x++) {
                delay(0.05);
            }
            if ($send->isComplete()) {
                // Surface the error, if any.
                $send->await();
                self::fail('The send completed before it could be interrupted');
            }
            self::assertGreaterThanOrEqual(57, $progress, 'The upload did not reach the stall');

            // Restart the IPC server: it saves the session and exits, then the client starts it again.
            $offset = self::logSize();
            self::$MadelineProto->restart();
            $release->complete();

            $id = $chat = null;
            for ($x = 0; $id === null && $x < 1200; $x++) {
                delay(0.1);
                if (preg_match('/Resumed interrupted sendMedia call \(message (\d+) in chat (-?\d+)\)/', self::logSince($offset), $matches)) {
                    $id = (int) $matches[1];
                    $chat = (int) $matches[2];
                }
            }
            self::assertNotNull($id, 'The send was not resumed');
            self::assertUploadResumed(self::logSince($offset), 'resumed.bin');

            $message = (DialogId::isSupergroupOrChannel($chat)
                ? self::$MadelineProto->channels->getMessages(channel: $chat, id: [$id])
                : self::$MadelineProto->messages->getMessages(id: [$id]))['messages'][0];
            self::assertSame($caption, $message['message']);
            self::assertSame(self::size(), $message['media']['document']['size']);
            self::assertSame(self::$data, self::download($message['media']));
        } finally {
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }
}
