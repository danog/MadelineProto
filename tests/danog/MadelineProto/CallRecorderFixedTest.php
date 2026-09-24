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

use Amp\ByteStream\WritableStream;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\Tgcalls\CallRecorder;
use danog\MadelineProto\Tgcalls\RecordingObserver;
use PHPUnit\Framework\TestCase;

/**
 * A fixed recorder (the {@see \danog\MadelineProto\EventHandler\Call::setOutput()} mode) writes one file
 * whose tracks never change: a stream turned off just stops being written, a stream not chosen is
 * ignored, and only a change of codec finishes the file.
 */
final class CallRecorderFixedTest extends TestCase
{
    private string $file;
    /** @var list<string> */
    private array $events = [];
    private RecordingObserver $observer;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/rec_'.bin2hex(random_bytes(4)).'.mkv';
        $this->events = [];
        $this->observer = new class($this->events) implements RecordingObserver {
            /** @param list<string> $events */
            public function __construct(private array &$events)
            {
            }
            public function onRecordingStarted(CallRecorder $recorder, LocalFile|WritableStream $out): void
            {
                $this->events[] = 'started:'.($out instanceof LocalFile ? basename($out->file) : 'stream');
            }
            public function onRecordingEnded(CallRecorder $recorder, LocalFile|WritableStream $out): void
            {
                $this->events[] = 'ended:'.($out instanceof LocalFile ? basename($out->file) : 'stream');
            }
        };
    }

    protected function tearDown(): void
    {
        foreach (glob(substr($this->file, 0, -4).'*') ?: [] as $file) {
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

    /** A synthetic VP9 frame: frame marker 0b10, profile 0, and a keyframe (frame_type 0) or not. */
    private static function vp9(bool $keyframe): string
    {
        return ($keyframe ? "\x82" : "\x86")."\x49\x83\x42\x00\x01\x3f\x00\xb3".str_repeat('v', 30);
    }

    /**
     * @return array{audio: bool, video: bool, screen: bool, codec: ?string, frames: int}
     */
    private static function describe(string $path): array
    {
        $m = new Matroska(new LocalFile($path), null);
        $result = ['audio' => false, 'video' => false, 'screen' => false, 'codec' => null, 'frames' => 0];
        foreach ($m->tracks as $number => $track) {
            if (($track['type'] ?? 0) === Matroska::TRACK_TYPE_AUDIO) {
                $result['audio'] = true;
            } elseif (($track['type'] ?? 0) === Matroska::TRACK_TYPE_VIDEO) {
                // Track 1 is the camera, track 3 the screen share.
                $result[$number === 3 ? 'screen' : 'video'] = true;
                $result['codec'] ??= $track['codec'];
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

    public function testAudioOnlyOpensAtOnceAndFollowsMuting(): void
    {
        $recorder = CallRecorder::fixed(new LocalFile($this->file), RecordingFormat::Mkv, CallStream::AUDIO, $this->observer);
        // No video track to describe: the header is written right away.
        $this->assertFileExists($this->file);
        $this->assertSame(['started:'.basename($this->file)], $this->events);
        $recorder->setExpected(audio: true, video: false, presentation: false);
        $ts = 0;
        for ($i = 0; $i < 5; $i++, $ts += 960) {
            $recorder->pushAudioFrame(str_repeat('a', 30), $ts, 1);
        }
        // The peer mutes: nothing is written, the file stays open.
        $recorder->setExpected(audio: false);
        for ($i = 0; $i < 3; $i++, $ts += 960) {
            $recorder->pushAudioFrame(str_repeat('a', 30), $ts, 1);
        }
        // ...and unmutes: writing resumes into the same file.
        $recorder->setExpected(audio: true);
        for ($i = 0; $i < 4; $i++, $ts += 960) {
            $recorder->pushAudioFrame(str_repeat('a', 30), $ts, 1);
        }
        // The camera comes on, but was not chosen: ignored, the file's tracks are fixed.
        $recorder->setExpected(video: true);
        $recorder->pushVideoFrame(self::vp8(true), 0, 10);
        $recorder->pushVideoFrame(self::vp8(false), 3000, 10);
        $this->assertFalse($recorder->isClosed());
        $recorder->close();
        $this->assertTrue($recorder->isClosed());
        $this->assertSame(['started:'.basename($this->file), 'ended:'.basename($this->file)], $this->events);
        $this->assertCount(1, glob(substr($this->file, 0, -4).'*') ?: []);
        $this->assertSame(['audio' => true, 'video' => false, 'screen' => false, 'codec' => null, 'frames' => 9], self::describe($this->file));
    }

    public function testVideoWaitsForTheKeyframeAndSurvivesTheCameraToggling(): void
    {
        $recorder = CallRecorder::fixed(new LocalFile($this->file), RecordingFormat::Mkv, CallStream::AUDIO | CallStream::VIDEO, $this->observer);
        $recorder->setExpected(audio: true, video: true, presentation: false);
        // The audio is buffered until the camera's first keyframe describes the video track.
        $recorder->pushAudioFrame(str_repeat('a', 30), 0, 1);
        $recorder->pushAudioFrame(str_repeat('a', 30), 960, 1);
        $this->assertFileDoesNotExist($this->file);
        $recorder->pushVideoFrame(self::vp8(false), 0, 10); // not a keyframe: cannot start the track
        $this->assertFileDoesNotExist($this->file);
        $recorder->pushVideoFrame(self::vp8(true), 3000, 10);
        $this->assertFileExists($this->file);
        $this->assertSame(['started:'.basename($this->file)], $this->events);
        $recorder->pushVideoFrame(self::vp8(false), 6000, 10);
        // The camera goes off: its stragglers are dropped, audio goes on.
        $recorder->setExpected(video: false);
        $recorder->pushVideoFrame(self::vp8(false), 9000, 10);
        $recorder->pushAudioFrame(str_repeat('a', 30), 1920, 1);
        // ...and comes back as a new source, in the same codec but a bigger picture: same file.
        $recorder->setExpected(video: true);
        $recorder->pushVideoFrame(self::vp8(true, 1280, 720), 100000, 11);
        $recorder->pushVideoFrame(self::vp8(false), 103000, 11);
        $this->assertFalse($recorder->isClosed());
        $recorder->close();
        $this->assertSame(['started:'.basename($this->file), 'ended:'.basename($this->file)], $this->events);
        $this->assertSame(['audio' => true, 'video' => true, 'screen' => false, 'codec' => 'V_VP8', 'frames' => 3 + 4], self::describe($this->file));
    }

    public function testCodecChangeFinishesTheFile(): void
    {
        $recorder = CallRecorder::fixed(new LocalFile($this->file), RecordingFormat::Mkv, CallStream::VIDEO, $this->observer);
        $recorder->setExpected(audio: false, video: true, presentation: false);
        $recorder->pushVideoFrame(self::vp8(true), 0, 1);
        $recorder->pushVideoFrame(self::vp8(false), 3000, 1);
        // The camera is turned off and on with another codec: a Matroska track cannot follow.
        $recorder->setExpected(video: false);
        $recorder->setExpected(video: true);
        $recorder->pushVideoFrame(self::vp9(true), 6000, 2);
        $this->assertTrue($recorder->isClosed());
        $this->assertSame(['started:'.basename($this->file), 'ended:'.basename($this->file)], $this->events);
        $recorder->pushVideoFrame(self::vp9(false), 9000, 2); // finished: dropped
        $recorder->close();
        $this->assertCount(2, $this->events);
        $this->assertSame(['audio' => false, 'video' => true, 'screen' => false, 'codec' => 'V_VP8', 'frames' => 2], self::describe($this->file));
    }

    public function testEveryAvailableStreamWhenNoneIsChosen(): void
    {
        $recorder = CallRecorder::fixed(new LocalFile($this->file), RecordingFormat::Mkv, null, $this->observer);
        $recorder->setExpected(audio: true, video: false, presentation: true);
        $recorder->pushAudioFrame(str_repeat('a', 30), 0, 1);
        $this->assertFileDoesNotExist($this->file); // the screen share, reported on, is awaited
        $recorder->pushVideoFrame(self::vp8(true, 1280, 720), 0, 20, CallStream::SCREEN);
        $this->assertFileExists($this->file);
        // The camera comes on later: the file's tracks are fixed, so it is ignored.
        $recorder->setExpected(video: true);
        $recorder->pushVideoFrame(self::vp8(true), 0, 30);
        $recorder->pushVideoFrame(self::vp8(false), 3000, 30);
        $recorder->pushVideoFrame(self::vp8(false), 3000, 20, CallStream::SCREEN);
        $recorder->close();
        $this->assertSame(['started:'.basename($this->file), 'ended:'.basename($this->file)], $this->events);
        $this->assertSame(['audio' => true, 'video' => false, 'screen' => true, 'codec' => 'V_VP8', 'frames' => 1 + 2], self::describe($this->file));
    }

    public function testChosenStreamsAreValidated(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CallRecorder::fixed(new LocalFile($this->file), RecordingFormat::Mkv, 8);
    }
}
