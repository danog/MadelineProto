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

use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use Closure;
use Generator;

/**
 * A streaming Matroska/WebM demuxer, written entirely in PHP.
 *
 * WebM stores VP8/VP9 video and OPUS audio as bare, already-encoded frames, which is exactly what
 * Telegram calls put on the wire: MadelineProto can therefore play a `.webm` file without decoding
 * anything, and without needing FFI, ffmpeg or any shell command.
 *
 * Only the elements needed to pull frames out of a file are parsed: everything else is skipped.
 *
 * @internal
 */
final class Matroska
{
    // EBML element IDs, including their length marker, as they appear on disk.
    private const ID_SEGMENT = 0x18538067;
    private const ID_INFO = 0x1549A966;
    private const ID_TIMESTAMP_SCALE = 0x2AD7B1;
    private const ID_TRACKS = 0x1654AE6B;
    private const ID_TRACK_ENTRY = 0xAE;
    private const ID_TRACK_NUMBER = 0xD7;
    private const ID_TRACK_TYPE = 0x83;
    private const ID_CODEC_ID = 0x86;
    private const ID_CODEC_PRIVATE = 0x63A2;
    private const ID_CLUSTER = 0x1F43B675;
    private const ID_TIMESTAMP = 0xE7;
    private const ID_SIMPLE_BLOCK = 0xA3;
    private const ID_BLOCK_GROUP = 0xA0;
    private const ID_BLOCK = 0xA1;

    /** Elements whose children we walk into instead of skipping. */
    private const MASTER_ELEMENTS = [
        self::ID_SEGMENT => true,
        self::ID_INFO => true,
        self::ID_TRACKS => true,
        self::ID_TRACK_ENTRY => true,
        self::ID_CLUSTER => true,
        self::ID_BLOCK_GROUP => true,
    ];

    public const TRACK_TYPE_VIDEO = 1;
    public const TRACK_TYPE_AUDIO = 2;

    /**
     * Frames of the file, in storage order.
     *
     * Each entry is `['track' => int, 'codec' => string, 'type' => int, 'data' => string,
     * 'timestamp' => int (milliseconds), 'keyframe' => bool]`.
     *
     * @var iterable<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}>
     */
    public readonly iterable $frames;

    /**
     * Track metadata, keyed by track number.
     *
     * @var array<int, array{codec: string, type: int, private: ?string}>
     */
    public array $tracks = [];

    /** Duration of one timestamp unit, in nanoseconds. */
    private int $timestampScale = 1000000;

    /** @var Closure(int): ?string Reads exactly the requested number of bytes, or null at EOF. */
    private Closure $read;
    private string $buffer = '';
    private bool $eof = false;

    public function __construct(LocalFile|RemoteUrl|ReadableStream $stream, ?Cancellation $cancellation = null)
    {
        $this->read = Tools::openBuffered($stream, $cancellation);
        $it = $this->read();
        // Prime the generator so that the track list is populated before the caller iterates.
        $it->current();
        $this->frames = $it;
    }

    /**
     * Whether the file declares a track using the given codec.
     */
    public function hasCodec(string $codec): bool
    {
        foreach ($this->tracks as $track) {
            if ($track['codec'] === $codec) {
                return true;
            }
        }
        return false;
    }

    /**
     * Make sure at least `$length` bytes are buffered, reading more from the stream if needed.
     */
    private function fill(int $length): bool
    {
        while (\strlen($this->buffer) < $length && !$this->eof) {
            $chunk = ($this->read)($length - \strlen($this->buffer));
            if ($chunk === null || $chunk === '') {
                $this->eof = true;
                break;
            }
            $this->buffer .= $chunk;
        }
        return \strlen($this->buffer) >= $length;
    }

    private function consume(int $length): string
    {
        $this->fill($length);
        $data = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, \strlen($data));
        return $data;
    }

    /**
     * Read an EBML variable length integer.
     *
     * @param bool $keepMarker Element IDs keep the length marker, sizes strip it.
     * @return array{int, int}|null The value and how many bytes it occupied, or null at EOF.
     */
    private function readVint(bool $keepMarker): ?array
    {
        if (!$this->fill(1)) {
            return null;
        }
        $first = \ord($this->buffer[0]);
        if ($first === 0) {
            return null;
        }
        $length = 1;
        for ($mask = 0x80; $mask > 0 && !($first & $mask); $mask >>= 1) {
            $length++;
        }
        if ($length > 8 || !$this->fill($length)) {
            return null;
        }
        $raw = $this->consume($length);

        $value = \ord($raw[0]);
        if (!$keepMarker) {
            // Clear the marker bit that indicates the length.
            $value &= (1 << (8 - $length)) - 1;
        }
        for ($i = 1; $i < $length; $i++) {
            $value = ($value << 8) | \ord($raw[$i]);
        }
        return [$value, $length];
    }

    /**
     * Walk the file, yielding every frame found in a cluster.
     *
     * @return Generator<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}>
     */
    private function read(): Generator
    {
        $clusterTimestamp = 0;
        /** @var array{number: ?int, codec: ?string, type: ?int, private: ?string} $pending */
        $pending = ['number' => null, 'codec' => null, 'type' => null, 'private' => null];

        while (true) {
            $id = $this->readVint(true);
            if ($id === null) {
                break;
            }
            $size = $this->readVint(false);
            if ($size === null) {
                break;
            }
            [$elementId] = $id;
            [$elementSize] = $size;

            // An "unknown size" master element (all size bits set) is streamed: walk into it.
            $unknownSize = $elementSize === (1 << (7 * $size[1])) - 1;

            if (isset(self::MASTER_ELEMENTS[$elementId])) {
                if ($elementId === self::ID_TRACK_ENTRY) {
                    $pending = ['number' => null, 'codec' => null, 'type' => null, 'private' => null];
                }
                continue;
            }

            if ($unknownSize) {
                continue;
            }

            switch ($elementId) {
                case self::ID_TIMESTAMP_SCALE:
                    $this->timestampScale = self::toInt($this->consume($elementSize));
                    break;
                case self::ID_TRACK_NUMBER:
                    $pending['number'] = self::toInt($this->consume($elementSize));
                    $this->flushTrack($pending);
                    break;
                case self::ID_TRACK_TYPE:
                    $pending['type'] = self::toInt($this->consume($elementSize));
                    $this->flushTrack($pending);
                    break;
                case self::ID_CODEC_ID:
                    $pending['codec'] = rtrim($this->consume($elementSize), "\0");
                    $this->flushTrack($pending);
                    break;
                case self::ID_CODEC_PRIVATE:
                    // Codec setup data, such as the AVCDecoderConfigurationRecord of an H.264
                    // track: some codecs cannot be decoded at all without it.
                    $pending['private'] = $this->consume($elementSize);
                    $this->flushTrack($pending);
                    break;
                case self::ID_TIMESTAMP:
                    $clusterTimestamp = self::toInt($this->consume($elementSize));
                    break;
                case self::ID_SIMPLE_BLOCK:
                case self::ID_BLOCK:
                    $block = $this->consume($elementSize);
                    foreach ($this->parseBlock($block, $clusterTimestamp, $elementId === self::ID_SIMPLE_BLOCK) as $frame) {
                        yield $frame;
                    }
                    break;
                default:
                    // Not interesting: skip the payload entirely.
                    $this->skip($elementSize);
            }

            if ($this->eof && $this->buffer === '') {
                break;
            }
        }
    }

    /**
     * Register a track once all three of its mandatory fields are known.
     *
     * Called again for every field, so a `CodecPrivate` that arrives after the codec ID simply
     * updates the entry that is already there.
     *
     * @param array{number: ?int, codec: ?string, type: ?int, private: ?string} $pending
     */
    private function flushTrack(array $pending): void
    {
        if ($pending['number'] === null || $pending['codec'] === null || $pending['type'] === null) {
            return;
        }
        $this->tracks[$pending['number']] = [
            'codec' => $pending['codec'],
            'type' => $pending['type'],
            'private' => $pending['private'],
        ];
    }

    /**
     * Discard `$length` bytes without buffering them all at once.
     */
    private function skip(int $length): void
    {
        while ($length > 0) {
            if ($this->buffer !== '') {
                $take = min($length, \strlen($this->buffer));
                $this->buffer = substr($this->buffer, $take);
                $length -= $take;
                continue;
            }
            if (!$this->fill(min($length, 65536))) {
                return;
            }
        }
    }

    /**
     * Split a (Simple)Block into its frames.
     *
     * @return list<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}>
     */
    private function parseBlock(string $block, int $clusterTimestamp, bool $simple): array
    {
        $offset = 0;
        $trackNumber = self::readBlockVint($block, $offset);
        if ($trackNumber === null || \strlen($block) < $offset + 3) {
            return [];
        }
        // A signed 16 bit offset from the cluster timestamp.
        /** @var int $relative */
        $relative = unpack('n', substr($block, $offset, 2))[1];
        if ($relative >= 0x8000) {
            $relative -= 0x10000;
        }
        $offset += 2;
        $flags = \ord($block[$offset++]);
        // For a BlockGroup the keyframe flag lives in the group, not the block; treating those
        // frames as non-key is safe, the decoder simply waits for the next real keyframe.
        $keyframe = $simple && ($flags & 0x80) !== 0;
        $lacing = ($flags >> 1) & 0x03;

        $payload = substr($block, $offset);
        $frames = $lacing === 0 ? [$payload] : self::unlace($payload, $lacing);

        $track = $this->tracks[$trackNumber] ?? ['codec' => '', 'type' => 0, 'private' => null];
        $timestamp = (int) (($clusterTimestamp + $relative) * $this->timestampScale / 1000000);

        $result = [];
        foreach ($frames as $frame) {
            if ($frame === '') {
                continue;
            }
            $result[] = [
                'track' => $trackNumber,
                'codec' => $track['codec'],
                'type' => $track['type'],
                'data' => $frame,
                'timestamp' => $timestamp,
                'keyframe' => $keyframe,
            ];
        }
        return $result;
    }

    /**
     * Undo Xiph, fixed size or EBML lacing.
     *
     * @return list<string>
     */
    private static function unlace(string $payload, int $lacing): array
    {
        if ($payload === '') {
            return [];
        }
        $offset = 0;
        $count = \ord($payload[$offset++]) + 1;
        $sizes = [];

        if ($lacing === 2) {
            // Fixed size lacing: every frame has the same length.
            $remaining = \strlen($payload) - $offset;
            $size = intdiv($remaining, $count);
            $sizes = array_fill(0, $count, $size);
        } elseif ($lacing === 1) {
            // Xiph lacing: sizes are encoded as a run of 255 bytes terminated by a smaller one.
            for ($i = 0; $i < $count - 1; $i++) {
                $size = 0;
                while ($offset < \strlen($payload)) {
                    $byte = \ord($payload[$offset++]);
                    $size += $byte;
                    if ($byte !== 255) {
                        break;
                    }
                }
                $sizes[] = $size;
            }
        } else {
            // EBML lacing: the first size is absolute, the rest are signed deltas.
            $first = self::readBlockVint($payload, $offset, false);
            if ($first === null) {
                return [$payload];
            }
            $sizes[] = $first;
            $previous = $first;
            for ($i = 1; $i < $count - 1; $i++) {
                $delta = self::readSignedBlockVint($payload, $offset);
                if ($delta === null) {
                    break;
                }
                $previous += $delta;
                $sizes[] = $previous;
            }
        }

        $frames = [];
        foreach ($sizes as $size) {
            $frames[] = substr($payload, $offset, $size);
            $offset += $size;
        }
        if ($lacing !== 2) {
            // The last frame takes whatever is left.
            $frames[] = substr($payload, $offset);
        }
        return $frames;
    }

    /**
     * Read a variable length integer out of an in-memory block.
     */
    private static function readBlockVint(string $data, int &$offset, bool $keepMarker = false): ?int
    {
        if ($offset >= \strlen($data)) {
            return null;
        }
        $first = \ord($data[$offset]);
        if ($first === 0) {
            return null;
        }
        $length = 1;
        for ($mask = 0x80; $mask > 0 && !($first & $mask); $mask >>= 1) {
            $length++;
        }
        if ($length > 8 || $offset + $length > \strlen($data)) {
            return null;
        }
        $value = $keepMarker ? $first : ($first & ((1 << (8 - $length)) - 1));
        for ($i = 1; $i < $length; $i++) {
            $value = ($value << 8) | \ord($data[$offset + $i]);
        }
        $offset += $length;
        return $value;
    }

    /**
     * Read a signed variable length integer, as used by EBML lacing deltas.
     */
    private static function readSignedBlockVint(string $data, int &$offset): ?int
    {
        $start = $offset;
        $value = self::readBlockVint($data, $offset);
        if ($value === null) {
            return null;
        }
        $length = $offset - $start;
        // The range is shifted so that it can express negative deltas.
        return $value - ((1 << (7 * $length - 1)) - 1);
    }

    /**
     * Decode a big endian unsigned integer of any width.
     */
    private static function toInt(string $data): int
    {
        $value = 0;
        for ($i = 0, $length = \strlen($data); $i < $length; $i++) {
            $value = ($value << 8) | \ord($data[$i]);
        }
        return $value;
    }
}
