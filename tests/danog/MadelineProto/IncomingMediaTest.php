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

use danog\MadelineProto\CallStream;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\Tgcalls\IncomingMedia;
use danog\MadelineProto\Tgcalls\IncomingMediaObserver;
use PHPUnit\Framework\TestCase;

/**
 * The incoming media router reports every change of a participant's streams and codecs, and every
 * recording starting or ending, exactly once — the raw material of a CallStreams update.
 */
final class IncomingMediaTest extends TestCase
{
    /** @var list<array{int, array<int, string>, ?RecordingEvent, ?string}> */
    private array $events = [];
    private IncomingMedia $media;
    private string $file;

    protected function setUp(): void
    {
        $this->events = [];
        $this->file = sys_get_temp_dir().'/rec_'.bin2hex(random_bytes(4)).'.mkv';
        $observer = new class($this->events) implements IncomingMediaObserver {
            /** @param list<array{int, array<int, string>, ?RecordingEvent, ?string}> $events */
            public function __construct(private array &$events)
            {
            }
            public function onIncomingMediaChanged(IncomingMedia $media, ?RecordingEvent $recording, ?LocalFile $file): void
            {
                $this->events[] = [$media->getAvailable(), $media->getCodecs(), $recording, $file !== null ? basename($file->file) : null];
            }
        };
        $this->media = new IncomingMedia($observer);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    private static function vp8(bool $keyframe): string
    {
        return $keyframe ? "\x10\x02\x00\x9d\x01\x2a".pack('vv', 320, 180).str_repeat('k', 40) : "\x11\x02\x00".str_repeat('p', 20);
    }

    public function testStreamsAndCodecsAreReportedOnce(): void
    {
        $this->assertFalse($this->media->isKnown());
        $this->assertSame(0, $this->media->getAvailable());
        $this->media->setExpected(audio: true, video: false, presentation: false);
        $this->assertTrue($this->media->isKnown());
        $this->media->setExpected(audio: true); // unchanged: not reported again
        $this->media->pushAudioFrame('aaaa', 0, 1);
        $this->media->pushAudioFrame('aaaa', 960, 1); // the codec is known already
        $this->media->setExpected(video: true);
        $this->media->pushVideoFrame(self::vp8(false), 0, 10); // no keyframe yet: nothing to describe
        $this->media->pushVideoFrame(self::vp8(true), 3000, 10);
        $this->media->pushVideoFrame(self::vp8(false), 6000, 10);
        // The camera goes off: its codec is forgotten with it.
        $this->media->setExpected(video: false);
        $this->assertSame([
            [CallStream::AUDIO, [], null, null],
            [CallStream::AUDIO, [CallStream::AUDIO => 'A_OPUS'], null, null],
            [CallStream::AUDIO | CallStream::VIDEO, [CallStream::AUDIO => 'A_OPUS'], null, null],
            [CallStream::AUDIO | CallStream::VIDEO, [CallStream::AUDIO => 'A_OPUS', CallStream::VIDEO => 'V_VP8'], null, null],
            [CallStream::AUDIO, [CallStream::AUDIO => 'A_OPUS'], null, null],
        ], $this->events);
    }

    public function testRecordingEventsCarryTheFile(): void
    {
        $this->media->setExpected(audio: true, video: false, presentation: false);
        $this->media->recordFixed(new LocalFile($this->file), RecordingFormat::Mkv, CallStream::AUDIO);
        $this->assertNotNull($this->media->getRecorder());
        $this->media->pushAudioFrame('aaaa', 0, 1);
        // Stopping (or replacing) the recording finishes the file.
        $this->media->stopRecording();
        $this->assertNull($this->media->getRecorder());
        $this->assertSame([
            [CallStream::AUDIO, [], null, null],
            [CallStream::AUDIO, [], RecordingEvent::Started, basename($this->file)],
            [CallStream::AUDIO, [CallStream::AUDIO => 'A_OPUS'], null, null],
            [CallStream::AUDIO, [CallStream::AUDIO => 'A_OPUS'], RecordingEvent::Ended, basename($this->file)],
        ], $this->events);
        $this->assertFileExists($this->file);
    }

    public function testCloseFinishesTheRecording(): void
    {
        $this->media->recordFixed(new LocalFile($this->file), RecordingFormat::Mkv, CallStream::AUDIO);
        $this->media->close();
        $this->assertNull($this->media->getRecorder());
        $this->assertSame([RecordingEvent::Started, RecordingEvent::Ended], array_column($this->events, 2));
        // Nothing is accepted after closing.
        $this->media->setExpected(audio: true);
        $this->media->pushAudioFrame('aaaa', 0, 1);
        $this->assertCount(2, $this->events);
    }
}
