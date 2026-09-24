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

namespace danog\MadelineProto\Tgcalls;

use Amp\ByteStream\WritableStream;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\OggWriter;
use Throwable;

use function Amp\File\openFile;

/**
 * Writes the incoming audio of a call to an OGG OPUS stream.
 *
 * The receiver is put in raw mode, so the OPUS frames that arrive over RTP (pushed in by the
 * {@see IncomingMedia} router, or by the legacy libtgvoip engine) are muxed into OGG exactly as they
 * were sent, with no decode/encode round trip: the recording is bit-identical to what the peer
 * transmitted, it costs almost nothing, and it needs no codec library (and therefore no FFI
 * extension). The resulting file can be played back by MadelineProto as-is.
 *
 * @internal
 */
final class OpusRecorder
{
    private OggWriter $writer;
    private bool $closed = false;

    public readonly int $streamId;
    public readonly ?LocalFile $file;

    public function __construct(LocalFile|WritableStream $out, ?int $streamId = null, string $description = 'incoming audio stream')
    {
        if ($out instanceof LocalFile) {
            $this->file = $out;
            $out = openFile($out->file, 'w');
        } else {
            $this->file = null;
        }
        $this->streamId = $streamId ?? random_int(-(2**31), (2**31)-1);
        $this->writer = new OggWriter($out, $this->streamId);
        $this->writer->writeHeader(1, OpusPlaybackTrack::CLOCK_RATE, $description);
    }

    /**
     * Drop the (unserializable) OGG writer; {@see self::resume()} reopens the file and continues
     * recording, so an incoming stream survives a serialize/deserialize cycle.
     *
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['writer']);
        return $vars;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function __unserialize(array $data): void
    {
        // Synchronous state restoration only — no async work here (see resume()). Until resume() runs,
        // $writer stays unset and writeChunk/writeOpus/close guard on isset($this->writer).
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Reopen the file and resume recording after the whole call graph has been deserialized: append a
     * fresh chained Opus stream. Called by the owner's resume(); never during unserialize (opening the
     * file suspends the fiber).
     */
    public function resume(): void
    {
        // Only file-backed recorders are ever serialized (their parent drops stream-backed ones), so
        // there is always a file to reopen; the guard is purely defensive.
        if ($this->closed || $this->file === null || isset($this->writer)) {
            return;
        }
        // Append a fresh chained Opus stream (its own serial) to the existing recording.
        $this->writer = new OggWriter(openFile($this->file->file, 'a'), random_int(-(2**31), (2**31) - 1));
        $this->writer->writeHeader(1, OpusPlaybackTrack::CLOCK_RATE, 'incoming audio stream (resumed)');
    }

    /**
     * Write one bare OPUS frame.
     *
     * @param int $samples The frame's duration in samples at 48 kHz, by which the granule position
     *                     advances: the RTP timestamp delta for WebRTC audio, and 60ms (the fixed
     *                     libtgvoip frame) by default.
     */
    public function writeOpus(string $frame, int $samples = 2880): void
    {
        if ($this->closed || $frame === '' || !isset($this->writer)) {
            return;
        }
        $this->writer->writeChunk($frame, $samples, false);
    }

    /**
     * Flush and close the output stream.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        try {
            if (isset($this->writer)) {
                $this->writer->writeChunk('', 0, true);
            }
        } catch (Throwable) {
        }
    }
}
