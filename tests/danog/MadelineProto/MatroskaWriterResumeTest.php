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
use danog\MadelineProto\Matroska;
use danog\MadelineProto\MatroskaWriter;
use PHPUnit\Framework\TestCase;

/**
 * A recording that continues after a restart is still a complete file: its frames follow the ones
 * written before, and its Duration covers both.
 */
final class MatroskaWriterResumeTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/resume_'.bin2hex(random_bytes(4)).'.mkv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    /** The Duration of a Matroska file (in milliseconds, the writer's TimestampScale), or null. */
    private static function duration(string $data): ?float
    {
        // The writer stores it as an 8-byte float right after its ID and a 1-byte size of 8.
        $pos = strpos($data, "\x44\x89\x88");
        return $pos === false ? null : unpack('E', substr($data, $pos + 3, 8))[1];
    }

    public function testAResumedRecordingKeepsItsDuration(): void
    {
        $writer = new MatroskaWriter(new LocalFile($this->file));
        $writer->setAudioTrack('A_OPUS', 48000, 2);
        $writer->start();
        for ($ms = 0; $ms < 1000; $ms += 20) {
            $writer->writeAudio("A$ms", $ms);
        }

        // The process restarts: the writer is serialized, then reopens the file and carries on.
        $writer = unserialize(serialize($writer));
        $this->assertInstanceOf(MatroskaWriter::class, $writer);
        $this->assertTrue($writer->resume());
        for ($ms = 1000; $ms < 2000; $ms += 20) {
            $writer->writeAudio("A$ms", $ms);
        }
        $writer->close();

        $data = file_get_contents($this->file);
        $this->assertSame(1980.0, self::duration($data));
        $frames = [];
        foreach ((new Matroska(new LocalFile($this->file)))->frames as $frame) {
            $frames[] = $frame['timestamp'];
        }
        $this->assertSame(range(0, 1980, 20), $frames);
    }
}
