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

use Amp\ByteStream\ReadableBuffer;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Amp\Ipc\Sync\ChannelException;
use Amp\TimeoutCancellation;
use danog\DialogId\DialogId;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\Settings\Files as FilesSettings;
use danog\MadelineProto\UploadResumeException;

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
    use ServesFiles;

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
        // Progress callbacks cross the IPC connection asynchronously.
        for ($x = 0; $x < 50 && $progress !== 100.0; $x++) {
            delay(0.1);
        }
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

    public function testAMethodCallWithACancellation(): void
    {
        $deferred = new DeferredCancellation;
        self::assertSame('config', self::$MadelineProto->help->getConfig(cancellation: $deferred->getCancellation())['_']);
    }

    public function testACallWithAnAlreadyCancelledCancellationIsNotStarted(): void
    {
        $deferred = new DeferredCancellation;
        $deferred->cancel();
        $offset = self::logSize();
        try {
            self::$MadelineProto->upload(new LocalFile(self::$path), 'cancelled.bin', cancellation: $deferred->getCancellation());
            self::fail('The upload was not cancelled');
        } catch (CancelledException) {
        }
        try {
            self::$MadelineProto->help->getConfig(cancellation: $deferred->getCancellation());
            self::fail('The call was not cancelled');
        } catch (CancelledException) {
        }
        self::assertStringNotContainsString('Upload status', self::logSince($offset));
    }

    public function testAPlainStreamCantBePlayedOnHoldThroughIpc(): void
    {
        $this->expectExceptionMessage('Only files, URLs and resumable streams can be played on hold through the IPC server');
        self::$MadelineProto->callPlayOnHold(0, MediaDestination::Camera, new ReadableBuffer('data'));
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
     * The cancellation crosses the IPC connection asynchronously: if the last parts were uploaded in the meantime,
     * the upload completes, and is tried again.
     *
     * @param callable(callable, \Amp\Cancellation): mixed $upload
     */
    private static function interrupt(callable $upload): void
    {
        for ($x = 0; $x < 5; $x++) {
            $deferred = new DeferredCancellation;
            try {
                $upload(static function (float $progress) use ($deferred): void {
                    if ($progress >= 50) {
                        $deferred->cancel();
                    }
                }, $deferred->getCancellation());
            } catch (CancelledException) {
                return;
            }
        }
        self::fail('The upload was not interrupted');
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

    public static function telegramFiles(): array
    {
        $files = [];
        foreach (['MessageMedia' => 'array', 'Media object' => 'media', 'bot API file ID' => 'botApi'] as $name => $type) {
            $files[$name] = [$type, false];
            $files["$name, encrypted"] = [$type, true];
        }
        return $files;
    }

    /**
     * Telegram files are uploaded again downloading them in parallel, or as a resumable stream when resuming.
     *
     * @dataProvider telegramFiles
     */
    public function testResumingATelegramFileUpload(string $type, bool $encrypted): void
    {
        $file = match ($type) {
            'array' => self::$media,
            'media' => self::$MadelineProto->wrapMedia(self::$media),
            'botApi' => new BotApiFileId(self::$MadelineProto->wrapMedia(self::$media)->botApiFileId, self::size(), 'test.bin', false),
        };
        self::interrupt(static fn (callable $cb, $cancellation) => self::$MadelineProto->upload($file, cb: $cb, encrypted: $encrypted, cancellation: $cancellation));
        $offset = self::logSize();
        $uploaded = self::$MadelineProto->upload($file, encrypted: $encrypted);

        self::assertUploadResumed(self::logSince($offset), 'file');
        if ($encrypted) {
            self::assertSame(self::size(), $uploaded['size']);
        } else {
            self::assertSame(self::$data, self::download(self::uploadMedia($uploaded)));
        }
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

    /**
     * Assert that resuming an upload fails because its source changed.
     */
    private static function assertResumeFails(callable $upload, string $fileName): void
    {
        $offset = self::logSize();
        try {
            $upload();
            self::fail('The upload of a changed file was resumed');
        } catch (UploadResumeException $e) {
            self::assertStringContainsString('Could not resume the upload', $e->getMessage());
        }
        self::assertUploadResumed(self::logSince($offset), $fileName);
    }

    /**
     * Assert that an upload started from scratch.
     */
    private static function assertUploadNotResumed(string $log, string $fileName): void
    {
        self::assertStringNotContainsString('Resuming upload of '.$fileName.':', $log, 'The upload was resumed');
    }

    /** Where {@see self::serve()} stalls the first download of the file: after four parts. */
    private const STALL_AT = 4 * 512 * 1024;

    /**
     * Interrupt an upload from a URL served by {@see self::serve()} at the stall, so that exactly the first four parts
     * are uploaded, then let the server send the rest of the file.
     *
     * @param callable(callable, \Amp\Cancellation): mixed $upload
     */
    private static function interruptAtTheStall(callable $upload, DeferredFuture $release): void
    {
        // The fourth part brings the progress of the seven parts above 50%.
        self::interrupt($upload);
        $release->complete();
    }

    public static function rangeSupport(): array
    {
        return [
            'without ranges' => [false, true],
            'with ranges' => [true, true],
            'with ignored ranges' => [true, false],
        ];
    }

    /**
     * @dataProvider rangeSupport
     */
    public function testResumingAUrlUpload(bool $ranges, bool $honorRanges): void
    {
        $content = self::$data;
        $requests = [];
        $release = new DeferredFuture;
        [$server, $url] = self::serve($content, $ranges, $honorRanges, $requests, $release, self::STALL_AT);
        try {
            self::interruptAtTheStall(static fn (callable $cb, $cancellation) => self::$MadelineProto->upload(new RemoteUrl($url), 'url.bin', $cb, cancellation: $cancellation), $release);
            $requests = [];
            $offset = self::logSize();
            $file = self::$MadelineProto->upload(new RemoteUrl($url), 'url.bin');

            self::assertUploadResumed(self::logSince($offset), 'url.bin');
            self::assertSame(self::$data, self::download(self::uploadMedia($file)));
            // Reopened at the start of the last uploaded part, which is read again to check it.
            self::assertSame($ranges ? [null, 'bytes='.(3 * 512 * 1024).'-'] : [null], $requests);
        } finally {
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }

    public function testResumingAnEncryptedUrlUpload(): void
    {
        $content = self::$data;
        $requests = [];
        $release = new DeferredFuture;
        [$server, $url] = self::serve($content, true, true, $requests, $release, self::STALL_AT);
        try {
            self::interruptAtTheStall(static fn (callable $cb, $cancellation) => self::$MadelineProto->upload(new RemoteUrl($url), 'encrypted-url.bin', $cb, true, $cancellation), $release);
            $requests = [];
            $offset = self::logSize();
            $file = self::$MadelineProto->upload(new RemoteUrl($url), 'encrypted-url.bin', encrypted: true);

            self::assertUploadResumed(self::logSince($offset), 'encrypted-url.bin');
            self::assertSame(self::size(), $file['size']);
            // Encryption resumes from the state saved after the fourth part.
            self::assertSame([null, 'bytes='.(3 * 512 * 1024).'-'], $requests);
        } finally {
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }

    /**
     * @dataProvider rangeSupport
     */
    public function testAChangedUrlIsNotResumed(bool $ranges, bool $honorRanges): void
    {
        $content = self::$data;
        $requests = [];
        $release = new DeferredFuture;
        [$server, $url] = self::serve($content, $ranges, $honorRanges, $requests, $release, self::STALL_AT);
        try {
            self::interruptAtTheStall(static fn (callable $cb, $cancellation) => self::$MadelineProto->upload(new RemoteUrl($url), 'changed-url.bin', $cb, cancellation: $cancellation), $release);
            $content = self::change(self::$data);
            self::assertResumeFails(static fn () => self::$MadelineProto->upload(new RemoteUrl($url), 'changed-url.bin'), 'changed-url.bin');

            // The resume state was dropped.
            $offset = self::logSize();
            $file = self::$MadelineProto->upload(new RemoteUrl($url), 'changed-url.bin');
            self::assertUploadNotResumed(self::logSince($offset), 'changed-url.bin');
            self::assertSame($content, self::download(self::uploadMedia($file)));
        } finally {
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }

    public static function fileUploads(): array
    {
        return [
            // Read in any order: all uploaded parts are checked.
            'plain' => [false],
            // Read sequentially from the last saved encryption state: the parts that are read again are checked.
            'encrypted' => [true],
        ];
    }

    /**
     * A file changed without changing its size and modification time.
     *
     * @dataProvider fileUploads
     */
    public function testAChangedFileIsNotResumed(bool $encrypted): void
    {
        $path = tempnam(sys_get_temp_dir(), 'madeline-changed-');
        file_put_contents($path, self::$data);
        $name = basename($path);
        $upload = static fn (?callable $cb = null, $cancellation = null) => self::$MadelineProto->upload(new LocalFile($path), $name, $cb, $encrypted, $cancellation);
        try {
            self::interrupt($upload);
            clearstatcache();
            $mtime = filemtime($path);
            $changed = self::change(self::$data);
            file_put_contents($path, $changed);
            touch($path, $mtime);
            self::assertResumeFails($upload, $name);

            // The resume state was dropped.
            $offset = self::logSize();
            $file = $upload();
            self::assertUploadNotResumed(self::logSince($offset), $name);
            if ($encrypted) {
                self::assertSame(self::size(), $file['size']);
            } else {
                self::assertSame($changed, self::download(self::uploadMedia($file)));
            }
        } finally {
            @unlink($path);
        }
    }

    /**
     * Start sending a document served by {@see self::serve()}, stalled after four parts, and restart the IPC server
     * once they are uploaded.
     *
     * Without a callback, the IPC client sends the call again once it reconnects.
     *
     * @param ?\Closure(): void $beforeRestart Called right before the restart
     *
     * @return array{int, \Amp\Future} Size of the log at the restart, and the result of the send
     */
    private static function sendAndRestart(string $url, string $caption, ?\Closure $beforeRestart = null, bool $withCallback = true): array
    {
        $progress = 0.0;
        // Not inside the arrow function, which would capture $progress by value.
        $callback = $withCallback ? static function (float $p) use (&$progress): void {
            $progress = $p;
        } : null;
        $offset = self::logSize();
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
            if (!$withCallback && preg_match('/Upload status: 57\./', self::logSince($offset))) {
                // Logged by the IPC server when there's no callback.
                $progress = 57.0;
            }
        }
        if ($send->isComplete()) {
            // Surface the error, if any.
            $send->await();
            self::fail('The send completed before it could be interrupted');
        }
        self::assertGreaterThanOrEqual(57, $progress, 'The upload did not reach the stall');
        if ($beforeRestart !== null) {
            $beforeRestart();
        }

        // Restart the IPC server: it saves the session and exits, then the client starts it again.
        $offset = self::logSize();
        self::$MadelineProto->restart();
        return [$offset, $send];
    }

    /**
     * Get a message sent to DEST.
     */
    private static function getMessage(int $chat, int $id): ?array
    {
        $message = (DialogId::isSupergroupOrChannel($chat)
            ? self::$MadelineProto->channels->getMessages(channel: $chat, id: [$id])
            : self::$MadelineProto->messages->getMessages(id: [$id]))['messages'][0] ?? null;
        return $message === null || $message['_'] === 'messageEmpty' ? null : $message;
    }

    /**
     * Wait until the log contains a line matching the specified regex.
     *
     * @return ?array<string> The matches
     */
    private static function waitForLog(int $offset, string $regex): ?array
    {
        for ($x = 0; $x < 1200; $x++) {
            if (preg_match($regex, self::logSince($offset), $matches)) {
                return $matches;
            }
            delay(0.1);
        }
        return null;
    }

    public function testAnInterruptedSendIsResumedAfterARestart(): void
    {
        $content = self::$data;
        $release = new DeferredFuture;
        [$server, $url] = self::serve($content, release: $release, stallAt: self::STALL_AT);
        try {
            $caption = 'Resumed send '.bin2hex(random_bytes(8));
            [$offset, $send] = self::sendAndRestart($url, $caption);
            $release->complete();
            // The callback can't be called by the new IPC server: the call fails on the client, but is resumed by the server.
            try {
                $send->await(new TimeoutCancellation(60));
                self::fail('The send with a callback was sent again to the new IPC server');
            } catch (ChannelException) {
            }

            $matches = self::waitForLog($offset, '/Resumed interrupted sendMedia call \(message (\d+) in chat (-?\d+)\)/');
            self::assertNotNull($matches, 'The send was not resumed');
            self::assertUploadResumed(self::logSince($offset), 'resumed.bin');
            $id = (int) $matches[1];
            $chat = (int) $matches[2];

            $message = self::getMessage($chat, $id);
            self::assertNotNull($message);
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

    public function testAnInterruptedSendWithoutCallbacksReturnsAfterARestart(): void
    {
        $content = self::$data;
        $release = new DeferredFuture;
        [$server, $url] = self::serve($content, release: $release, stallAt: self::STALL_AT);
        try {
            $caption = 'Resumed send without callbacks '.bin2hex(random_bytes(8));
            [$offset, $send] = self::sendAndRestart($url, $caption, withCallback: false);
            $release->complete();

            // Sent again by the client, and resumed by the server: the message is sent once, and returned to the client.
            $message = $send->await(new TimeoutCancellation(120));
            self::assertInstanceOf(Message::class, $message);
            self::assertSame($caption, $message->message);
            self::assertUploadResumed(self::logSince($offset), 'resumed.bin');
            $raw = self::getMessage($message->chatId, $message->id);
            self::assertNotNull($raw);
            self::assertSame(self::$data, self::download($raw['media']));
            foreach ([-2, -1, 1, 2] as $delta) {
                self::assertNotSame($caption, self::getMessage($message->chatId, $message->id + $delta)['message'] ?? null, 'The message was sent twice');
            }
        } finally {
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }

    public function testInterruptedSendsAreNotResumedWhenDisabled(): void
    {
        $content = self::$data;
        $release = new DeferredFuture;
        [$server, $url] = self::serve($content, release: $release, stallAt: self::STALL_AT);
        try {
            // Saved while enabled, then disabled before the restart.
            [$offset] = self::sendAndRestart($url, 'Not resumed send '.bin2hex(random_bytes(8)), static function (): void {
                self::$MadelineProto->updateSettings((new FilesSettings)->setResumeInterruptedCalls(false));
            });
            $release->complete();

            self::assertNotNull(
                self::waitForLog($offset, '/Not resuming interrupted sendMedia call, resuming interrupted calls is disabled/'),
                'The interrupted send was not dropped'
            );
            self::assertStringNotContainsString('Resuming interrupted sendMedia call', self::logSince($offset));
        } finally {
            self::$MadelineProto->updateSettings((new FilesSettings)->setResumeInterruptedCalls(true));
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }

    public function testAChangedFileIsNotSentAfterARestart(): void
    {
        $content = self::$data;
        $release = new DeferredFuture;
        [$server, $url] = self::serve($content, release: $release, stallAt: self::STALL_AT);
        try {
            // Changed before the restart, so that the resumed send can only see the new contents.
            [$offset] = self::sendAndRestart($url, 'Changed send '.bin2hex(random_bytes(8)), static function () use (&$content): void {
                $content = self::change(self::$data);
            });
            $release->complete();

            self::assertNotNull(
                self::waitForLog($offset, '/Reporting: Could not resume interrupted sendMedia call, the file was not sent: .*Could not resume the upload of resumed\.bin/'),
                'The failure was not reported'
            );
            $log = self::logSince($offset);
            self::assertUploadResumed($log, 'resumed.bin');
            self::assertStringNotContainsString('Resumed interrupted sendMedia call', $log);
        } finally {
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }
}
