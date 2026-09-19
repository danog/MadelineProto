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

namespace danog\MadelineProto;

use Amp\ByteStream\WritableStream;
use Amp\File\File;
use Amp\File\Whence;

use function Amp\File\openFile;

/**
 * A minimal, streaming Matroska (.mkv) muxer, written entirely in PHP.
 *
 * It writes the EBML header, one video and/or one audio track and a sequence of clusters holding
 * SimpleBlocks — enough to record the already-encoded video and OPUS audio of a call into a file that
 * standard players read, without decoding, ffmpeg, any CLI tool or FFI. It is the write-side
 * counterpart of {@see Matroska}.
 *
 * A new cluster is started at every video keyframe (and whenever a block's timestamp would leave the
 * signed 16-bit range a SimpleBlock allows), so the file stays seekable and keyframes are cluster
 * aligned.
 *
 * @internal
 */
final class MatroskaWriter
{
    // EBML element IDs, on-disk form (with their length-descriptor bits).
    private const ID_EBML = "\x1A\x45\xDF\xA3";
    private const ID_SEGMENT = "\x18\x53\x80\x67";
    private const ID_INFO = "\x15\x49\xA9\x66";
    private const ID_TIMESTAMP_SCALE = "\x2A\xD7\xB1";
    private const ID_DURATION = "\x44\x89";
    private const ID_MUXING_APP = "\x4D\x80";
    private const ID_WRITING_APP = "\x57\x41";
    private const ID_TRACKS = "\x16\x54\xAE\x6B";
    private const ID_TRACK_ENTRY = "\xAE";
    private const ID_TRACK_NUMBER = "\xD7";
    private const ID_TRACK_UID = "\x73\xC5";
    private const ID_TRACK_TYPE = "\x83";
    private const ID_CODEC_ID = "\x86";
    private const ID_CODEC_PRIVATE = "\x63\xA2";
    private const ID_VIDEO = "\xE0";
    private const ID_PIXEL_WIDTH = "\xB0";
    private const ID_PIXEL_HEIGHT = "\xBA";
    private const ID_AUDIO = "\xE1";
    private const ID_SAMPLING_FREQUENCY = "\xB5";
    private const ID_CHANNELS = "\x9F";
    private const ID_CLUSTER = "\x1F\x43\xB6\x75";
    private const ID_TIMESTAMP = "\xE7";
    private const ID_SIMPLE_BLOCK = "\xA3";

    private const TRACK_VIDEO = 1;
    private const TRACK_AUDIO = 2;
    private const VIDEO_TRACK_NUMBER = 1;
    private const AUDIO_TRACK_NUMBER = 2;

    /** One timestamp tick is a millisecond (TimestampScale = 1e6 ns). */
    private const TIMESTAMP_SCALE_NS = 1000000;

    /** Keep a cluster's relative timestamps within a comfortable part of the signed-16-bit range. */
    private const MAX_CLUSTER_MS = 30000;

    private WritableStream $out;
    /** Whether {@see self::$out} is a seekable file we can back-patch the Duration into on close(). */
    private bool $seekable;

    /** @var array{codecId: string, width: int, height: int, private: string}|null */
    private ?array $video = null;
    /** @var array{codecId: string, rate: int, channels: int, private: string}|null */
    private ?array $audio = null;

    private bool $headerWritten = false;
    private bool $closed = false;

    /** Wall value (ms) the whole file is rebased onto: the first frame's timestamp. */
    private ?int $baseMs = null;
    /** Byte offset of the 8-byte Duration value to patch on close(), or null when not seekable. */
    private ?int $durationOffset = null;
    /** Highest file-relative block timestamp (ms) seen, written as the Duration on close(). */
    private ?int $maxTimestampMs = null;
    /** Timestamp (ms, file-relative) of the currently open cluster, or null if none is open. */
    private ?int $clusterBaseMs = null;
    /** Buffered body of the currently open cluster. */
    private string $clusterBody = '';

    /** The file being written, kept so the writer can reopen it after a serialize/deserialize cycle. */
    private ?LocalFile $localFile = null;

    public function __construct(LocalFile|WritableStream $out)
    {
        if ($out instanceof LocalFile) {
            $this->localFile = $out;
            $this->out = openFile($out->file, 'w');
        } else {
            $this->out = $out;
        }
        // Only a real file handle can be rewound to back-patch the total Duration on close().
        $this->seekable = $this->out instanceof File;
    }

    /**
     * Drop the (unserializable) file handle, flushing any buffered cluster first so nothing is lost.
     * {@see self::__unserialize()} reopens the file to continue the recording.
     */
    public function __serialize(): array
    {
        if ($this->headerWritten && !$this->closed) {
            $this->flushCluster();
        }
        $vars = get_object_vars($this);
        unset($vars['out']);
        return $vars;
    }

    /**
     * Reopen the file in append mode and continue the recording where it left off. The EBML header,
     * Tracks and Duration placeholder are already on disk, so only new clusters are appended.
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        // Only file-backed writers are ever serialized (their parent drops stream-backed recorders),
        // so there is always a file to reopen; the guard is purely defensive.
        if ($this->closed || $this->localFile === null) {
            $this->closed = true;
            return;
        }
        $this->out = openFile($this->localFile->file, 'a');
        // Append mode cannot seek back to patch the Duration, so stop advertising it as seekable.
        $this->seekable = false;
    }

    /**
     * Declare the video track. `$codecPrivate` is empty for VP8/VP9 and the codec configuration
     * record for H.264/AV1.
     */
    public function setVideoTrack(string $codecId, int $width, int $height, string $codecPrivate = ''): void
    {
        $this->video = ['codecId' => $codecId, 'width' => max(1, $width), 'height' => max(1, $height), 'private' => $codecPrivate];
    }

    /**
     * Declare the audio track (OPUS, 48kHz, with its OpusHead as the codec private data).
     */
    public function setAudioTrack(string $codecId, int $rate, int $channels, string $codecPrivate = ''): void
    {
        $this->audio = ['codecId' => $codecId, 'rate' => $rate, 'channels' => max(1, $channels), 'private' => $codecPrivate];
    }

    public function hasVideoTrack(): bool
    {
        return $this->video !== null;
    }

    /**
     * Write the EBML header, segment and track list. Called once, after the tracks are declared.
     */
    public function start(): void
    {
        if ($this->headerWritten || $this->closed) {
            return;
        }
        $this->headerWritten = true;

        $ebml = self::element(self::ID_EBML,
            self::uintElement("\x42\x86", 1)              // EBMLVersion
            .self::uintElement("\x42\xF7", 1)             // EBMLReadVersion
            .self::uintElement("\x42\xF2", 4)             // EBMLMaxIDLength
            .self::uintElement("\x42\xF3", 8)             // EBMLMaxSizeLength
            .self::stringElement("\x42\x82", 'matroska')  // DocType
            .self::uintElement("\x42\x87", 4)             // DocTypeVersion
            .self::uintElement("\x42\x85", 2)             // DocTypeReadVersion
        );

        $infoBody = self::uintElement(self::ID_TIMESTAMP_SCALE, self::TIMESTAMP_SCALE_NS)
            .self::stringElement(self::ID_MUXING_APP, 'MadelineProto')
            .self::stringElement(self::ID_WRITING_APP, 'MadelineProto');
        // On a seekable output, reserve a Duration element (a 64-bit float, in TimestampScale units,
        // i.e. milliseconds) as the LAST element of Info, so its value is the last 8 bytes of $info;
        // close() rewinds to it and writes the real duration. Non-seekable streams stay Duration-less.
        if ($this->seekable) {
            $infoBody .= self::ID_DURATION.self::ebmlSize(8).pack('E', 0.0);
        }
        $info = self::element(self::ID_INFO, $infoBody);

        $tracks = '';
        if ($this->video !== null) {
            $entry = self::uintElement(self::ID_TRACK_NUMBER, self::VIDEO_TRACK_NUMBER)
                .self::uintElement(self::ID_TRACK_UID, self::VIDEO_TRACK_NUMBER)
                .self::uintElement(self::ID_TRACK_TYPE, self::TRACK_VIDEO)
                .self::stringElement(self::ID_CODEC_ID, $this->video['codecId'])
                .self::element(self::ID_VIDEO,
                    self::uintElement(self::ID_PIXEL_WIDTH, $this->video['width'])
                    .self::uintElement(self::ID_PIXEL_HEIGHT, $this->video['height'])
                );
            if ($this->video['private'] !== '') {
                $entry .= self::element(self::ID_CODEC_PRIVATE, $this->video['private']);
            }
            $tracks .= self::element(self::ID_TRACK_ENTRY, $entry);
        }
        if ($this->audio !== null) {
            $entry = self::uintElement(self::ID_TRACK_NUMBER, self::AUDIO_TRACK_NUMBER)
                .self::uintElement(self::ID_TRACK_UID, self::AUDIO_TRACK_NUMBER)
                .self::uintElement(self::ID_TRACK_TYPE, self::TRACK_AUDIO)
                .self::stringElement(self::ID_CODEC_ID, $this->audio['codecId'])
                .self::element(self::ID_AUDIO,
                    self::floatElement(self::ID_SAMPLING_FREQUENCY, (float) $this->audio['rate'])
                    .self::uintElement(self::ID_CHANNELS, $this->audio['channels'])
                );
            if ($this->audio['private'] !== '') {
                $entry .= self::element(self::ID_CODEC_PRIVATE, $this->audio['private']);
            }
            $tracks .= self::element(self::ID_TRACK_ENTRY, $entry);
        }

        // The segment uses the "unknown size" length so it can be streamed; players read to EOF.
        $header = $ebml.self::ID_SEGMENT."\x01\xFF\xFF\xFF\xFF\xFF\xFF\xFF";
        if ($this->seekable) {
            // The reserved Duration value is the last 8 bytes of $info, which follows $header.
            $this->durationOffset = \strlen($header) + \strlen($info) - 8;
        }
        $this->out->write($header.$info.self::element(self::ID_TRACKS, $tracks));
    }

    /**
     * Append one encoded video frame.
     */
    public function writeVideo(string $data, int $timestampMs, bool $keyframe): void
    {
        $this->writeBlock(self::VIDEO_TRACK_NUMBER, $data, $timestampMs, $keyframe, $keyframe);
    }

    /**
     * Append one encoded (OPUS) audio frame.
     */
    public function writeAudio(string $data, int $timestampMs): void
    {
        $this->writeBlock(self::AUDIO_TRACK_NUMBER, $data, $timestampMs, true, false);
    }

    private function writeBlock(int $trackNumber, string $data, int $timestampMs, bool $keyframe, bool $startsCluster): void
    {
        if ($this->closed || !$this->headerWritten || $data === '') {
            return;
        }
        $this->baseMs ??= $timestampMs;
        $relativeToFile = max(0, $timestampMs - $this->baseMs);
        $this->maxTimestampMs = max($this->maxTimestampMs ?? 0, $relativeToFile);

        // Start a new cluster on a video keyframe, or when the block would fall outside the open
        // cluster's signed-16-bit relative range.
        if ($this->clusterBaseMs === null
            || ($startsCluster && $trackNumber === self::VIDEO_TRACK_NUMBER)
            || $relativeToFile - $this->clusterBaseMs > self::MAX_CLUSTER_MS
        ) {
            $this->flushCluster();
            $this->clusterBaseMs = $relativeToFile;
        }

        $relative = $relativeToFile - $this->clusterBaseMs;
        // Clamp into the SimpleBlock signed-16-bit relative timestamp.
        $relative = max(-32768, min(32767, $relative));

        $block = self::vint($trackNumber)          // track number as an EBML vint
            .pack('n', $relative & 0xFFFF)          // signed 16-bit relative timestamp
            .\chr($keyframe ? 0x80 : 0x00)          // flags: keyframe bit
            .$data;
        $this->clusterBody .= self::element(self::ID_SIMPLE_BLOCK, $block);
    }

    private function flushCluster(): void
    {
        if ($this->clusterBaseMs === null || $this->clusterBody === '') {
            $this->clusterBody = '';
            return;
        }
        $cluster = self::uintElement(self::ID_TIMESTAMP, $this->clusterBaseMs).$this->clusterBody;
        $this->out->write(self::element(self::ID_CLUSTER, $cluster));
        $this->clusterBody = '';
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        if ($this->headerWritten) {
            $this->flushCluster();
            // Back-patch the total Duration now that the last timestamp is known (seekable files only).
            if ($this->seekable && $this->durationOffset !== null && $this->maxTimestampMs !== null) {
                \assert($this->out instanceof File);
                $this->out->seek($this->durationOffset);
                $this->out->write(pack('E', (float) $this->maxTimestampMs));
                $this->out->seek(0, Whence::End);
            }
        }
        $this->out->end();
    }

    /* ----------------------------------------------------------------- *
     *  EBML encoding helpers.
     * ----------------------------------------------------------------- */

    /** An element: id + size + payload. */
    private static function element(string $id, string $payload): string
    {
        return $id.self::ebmlSize(\strlen($payload)).$payload;
    }

    /** Encode a size as an EBML variable-length integer with its length descriptor. */
    private static function ebmlSize(int $size): string
    {
        for ($len = 1; $len <= 8; $len++) {
            if ($size < (2 ** (7 * $len)) - 1) {
                break;
            }
        }
        $marker = 1 << (7 * $len);
        return self::intToBytes($marker | $size, $len);
    }

    /** Encode an unsigned integer as an EBML vint (used for the SimpleBlock track number). */
    private static function vint(int $value): string
    {
        for ($len = 1; $len <= 8; $len++) {
            if ($value < (2 ** (7 * $len)) - 1) {
                break;
            }
        }
        $marker = 1 << (7 * $len);
        return self::intToBytes($marker | $value, $len);
    }

    private static function uintElement(string $id, int $value): string
    {
        $bytes = $value === 0 ? "\x00" : '';
        $v = $value;
        while ($v > 0) {
            $bytes = \chr($v & 0xFF).$bytes;
            $v >>= 8;
        }
        return self::element($id, $bytes);
    }

    private static function stringElement(string $id, string $value): string
    {
        return self::element($id, $value);
    }

    private static function floatElement(string $id, float $value): string
    {
        return self::element($id, pack('E', $value)); // 64-bit big-endian IEEE 754
    }

    private static function intToBytes(int $value, int $len): string
    {
        $bytes = '';
        for ($i = $len - 1; $i >= 0; $i--) {
            $bytes .= \chr(($value >> (8 * $i)) & 0xFF);
        }
        return $bytes;
    }
}
