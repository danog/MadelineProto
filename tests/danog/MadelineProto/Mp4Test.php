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
use danog\MadelineProto\Mp4;
use PHPUnit\Framework\TestCase;

/**
 * The MP4 demuxer: progressive files with the moov first or (like the chunks of an RTMP livestream)
 * last, and fragmented files; presentation timestamps with B-frames.
 */
final class Mp4Test extends TestCase
{
    /**
     * @return iterable<string, array{list<string>, 1?: bool}>
     */
    public static function layouts(): iterable
    {
        yield 'moov first' => [['-movflags', '+faststart']];
        yield 'moov last' => [[]];
        yield 'fragmented' => [['-movflags', '+frag_keyframe+empty_moov+default_base_moof']];
        // Negative composition offsets in version 0 boxes, where they're unsigned according to the spec,
        // as Telegram's clients write them.
        yield 'negative offsets' => [['-movflags', '+faststart+negative_cts_offsets'], true];
    }

    /**
     * @dataProvider layouts
     *
     * @param list<string> $flags
     * @param bool $version0 Whether to rewrite the version of the boxes of the composition offsets to 0.
     */
    public function testEveryFrameIsDemuxedWithItsPresentationTimestamp(array $flags, bool $version0 = false): void
    {
        exec('ffmpeg -hide_banner -encoders 2>/dev/null', $encoders, $code);
        if ($code !== 0 || !str_contains(implode("\n", $encoders), 'libx264')) {
            self::markTestSkipped('ffmpeg with libx264 is needed');
        }
        $file = tempnam(sys_get_temp_dir(), 'mp4').'.mp4';
        try {
            exec(implode(' ', array_map(escapeshellarg(...), [
                'ffmpeg', '-hide_banner', '-loglevel', 'error', '-y',
                '-f', 'lavfi', '-i', 'testsrc2=size=160x120:rate=25', '-f', 'lavfi', '-i', 'sine=sample_rate=48000',
                '-t', '2', '-c:v', 'libx264', '-bf', '2', '-g', '25', '-pix_fmt', 'yuv420p', '-c:a', 'aac',
                ...$flags, $file,
            ])), $output, $code);
            self::assertSame(0, $code, implode("\n", $output));
            $data = (string) file_get_contents($file);
            if ($version0) {
                $boxes = 0;
                foreach (['ctts', 'trun'] as $type) {
                    for ($pos = strpos($data, $type); $pos !== false; $pos = strpos($data, $type, $pos + 1)) {
                        if ($data[$pos + 4] === "\1") {
                            $data[$pos + 4] = "\0";
                            $boxes++;
                        }
                    }
                }
                self::assertGreaterThan(0, $boxes, 'No version 1 box of composition offsets');
                file_put_contents($file, $data);
            }

            $mp4 = new Mp4(new ReadableBuffer($data));
            $video = [];
            $audio = 0;
            foreach ($mp4->frames as $frame) {
                if ($frame['codec'] === 'V_MPEG4/ISO/AVC') {
                    $video[] = $frame['timestamp'];
                } elseif ($frame['codec'] === 'A_AAC') {
                    $audio++;
                }
            }
            $probe = json_decode((string) shell_exec('ffprobe -v error -show_entries packet=codec_type,pts_time -of json '.escapeshellarg($file)), true);
            $expectedVideo = [];
            $expectedAudio = 0;
            foreach ($probe['packets'] as $packet) {
                if ($packet['codec_type'] === 'video') {
                    $expectedVideo[] = (int) floor((float) $packet['pts_time'] * 1000 + 0.5);
                } else {
                    $expectedAudio++;
                }
            }
            self::assertSame($expectedAudio, $audio);
            self::assertCount(\count($expectedVideo), $video);
            // In decoding order: the B-frames are presented before the frames they follow.
            $presentationOrder = $video;
            sort($presentationOrder);
            self::assertNotSame($presentationOrder, $video);
            foreach ($video as $k => $timestamp) {
                self::assertEqualsWithDelta($expectedVideo[$k], $timestamp, 1, "The presentation timestamp of video frame $k");
            }
        } finally {
            @unlink($file);
        }
    }

    /**
     * Interleaved audio and video make a sample-to-chunk table about as long as the chunk list: it used to be rescanned
     * for each chunk, blocking the event loop for minutes before the first frame of a film.
     */
    public function testLongSampleToChunkTableIsParsedInLinearTime(): void
    {
        exec('ffmpeg -hide_banner -encoders 2>/dev/null', $encoders, $code);
        if ($code !== 0 || !str_contains(implode("\n", $encoders), 'libx264')) {
            self::markTestSkipped('ffmpeg with libx264 is needed');
        }
        $file = tempnam(sys_get_temp_dir(), 'mp4').'.mp4';
        try {
            exec(implode(' ', array_map(escapeshellarg(...), [
                'ffmpeg', '-hide_banner', '-loglevel', 'error', '-y',
                '-f', 'lavfi', '-i', 'testsrc2=size=64x48:rate=25', '-f', 'lavfi', '-i', 'sine=sample_rate=48000',
                '-t', '1800', '-c:v', 'libx264', '-preset', 'ultrafast', '-g', '250', '-pix_fmt', 'yuv420p',
                '-c:a', 'aac', '-b:a', '16k', '-movflags', '+faststart', $file,
            ])), $output, $code);
            self::assertSame(0, $code, implode("\n", $output));
            $data = (string) file_get_contents($file);
            $pos = strpos($data, 'stsc');
            $entries = 0;
            for (; $pos !== false; $pos = strpos($data, 'stsc', $pos + 1)) {
                $entries = max($entries, unpack('N', $data, $pos + 8)[1]);
            }
            self::assertGreaterThan(10000, $entries, 'The sample-to-chunk table is too short to tell');

            $start = hrtime(true);
            $mp4 = new Mp4(new ReadableBuffer($data));
            $video = 0;
            foreach ($mp4->frames as $frame) {
                if ($video === 0) {
                    // Quadratic, this took about 20 seconds.
                    self::assertLessThan(2_000_000_000, hrtime(true) - $start, 'The sample tables took too long to parse');
                }
                if ($frame['codec'] === 'V_MPEG4/ISO/AVC') {
                    $video++;
                }
            }
            self::assertSame(1800 * 25, $video);
        } finally {
            @unlink($file);
        }
    }
}
