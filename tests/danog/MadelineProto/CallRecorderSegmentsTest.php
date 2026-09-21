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

use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\Tgcalls\CallRecorder;
use PHPUnit\Framework\TestCase;

/**
 * The recorder follows the party turning its mic, camera and screen share on and off: every change of
 * the flowing streams closes the file and continues in `<base>.N.mkv` with exactly those streams.
 */
final class CallRecorderSegmentsTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir().'/rec_'.bin2hex(random_bytes(4)).'.mkv';
    }

    protected function tearDown(): void
    {
        foreach (glob(substr($this->base, 0, -4).'*.mkv') ?: [] as $file) {
            unlink($file);
        }
    }

    /** A synthetic VP8 frame: a keyframe carries the start code and picture size. */
    private static function vp8(bool $keyframe, int $width = 320, int $height = 180): string
    {
        if ($keyframe) {
            return "\x10\x02\x00\x9d\x01\x2a".pack('vv', $width, $height).str_repeat('k', 40);
        }
        return "\x11\x02\x00".str_repeat('p', 20);
    }

    /**
     * @return array{audio: bool, video: bool, presentation: bool, videoWidth: ?int, presentationWidth: ?int, frames: int}
     */
    private static function describe(string $path): array
    {
        $m = new Matroska(new LocalFile($path), null);
        $result = ['audio' => false, 'video' => false, 'presentation' => false, 'videoWidth' => null, 'presentationWidth' => null, 'frames' => 0];
        foreach ($m->tracks as $number => $track) {
            if (($track['type'] ?? 0) === Matroska::TRACK_TYPE_AUDIO) {
                $result['audio'] = true;
            } elseif (($track['type'] ?? 0) === Matroska::TRACK_TYPE_VIDEO) {
                // Track 1 is the camera, track 3 the screen share.
                $result[$number === 3 ? 'presentation' : 'video'] = true;
            }
        }
        try {
            foreach ($m->frames as $frame) {
                $result['frames']++;
            }
        } catch (\Exception) {
            // A header-only file has no clusters to iterate.
        }
        return $result;
    }

    private function segment(int $n, string $streams): string
    {
        return substr($this->base, 0, -4).".{$n}_$streams.mkv";
    }

    public function testSegmentsFollowTheStreams(): void
    {
        $recorder = new CallRecorder(new LocalFile($this->base));
        $audioTs = 0;
        $audio = static function (int $frames) use ($recorder, &$audioTs): void {
            for ($i = 0; $i < $frames; $i++) {
                $recorder->pushAudioFrame(str_repeat('a', 30), $audioTs, 1);
                $audioTs += 960;
            }
        };

        // 1. The peer is unmuted, nothing else: an audio-only segment.
        $recorder->setExpected(audio: true, video: false, presentation: false);
        $audio(10);
        $this->assertFileExists($this->segment(0, 'audio'));

        // 2. The camera comes on: a new segment with audio + video, opened on the first keyframe.
        $recorder->setExpected(video: true);
        $audio(5);
        $recorder->pushVideoFrame(self::vp8(false), 0, 10); // not a keyframe: cannot start the track
        $this->assertFileDoesNotExist($this->segment(1, 'audio,video'));
        $recorder->pushVideoFrame(self::vp8(true), 3000, 10);
        $this->assertFileExists($this->segment(1, 'audio,video'));
        $recorder->pushVideoFrame(self::vp8(false), 6000, 10);
        $audio(5);

        // 3. The camera goes off again: back to audio only.
        $recorder->setExpected(video: false);
        $this->assertFileExists($this->segment(2, 'audio'));
        $recorder->pushVideoFrame(self::vp8(false), 9000, 10); // a straggler: ignored
        $audio(5);

        // 4. A screen share starts: audio + the presentation track.
        $recorder->setExpected(presentation: true);
        $recorder->pushVideoFrame(self::vp8(true, 1280, 720), 100000, 20, CallRecorder::SLOT_PRESENTATION);
        $this->assertFileExists($this->segment(3, 'audio,screen'));
        $recorder->pushVideoFrame(self::vp8(false), 103000, 20, CallRecorder::SLOT_PRESENTATION);
        $audio(5);

        // 5. The peer mutes: the screen share alone.
        $recorder->setExpected(audio: false);
        $this->assertFileExists($this->segment(4, 'screen'));
        $audio(3); // stragglers, ignored
        $recorder->pushVideoFrame(self::vp8(false), 106000, 20, CallRecorder::SLOT_PRESENTATION);

        // 6. The camera comes back while the screen share is on: both video tracks, no audio.
        $recorder->setExpected(video: true);
        $recorder->pushVideoFrame(self::vp8(true, 640, 360), 200000, 30);
        $this->assertFileExists($this->segment(5, 'video,screen'));
        $recorder->pushVideoFrame(self::vp8(false), 203000, 30);
        $recorder->pushVideoFrame(self::vp8(false), 109000, 20, CallRecorder::SLOT_PRESENTATION);
        $recorder->close();
        $this->assertCount(6, glob(substr($this->base, 0, -4).'.*.mkv') ?: []);

        // Audio keeps going into the open segment while the camera's first keyframe is awaited.
        $expected = [
            [0, 'audio', ['audio' => true, 'video' => false, 'presentation' => false, 'frames' => 10 + 5]],
            [1, 'audio,video', ['audio' => true, 'video' => true, 'presentation' => false, 'frames' => 2 + 5]],
            [2, 'audio', ['audio' => true, 'video' => false, 'presentation' => false, 'frames' => 5]],
            [3, 'audio,screen', ['audio' => true, 'video' => false, 'presentation' => true, 'frames' => 2 + 5]],
            [4, 'screen', ['audio' => false, 'video' => false, 'presentation' => true, 'frames' => 1]],
            [5, 'video,screen', ['audio' => false, 'video' => true, 'presentation' => true, 'frames' => 3]],
        ];
        foreach ($expected as [$n, $streams, $tracks]) {
            $actual = self::describe($this->segment($n, $streams));
            unset($actual['videoWidth'], $actual['presentationWidth']);
            $this->assertSame($tracks, $actual, "Segment $n");
        }
    }

    public function testCodecChangeStartsANewSegment(): void
    {
        $recorder = new CallRecorder(new LocalFile($this->base));
        $recorder->setExpected(audio: false, video: true, presentation: false);
        $recorder->pushVideoFrame(self::vp8(true), 0, 1);
        $recorder->pushVideoFrame(self::vp8(false), 3000, 1);
        // The camera is turned off and on: a new track, and a bigger picture — a new segment.
        $recorder->pushVideoFrame(self::vp8(false), 6000, 2); // the new track's inter frame: not yet
        $this->assertFileDoesNotExist($this->segment(1, 'video'));
        $recorder->pushVideoFrame(self::vp8(true, 1280, 720), 9000, 2);
        $this->assertFileExists($this->segment(1, 'video'));
        // Same size and codec again on yet another track: the segment continues.
        $recorder->pushVideoFrame(self::vp8(true, 1280, 720), 20000, 3);
        $recorder->close();
        $this->assertFileDoesNotExist($this->segment(2, 'video'));
        $this->assertSame(2, self::describe($this->segment(0, 'video'))['frames']);
        $this->assertSame(2, self::describe($this->segment(1, 'video'))['frames']);
    }

    public function testDirectoryStemAndNothingRecorded(): void
    {
        $dir = sys_get_temp_dir().'/rec_'.bin2hex(random_bytes(4)).'/';
        mkdir($dir);
        $recorder = new CallRecorder(new LocalFile($dir));
        $recorder->close();
        $this->assertSame([], glob($dir.'*') ?: []);
        $recorder = new CallRecorder(new LocalFile($dir), \danog\MadelineProto\RecordingFormat::Webm);
        $recorder->setExpected(audio: true, video: false, presentation: false);
        $recorder->pushAudioFrame('aaaa', 0, 1);
        $recorder->close();
        $this->assertFileExists($dir.'0_audio.webm');
        unlink($dir.'0_audio.webm');
        rmdir($dir);
    }
}
