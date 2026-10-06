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

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\ReadableStreamIteratorAggregate;
use Amp\Cancellation;
use Amp\DeferredFuture;
use Closure;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

/**
 * Playback of resumable streams across a serialize/unserialize cycle of the call (a restart).
 */
final class ResumablePlaybackTest extends TestCase
{
    private const FRAMES = 10;

    /**
     * Encode an EBML element: its ID (already including the length marker) and its payload.
     */
    private static function element(string $id, string $payload): string
    {
        return $id.pack('N', \strlen($payload) | (1 << 28)).$payload;
    }

    /**
     * Build a Matroska file with one VP8 track and one keyframe per cluster, a second apart.
     */
    private static function buildFile(): string
    {
        $entry = self::element("\xD7", "\x01")            // TrackNumber = 1
            .self::element("\x83", "\x01")                // TrackType = video
            .self::element("\x86", 'V_VP8');              // CodecID
        $tracks = self::element("\x16\x54\xAE\x6B", self::element("\xAE", $entry));
        $info = self::element("\x15\x49\xA9\x66", self::element("\x2A\xD7\xB1", "\x0F\x42\x40"));
        $clusters = '';
        for ($x = 0; $x < self::FRAMES; $x++) {
            $clusters .= self::element(
                "\x1F\x43\xB6\x75",
                self::element("\xE7", pack('n', $x * 1000))
                // SimpleBlock: track vint, 16-bit relative timestamp, flags with the keyframe bit.
                .self::element("\xA3", "\x81".pack('n', 0)."\x80"."FRAME$x")
            );
        }
        return self::element("\x18\x53\x80\x67", $info.$tracks.$clusters);
    }

    /**
     * Build a Matroska file with a single cluster, where each VP8 keyframe (a second apart) is followed by a frame of a
     * subtitle track, which is not transmitted.
     */
    private static function buildSingleClusterFile(): string
    {
        $video = self::element("\xD7", "\x01")            // TrackNumber = 1
            .self::element("\x83", "\x01")                // TrackType = video
            .self::element("\x86", 'V_VP8');               // CodecID
        $subtitles = self::element("\xD7", "\x02")        // TrackNumber = 2
            .self::element("\x83", "\x11")                // TrackType = subtitle
            .self::element("\x86", 'S_TEXT/UTF8');         // CodecID
        $tracks = self::element("\x16\x54\xAE\x6B", self::element("\xAE", $video).self::element("\xAE", $subtitles));
        $info = self::element("\x15\x49\xA9\x66", self::element("\x2A\xD7\xB1", "\x0F\x42\x40"));
        $blocks = self::element("\xE7", pack('n', 0));
        for ($x = 0; $x < self::FRAMES; $x++) {
            $blocks .= self::element("\xA3", "\x81".pack('n', $x * 1000)."\x80"."FRAME$x")
                .self::element("\xA3", "\x82".pack('n', $x * 1000)."\x80"."SUBTITLE$x");
        }
        return self::element("\x18\x53\x80\x67", $info.$tracks.self::element("\x1F\x43\xB6\x75", $blocks));
    }

    /**
     * Take every video frame currently queued.
     *
     * @return list<string>
     */
    private static function pullAll(DjLoop $dj): array
    {
        $frames = [];
        while (($frame = $dj->pullVideo()) !== null) {
            $frames[] = $frame['data'];
        }
        return $frames;
    }

    /**
     * Simulate a restart of the call: the loop is serialized, destroyed and restored.
     */
    private static function restart(DjLoop $dj): DjLoop
    {
        $serialized = serialize($dj);
        $dj->discard();
        $dj = unserialize($serialized);
        self::assertInstanceOf(DjLoop::class, $dj);
        $dj->resumeReader();
        return $dj;
    }

    /**
     * @return list<string>
     */
    private static function expected(): array
    {
        return array_map(static fn (int $x) => "FRAME$x", range(0, self::FRAMES - 1));
    }

    public function testAResumableStreamResumesMidItemAfterARestart(): void
    {
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        // An odd chunk size, so the stream is never positioned on a cluster boundary.
        $dj->play(new ChunkedResumableStream(self::buildFile(), 7));
        // The reader buffers ahead of playback, then waits for the tracks to drain.
        delay(0.1);
        $played = self::pullAll($dj);
        self::assertNotEmpty($played);
        self::assertLessThan(self::FRAMES, \count($played), 'The whole file was read, nothing to resume');

        $dj = self::restart($dj);
        $dj->setPlaybackPosition(PHP_INT_MAX);
        delay(0.1);
        $played = [...$played, ...self::pullAll($dj)];

        self::assertSame(self::expected(), $played);
        $dj->discard();
    }

    public function testQueuedResumableStreamsSurviveARestart(): void
    {
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->play(new ChunkedResumableStream(self::buildFile(), 7));
        $dj->play(new ChunkedResumableStream(self::buildFile(), 7));
        delay(0.1);
        $played = self::pullAll($dj);

        $dj = self::restart($dj);
        $dj->setPlaybackPosition(PHP_INT_MAX);
        delay(0.1);
        $played = [...$played, ...self::pullAll($dj)];
        // The second file's timestamps restart, so the throttle holds its reader until playback moves on.
        $dj->setPlaybackPosition(PHP_INT_MAX);
        delay(0.1);
        $played = [...$played, ...self::pullAll($dj)];

        self::assertSame([...self::expected(), ...self::expected()], $played);
        $dj->discard();
    }

    public function testAResumableHoldFileIsReplayed(): void
    {
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->playOnHold(new ChunkedResumableStream(self::buildFile(), 7));
        $played = [];
        // Each loop of the hold file restarts the playback position, and only starts once the queues were drained.
        for ($x = 0; $x < 10 && \count($played) < 2 * self::FRAMES; $x++) {
            $dj->setPlaybackPosition(PHP_INT_MAX);
            delay(0.15);
            $played = [...$played, ...self::pullAll($dj)];
        }
        $dj->discard();

        self::assertSame([...self::expected(), ...self::expected()], \array_slice($played, 0, 2 * self::FRAMES));
    }

    public function testDroppedFramesAreSkippedWhenResumingMidCluster(): void
    {
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->play(new ChunkedResumableStream(self::buildSingleClusterFile(), 7));
        delay(0.1);
        $played = self::pullAll($dj);
        self::assertNotEmpty($played);
        self::assertLessThan(self::FRAMES, \count($played), 'The whole file was read, nothing to resume');

        $dj = self::restart($dj);
        $dj->setPlaybackPosition(PHP_INT_MAX);
        delay(0.1);
        $played = [...$played, ...self::pullAll($dj)];

        // No frame is played twice.
        self::assertSame(self::expected(), $played);
        $dj->discard();
    }

    public function testResumableHoldFilesSurviveARestartNextToAPlainStream(): void
    {
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->playOnHold(
            new ChunkedResumableStream(self::buildFile(), 7),
            new ReadableBuffer(self::buildFile()),
            new ChunkedResumableStream(self::buildFile(), 7),
        );
        // The plain stream is dropped: the two resumable streams are played in a loop.
        $dj = self::restart($dj);
        $played = [];
        for ($x = 0; $x < 20 && \count($played) < 3 * self::FRAMES; $x++) {
            $dj->setPlaybackPosition(PHP_INT_MAX);
            delay(0.15);
            $played = [...$played, ...self::pullAll($dj)];
        }
        $dj->discard();

        self::assertSame([...self::expected(), ...self::expected(), ...self::expected()], \array_slice($played, 0, 3 * self::FRAMES));
    }

    public function testTheFirstPlayReadsTheQueuedStream(): void
    {
        // Its progress callback and cancellation are not serialized.
        $file = self::buildFile();
        $stream = new ChunkedResumableStream($file, 7);
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->play($stream);
        $played = [];
        for ($x = 0; $x < 10 && \count($played) < self::FRAMES; $x++) {
            $dj->setPlaybackPosition(PHP_INT_MAX);
            delay(0.1);
            $played = [...$played, ...self::pullAll($dj)];
        }
        $dj->discard();

        self::assertSame(self::expected(), $played);
        self::assertSame(\strlen($file), $stream->getPosition());
    }

    public function testASkippedStreamIsClosed(): void
    {
        $stream = new ChunkedResumableStream(self::buildFile(), 7);
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->play($stream);
        delay(0.1);
        self::assertNotEmpty(self::pullAll($dj));
        self::assertFalse($stream->wasClosed());

        $dj->skip();
        delay(0.1);
        self::assertTrue($stream->wasClosed());
        $dj->discard();
    }

    public function testTheLoopCanBeSerializedWhileAPlainStreamIsProbed(): void
    {
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->play(new ChunkedResumableStream(self::buildFile(), 7));
        // Never written to: probing it suspends the reader.
        $pipe = new Pipe(1);
        $dj->play($pipe->getSource());
        $probed = 'stream '.spl_object_id($pipe->getSource());
        for ($x = 0; $x < 20 && $dj->getCurrent() !== $probed; $x++) {
            $dj->setPlaybackPosition(PHP_INT_MAX);
            delay(0.1);
            self::pullAll($dj);
        }
        self::assertSame($probed, $dj->getCurrent());

        // The plain stream is dropped, instead of being serialized as the resumable stream before it.
        $dj = self::restart($dj);
        self::assertNull($dj->getCurrent());
        $dj->discard();
        $pipe->getSink()->close();
    }

    public function testAPlainStreamDoesNotSurviveARestart(): void
    {
        $dj = new DjLoop(new ResumablePlaybackTestCall);
        $dj->play(new ReadableBuffer(self::buildFile()));
        delay(0.1);
        $played = self::pullAll($dj);
        self::assertLessThan(self::FRAMES, \count($played));

        $dj = self::restart($dj);
        $dj->setPlaybackPosition(PHP_INT_MAX);
        delay(0.1);
        // Only the frames that were already queued are played.
        $played = [...$played, ...self::pullAll($dj)];
        self::assertSame(\array_slice(self::expected(), 0, \count($played)), $played);
        self::assertLessThan(self::FRAMES, \count($played));
        self::assertNull($dj->getCurrent());
        $dj->discard();
    }
}

/**
 * @internal
 */
final class ResumablePlaybackTestCall implements CallControllerInterface
{
    public function log(string $message, int $level = Logger::NOTICE): void
    {
    }

    public function isCallEnded(): bool
    {
        return false;
    }

    public function __toString(): string
    {
        return 'test call';
    }
}

/**
 * A resumable stream over a string, returned in fixed-size chunks.
 *
 * @internal
 *
 * @implements IteratorAggregate<int, string>
 */
final class ChunkedResumableStream implements ReadableStream, ResumableStream, IteratorAggregate
{
    use ReadableStreamIteratorAggregate;

    private bool $closed = false;
    private DeferredFuture $onClose;

    public function getPosition(): int
    {
        return $this->position;
    }

    public function wasClosed(): bool
    {
        return $this->closed;
    }

    public function __construct(private string $data, private int $chunkSize, private int $position = 0)
    {
        $this->onClose = new DeferredFuture;
    }

    public function read(?Cancellation $cancellation = null): ?string
    {
        if (!$this->isReadable()) {
            return null;
        }
        $chunk = substr($this->data, $this->position, $this->chunkSize);
        $this->position += \strlen($chunk);
        return $chunk;
    }

    public function isReadable(): bool
    {
        return !$this->closed && $this->position < \strlen($this->data);
    }

    public function close(): void
    {
        $this->closed = true;
        if (!$this->onClose->isComplete()) {
            $this->onClose->complete();
        }
    }

    public function isClosed(): bool
    {
        return !$this->isReadable();
    }

    public function onClose(Closure $onClose): void
    {
        $this->onClose->getFuture()->finally($onClose);
    }

    public function __serialize(): array
    {
        return ['data' => $this->data, 'chunkSize' => $this->chunkSize, 'position' => $this->position];
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data['data'];
        $this->chunkSize = $data['chunkSize'];
        $this->position = $data['position'];
        $this->onClose = new DeferredFuture;
    }
}
