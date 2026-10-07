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
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

use function Amp\async;
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
    private static function buildFile(): string
    {
        $video = self::element("\xD7", "\x01").self::element("\x83", "\x01").self::element("\x86", 'V_VP8');
        $audio = self::element("\xD7", "\x02").self::element("\x83", "\x02").self::element("\x86", 'A_OPUS');
        $tracks = self::element("\x16\x54\xAE\x6B", self::element("\xAE", $video).self::element("\xAE", $audio));
        $info = self::element("\x15\x49\xA9\x66", self::element("\x2A\xD7\xB1", "\x0F\x42\x40"));
        $blocks = [];
        for ($x = 0; $x < self::VIDEO_FRAMES; $x++) {
            $blocks[] = [$x * 33, self::element("\xA3", "\x81".pack('n', $x * 33)."\x80"."VIDEO$x")];
        }
        for ($x = 0; $x < self::AUDIO_FRAMES; $x++) {
            $blocks[] = [$x * 20, self::element("\xA3", "\x82".pack('n', $x * 20)."\x80"."AUDIO$x")];
        }
        usort($blocks, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
        $cluster = self::element("\xE7", pack('n', 0)).implode('', array_column($blocks, 1));
        return self::element("\x18\x53\x80\x67", $info.$tracks.self::element("\x1F\x43\xB6\x75", $cluster));
    }

    /**
     * Collect the RTP timestamps a track emits until it is stopped.
     *
     * @return \Amp\Future<list<int>>
     */
    private static function collect(MediaStreamTrack $track): \Amp\Future
    {
        return async(static function () use ($track): array {
            $timestamps = [];
            foreach ($track->getConsumer() as $packet) {
                if ($packet instanceof EncodedPacket) {
                    $timestamps[] = $packet->getTimestamp();
                }
            }
            return $timestamps;
        });
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

            $audioTimestamps = $audioTimestamps->await();
            $videoTimestamps = $videoTimestamps->await();
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
}

/**
 * @internal
 */
final class PlaybackLoopTestCall implements CallControllerInterface
{
    public bool $ended = false;

    public function log(string $message, int $level = Logger::NOTICE): void
    {
    }

    public function isCallEnded(): bool
    {
        return $this->ended;
    }

    public function __toString(): string
    {
        return 'test call';
    }
}
