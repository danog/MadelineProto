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

use Amp\ByteStream\ReadableBuffer;
use danog\MadelineProto\Matroska;
use PHPUnit\Framework\TestCase;

/**
 * Tests the pure-PHP Matroska/WebM demuxer used to play video in calls.
 *
 * @internal
 */
final class MatroskaTest extends TestCase
{
    /**
     * Encode an EBML element: its ID (already including the length marker) and its payload.
     */
    private static function element(string $id, string $payload): string
    {
        return $id.self::size(\strlen($payload)).$payload;
    }

    /**
     * Encode an EBML size as a four byte variable length integer.
     */
    private static function size(int $value): string
    {
        return pack('N', $value | (1 << 28));
    }

    /**
     * Build a minimal but well-formed WebM file holding one video and one audio track.
     */
    private static function buildWebm(): string
    {
        $trackVideo = self::element(
            "\xAE", // TrackEntry
            self::element("\xD7", "\x01")            // TrackNumber = 1
            .self::element("\x83", "\x01")           // TrackType = video
            .self::element("\x86", 'V_VP8')          // CodecID
        );
        $trackAudio = self::element(
            "\xAE",
            self::element("\xD7", "\x02")            // TrackNumber = 2
            .self::element("\x83", "\x02")           // TrackType = audio
            .self::element("\x86", 'A_OPUS')
        );
        $tracks = self::element("\x16\x54\xAE\x6B", $trackVideo.$trackAudio);

        // TimestampScale = 1ms, expressed in nanoseconds.
        $info = self::element("\x15\x49\xA9\x66", self::element("\x2A\xD7\xB1", "\x0F\x42\x40"));

        // SimpleBlock: track number (vint), 16-bit relative timestamp, flags, then the frame.
        $block = static fn (int $track, int $relative, int $flags, string $data): string
            => self::element("\xA3", \chr(0x80 | $track).pack('n', $relative).\chr($flags).$data);

        $cluster = self::element(
            "\x1F\x43\xB6\x75",
            self::element("\xE7", "\x00")                       // Cluster timestamp = 0
            .$block(1, 0, 0x80, 'VIDEO-KEYFRAME')               // keyframe flag set
            .$block(2, 0, 0x00, 'AUDIO-ONE')
            .$block(1, 40, 0x00, 'VIDEO-DELTA')
            .$block(2, 20, 0x00, 'AUDIO-TWO')
        );

        return self::element("\x18\x53\x80\x67", $info.$tracks.$cluster); // Segment
    }

    public function testParsesTracksAndFrames(): void
    {
        $matroska = new Matroska(new ReadableBuffer(self::buildWebm()));

        $frames = [];
        foreach ($matroska->frames as $frame) {
            $frames[] = $frame;
        }

        $this->assertSame(
            [
                1 => ['codec' => 'V_VP8', 'type' => Matroska::TRACK_TYPE_VIDEO, 'private' => null],
                2 => ['codec' => 'A_OPUS', 'type' => Matroska::TRACK_TYPE_AUDIO, 'private' => null],
            ],
            $matroska->tracks
        );

        $this->assertCount(4, $frames);
        $this->assertSame(['VIDEO-KEYFRAME', 'AUDIO-ONE', 'VIDEO-DELTA', 'AUDIO-TWO'], array_column($frames, 'data'));
        $this->assertSame(['V_VP8', 'A_OPUS', 'V_VP8', 'A_OPUS'], array_column($frames, 'codec'));
        $this->assertSame([0, 0, 40, 20], array_column($frames, 'timestamp'));
        $this->assertSame([true, false, false, false], array_column($frames, 'keyframe'));
    }

    /**
     * H.264 cannot be decoded without its `CodecPrivate`, so the demuxer has to surface it.
     */
    public function testParsesCodecPrivate(): void
    {
        $record = "\x01\x42\xE0\x1F\xFF\xE1\x00\x04\x67\x42\xE0\x1F\x01\x00\x02\x68\xCE";
        $entry = self::element("\xD7", "\x01")
            .self::element("\x83", "\x01")
            .self::element("\x86", 'V_MPEG4/ISO/AVC')
            .self::element("\x63\xA2", $record);
        $file = self::element(
            "\x18\x53\x80\x67",
            self::element("\x16\x54\xAE\x6B", self::element("\xAE", $entry))
        );

        $matroska = new Matroska(new ReadableBuffer($file));

        $this->assertSame($record, $matroska->tracks[1]['private']);
        $this->assertSame('V_MPEG4/ISO/AVC', $matroska->tracks[1]['codec']);
    }

    public function testHasCodec(): void
    {
        $matroska = new Matroska(new ReadableBuffer(self::buildWebm()));

        $this->assertTrue($matroska->hasCodec('V_VP8'));
        $this->assertTrue($matroska->hasCodec('A_OPUS'));
        $this->assertFalse($matroska->hasCodec('V_VP9'));
    }

    /**
     * A real file produced by ffmpeg must demux to exactly the frames ffprobe reports.
     */
    public function testRealFileMatchesFfprobe(): void
    {
        $ffmpeg = shell_exec('command -v ffmpeg 2>/dev/null');
        $ffprobe = shell_exec('command -v ffprobe 2>/dev/null');
        if (!$ffmpeg || !$ffprobe) {
            $this->markTestSkipped('ffmpeg is only needed to generate the sample, not to play it.');
        }

        $path = sys_get_temp_dir().'/madeline-matroska-test.webm';
        shell_exec(sprintf(
            'ffmpeg -y -f lavfi -i testsrc=size=160x120:rate=10:duration=1 '
            .'-f lavfi -i sine=frequency=440:duration=1 '
            .'-c:v libvpx -deadline realtime -cpu-used 8 -c:a libopus -f webm %s 2>/dev/null',
            escapeshellarg($path)
        ));
        if (!is_file($path) || filesize($path) === 0) {
            $this->markTestSkipped('Could not produce a sample WebM file.');
        }

        $expectedVideo = (int) shell_exec(sprintf(
            'ffprobe -v error -select_streams v -count_packets -show_entries stream=nb_read_packets -of csv=p=0 %s',
            escapeshellarg($path)
        ));
        $expectedAudio = (int) shell_exec(sprintf(
            'ffprobe -v error -select_streams a -count_packets -show_entries stream=nb_read_packets -of csv=p=0 %s',
            escapeshellarg($path)
        ));

        $matroska = new Matroska(new \danog\MadelineProto\LocalFile($path));
        $counts = ['V_VP8' => 0, 'A_OPUS' => 0];
        foreach ($matroska->frames as $frame) {
            if (isset($counts[$frame['codec']])) {
                $counts[$frame['codec']]++;
            }
        }
        @unlink($path);

        $this->assertSame($expectedVideo, $counts['V_VP8'], 'video frame count');
        $this->assertSame($expectedAudio, $counts['A_OPUS'], 'audio frame count');
    }
}
