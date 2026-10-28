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

use Amp\ByteStream\WritableBuffer;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\GroupCall\StreamReceiver;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\OggWriter;
use danog\MadelineProto\RecordingFormat;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

/**
 * The recording of a stream-mode call was lost at every restart (the receiver wasn't kept in the session), while the
 * WebRTC recordings of the same call continue: it's kept, and its files reopened.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class StreamRecordingResumeTest extends TestCase
{
    /**
     * @return list<array{serial: int, seqno: int, granule: int}>
     */
    private static function pages(string $ogg): array
    {
        $pages = [];
        $offset = 0;
        while ($offset < \strlen($ogg)) {
            self::assertSame('OggS', substr($ogg, $offset, 4));
            $header = unpack('Cversion/Cflags/Pgranule/Vserial/Vseqno/Vcrc/Csegments', substr($ogg, $offset + 4, 23));
            $lacing = array_sum(unpack('C*', substr($ogg, $offset + 27, $header['segments'])) ?: []);
            $pages[] = ['serial' => $header['serial'], 'seqno' => $header['seqno'], 'granule' => $header['granule']];
            $offset += 27 + $header['segments'] + $lacing;
        }
        return $pages;
    }

    public function testAnOggRecordingContinuesAfterARestart(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'madeline-ogg-resume');
        $writer = new OggWriter(new LocalFile($file));
        $writer->writeHeader(2, 48000, 'test');
        $writer->writeChunk(random_bytes(100), 960, false);

        $writer = unserialize(serialize($writer));
        $this->assertTrue($writer->resume());
        $writer->writeChunk(random_bytes(100), 960, true);

        $pages = self::pages((string) file_get_contents($file));
        $this->assertSame([0, 1, 2, 3], array_column($pages, 'seqno'), 'One stream, its pages in sequence');
        $this->assertCount(1, array_unique(array_column($pages, 'serial')));
        $this->assertSame([0, 0, 960, 1920], array_column($pages, 'granule'));
        $this->assertFalse($writer->resume(), 'Ended');
        unlink($file);
    }

    public function testAnOggRecordingWhoseFileWasDeletedEnds(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'madeline-ogg-resume');
        $writer = new OggWriter(new LocalFile($file));
        $writer->writeHeader(2, 48000, 'test');
        $writer = unserialize(serialize($writer));
        unlink($file);
        $this->assertFalse($writer->resume());
    }

    private function receiver(): StreamReceiver
    {
        BypassFinals::enable();
        return new StreamReceiver($this->createMock(GroupCallController::class));
    }

    public function testAFileRecordingIsKept(): void
    {
        $receiver = $this->receiver();
        $receiver->setOutput(new LocalFile(sys_get_temp_dir().'/madeline-stream-resume.ogg'), RecordingFormat::Opus);
        $kept = $receiver->__serialize();
        $this->assertInstanceOf(LocalFile::class, $kept["\0".StreamReceiver::class."\0outputFile"] ?? $kept['outputFile'] ?? null);
        $this->assertArrayNotHasKey("\0".StreamReceiver::class."\0running", $kept);
        $this->assertArrayNotHasKey('running', $kept);
    }

    public function testAStreamRecordingEnds(): void
    {
        $receiver = $this->receiver();
        $receiver->setOutput(new WritableBuffer, RecordingFormat::Opus);
        $kept = $receiver->__serialize();
        $this->assertArrayNotHasKey('outputFile', $kept);
        $this->assertArrayNotHasKey("\0".StreamReceiver::class."\0outputFile", $kept);
    }
}
