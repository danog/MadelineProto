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

use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\OpusPlaybackTrack;
use danog\MadelineProto\Tgcalls\VideoPlaybackTrack;
use PHPUnit\Framework\TestCase;
use Webrtc\AVCodec\Frame\FrameInterface;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrackSink;

use function Amp\delay;

/**
 * A WebM file looped on hold: both tracks must carry on across every file boundary, without a gap
 * nor rewinding their RTP clock, however the boundary falls relative to the frames they hold.
 */
final class PlaybackLoopTest extends TestCase
{
    private const VIDEO_FRAMES = 15;      // 33ms apart: the video ends at 462ms
    private const AUDIO_FRAMES = 26;      // 20ms apart: the audio ends at 500ms, after the video
    private const LOOP_MS = 520;
    private const RUN_SECONDS = 6.0;

    private static function element(string $id, string $payload): string
    {
        return $id.pack('N', \strlen($payload) | (1 << 28)).$payload;
    }

    /** A Matroska file with a VP8 and an OPUS track, whose audio outlasts its video. */
    private static function buildFile(int $videoFrames = self::VIDEO_FRAMES, int $audioFrames = self::AUDIO_FRAMES): string
    {
        $video = self::element("\xD7", "\x01").self::element("\x83", "\x01").self::element("\x86", 'V_VP8');
        $audio = self::element("\xD7", "\x02").self::element("\x83", "\x02").self::element("\x86", 'A_OPUS');
        $tracks = self::element("\x16\x54\xAE\x6B", self::element("\xAE", $video).self::element("\xAE", $audio));
        $info = self::element("\x15\x49\xA9\x66", self::element("\x2A\xD7\xB1", "\x0F\x42\x40"));
        $blocks = [];
        for ($x = 0; $x < $videoFrames; $x++) {
            $blocks[] = [$x * 33, self::element("\xA3", "\x81".pack('n', $x * 33)."\x80"."VIDEO$x")];
        }
        for ($x = 0; $x < $audioFrames; $x++) {
            $blocks[] = [$x * 20, self::element("\xA3", "\x82".pack('n', $x * 20)."\x80"."AUDIO$x")];
        }
        usort($blocks, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
        $cluster = self::element("\xE7", pack('n', 0)).implode('', array_column($blocks, 1));
        return self::element("\x18\x53\x80\x67", $info.$tracks.self::element("\x1F\x43\xB6\x75", $cluster));
    }

    /**
     * Collect the packets a track emits, like an RTP sender would.
     */
    private static function collect(MediaStreamTrack $track): PlaybackLoopTestSink
    {
        $sink = new PlaybackLoopTestSink;
        $track->setSink($sink);
        return $sink;
    }

    /** @param list<int> $timestamps */
    private static function assertAdvancing(array $timestamps, string $what): void
    {
        foreach ($timestamps as $i => $timestamp) {
            if ($i > 0) {
                self::assertGreaterThan($timestamps[$i - 1], $timestamp, "The $what RTP clock went back (or stood still) at packet $i of ".\count($timestamps));
            }
        }
    }

    public function testBothTracksCarryOnAcrossLoopedFiles(): void
    {
        // MadelineProto's own file driver: amphp's default one opens every file through a worker
        // process, adding a delay between the files that would be counted as missing frames.
        Magic::start(light: true);
        $path = tempnam(sys_get_temp_dir(), 'loop').'.webm';
        file_put_contents($path, self::buildFile());
        try {
            $call = new PlaybackLoopTestCall;
            $dj = new DjLoop($call);
            $audio = new OpusPlaybackTrack($dj, $call);
            $video = new VideoPlaybackTrack($dj, $call);
            $audioTimestamps = self::collect($audio);
            $videoTimestamps = self::collect($video);
            $dj->playOnHold(new LocalFile($path));

            delay(self::RUN_SECONDS);
            $call->ended = true;
            $audio->stop();
            $video->stop();
            $dj->discard();

            $audioTimestamps = $audioTimestamps->timestamps();
            $videoTimestamps = $videoTimestamps->timestamps();
        } finally {
            unlink($path);
        }
        $loops = self::RUN_SECONDS * 1000 / self::LOOP_MS;

        self::assertAdvancing($audioTimestamps, 'audio');
        self::assertAdvancing($videoTimestamps, 'video');
        // Every loop plays all of its frames, at their pace: a file played on a stale clock is sent
        // in a burst (and then nothing), a stalled track sends nothing for a whole loop.
        self::assertEqualsWithDelta($loops * self::AUDIO_FRAMES, \count($audioTimestamps), 2 * self::AUDIO_FRAMES, 'audio packets');
        self::assertEqualsWithDelta($loops * self::VIDEO_FRAMES, \count($videoTimestamps), 2 * self::VIDEO_FRAMES, 'video packets');
    }

    public function testATrackKeepsUpWithoutLookahead(): void
    {
        Magic::start(light: true);
        $path = tempnam(sys_get_temp_dir(), 'pace').'.webm';
        // 3 seconds.
        file_put_contents($path, self::buildFile(90, 150));
        try {
            // Each frame is read only once the previous one of its track was played.
            $call = new PlaybackLoopTestCall(lookahead: 0);
            $dj = new DjLoop($call);
            $audio = new OpusPlaybackTrack($dj, $call);
            $video = new VideoPlaybackTrack($dj, $call);
            $audioLateness = self::collect($audio);
            $videoLateness = self::collect($video);
            $dj->play(new LocalFile($path));

            delay(3.5);
            $call->ended = true;
            $audio->stop();
            $video->stop();
            $dj->discard();

            $audioLateness = $audioLateness->lateness(DjLoop::AUDIO_CLOCK_RATE);
            $videoLateness = $videoLateness->lateness(DjLoop::VIDEO_CLOCK_RATE);
        } finally {
            unlink($path);
        }
        self::assertCount(150, $audioLateness, 'audio packets');
        self::assertCount(90, $videoLateness, 'video packets');
        // A track that has to wait for the reader to read its next frame, after it was due, falls
        // behind a bit more with every frame: by the end of the file, it was seconds behind.
        self::assertLessThan(0.2, max($audioLateness), 'how late the audio is played, at most');
        self::assertLessThan(0.2, max($videoLateness), 'how late the video is played, at most');
    }

    public function testATrackConsumedLateDoesNotBurst(): void
    {
        Magic::start(light: true);
        $path = tempnam(sys_get_temp_dir(), 'late').'.webm';
        file_put_contents($path, self::buildFile(90, 150));
        try {
            $call = new PlaybackLoopTestCall(lookahead: 0);
            $dj = new DjLoop($call);
            $audio = new OpusPlaybackTrack($dj, $call);
            $video = new VideoPlaybackTrack($dj, $call);
            $dj->play(new LocalFile($path));
            // As when the call connects, a while after the tracks started playing.
            delay(1.5);
            $start = hrtime(true) / 1e9;
            $audioLateness = self::collect($audio);
            $videoLateness = self::collect($video);

            delay(1.0);
            $call->ended = true;
            $audio->stop();
            $video->stop();
            $dj->discard();

            $audioLateness = $audioLateness->lateness(DjLoop::AUDIO_CLOCK_RATE);
            $videoLateness = $videoLateness->lateness(DjLoop::VIDEO_CLOCK_RATE);
        } finally {
            unlink($path);
        }
        // The frames that were due while nobody took them are not sent all at once, but at their pace:
        // about a second of frames in a second.
        self::assertLessThan(60, \count($audioLateness), 'audio packets in the first second');
        self::assertLessThan(36, \count($videoLateness), 'video packets in the first second');
    }

    /**
     * Wait for the DJ to play a file (null for nothing), for less than a file lasts.
     */
    private static function assertPlaying(DjLoop $dj, ?string $path, string $message): void
    {
        for ($x = 0; $x < 25; $x++) {
            $current = $dj->getCurrent();
            $current = $current instanceof LocalFile ? $current->file : $current;
            if ($current === $path) {
                break;
            }
            delay(0.01);
        }
        self::assertSame($path, $current, $message);
    }

    public function testNewHoldFilesReplaceThePlayingOne(): void
    {
        Magic::start(light: true);
        $paths = [];
        foreach (['a', 'b', 'c'] as $name) {
            $paths[$name] = tempnam(sys_get_temp_dir(), 'hold').'.webm';
            file_put_contents($paths[$name], self::buildFile());
        }
        try {
            // No lookahead: the files are read as they are played.
            $call = new PlaybackLoopTestCall(lookahead: 0);
            $dj = new DjLoop($call);
            $audio = new OpusPlaybackTrack($dj, $call);
            $video = new VideoPlaybackTrack($dj, $call);
            $audioTimestamps = self::collect($audio);
            $videoTimestamps = self::collect($video);

            $dj->playOnHold(new LocalFile($paths['a']));
            self::assertPlaying($dj, $paths['a'], 'The hold file should play');
            $dj->playOnHold(new LocalFile($paths['b']));
            self::assertPlaying($dj, $paths['b'], 'The new hold file should replace the playing one');

            $dj->play(new LocalFile($paths['c']));
            self::assertPlaying($dj, $paths['c'], 'The playlist should interrupt the hold file');
            $dj->playOnHold(new LocalFile($paths['a']));
            delay(0.1);
            self::assertPlaying($dj, $paths['c'], 'New hold files should not interrupt the playlist');
            $dj->skip();
            self::assertPlaying($dj, $paths['a'], 'The new hold file should play once the playlist is over');

            $dj->playOnHold();
            self::assertPlaying($dj, null, 'Removing the hold files should stop the playing one');

            $call->ended = true;
            $audio->stop();
            $video->stop();
            $dj->discard();
        } finally {
            array_map(unlink(...), $paths);
        }
    }
}

/**
 * @internal
 */
final class PlaybackLoopTestCall implements CallControllerInterface
{
    public bool $ended = false;

    public function __construct(private readonly int $lookahead = 2000)
    {
    }

    public function log(string $message, int $level = Logger::NOTICE): void
    {
    }

    public function isCallEnded(): bool
    {
        return $this->ended;
    }

    public function getVoIPSettings(): \danog\MadelineProto\Settings\VoIP
    {
        // Read ahead by default, so that frames are queued.
        return (new \danog\MadelineProto\Settings\VoIP)->setPlaybackLookahead($this->lookahead);
    }

    public function __toString(): string
    {
        return 'test call';
    }
}

/**
 * Takes the packets of a track, like an RTP sender would.
 *
 * @internal
 */
final class PlaybackLoopTestSink implements MediaStreamTrackSink
{
    /** @var list<array{float, int}> When each packet was emitted, and its RTP timestamp. */
    private array $packets = [];

    public function onFrame(FrameInterface|EncodedPacket $frame): bool
    {
        \assert($frame instanceof EncodedPacket);
        $this->packets[] = [hrtime(true) / 1e9, $frame->getTimestamp()];
        return true;
    }

    /** @return list<int> */
    public function timestamps(): array
    {
        return array_column($this->packets, 1);
    }

    /**
     * How late each packet was emitted: when, minus when it was due according to its timestamp,
     * relative to the first one, in seconds.
     *
     * @return list<float>
     */
    public function lateness(int $clockRate): array
    {
        $lateness = [];
        $first = $this->packets[0] ?? null;
        foreach ($this->packets as [$time, $timestamp]) {
            \assert($first !== null);
            $lateness[] = $time - $first[0] - ($timestamp - $first[1]) / $clockRate;
        }
        return $lateness;
    }
}
