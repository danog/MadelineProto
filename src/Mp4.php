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
 * A pure PHP MP4 (ISO base media file format) demuxer, with the same shape as {@see Matroska}: it
 * reads the file sequentially and yields its encoded frames, one at a time, with their track, codec
 * (as a Matroska codec ID, so they can be muxed by {@see MatroskaWriter} as they are), type, timestamp
 * and keyframe flag.
 *
 * Both fragmented files (`moov` with `mvex`, then `moof`+`mdat` pairs, as served for group calls in
 * [stream mode](https://core.telegram.org/api/group-calls#stream-mode) and by every live/DASH
 * encoder) and progressive files whose `moov` precedes the `mdat` are supported. Progressive files
 * with the `moov` at the end cannot be demuxed sequentially.
 *
 * Supported codecs: H.264 (`avc1`/`avc3`), H.265 (`hvc1`/`hev1`), VP9 (`vp09`), AV1 (`av01`), AAC
 * (`mp4a`) and Opus (`Opus`). Video samples keep the length-prefixed NAL units of the file, which is
 * also what Matroska expects.
 */
final class Mp4
{
    public const TRACK_TYPE_VIDEO = Matroska::TRACK_TYPE_VIDEO;
    public const TRACK_TYPE_AUDIO = Matroska::TRACK_TYPE_AUDIO;

    /** Sample flag: the sample is not a sync (key) sample. */
    private const SAMPLE_NON_SYNC = 0x10000;
    private const TFHD_BASE_DATA_OFFSET = 0x1;
    private const TFHD_DEFAULT_SAMPLE_DURATION = 0x8;
    private const TFHD_DEFAULT_SAMPLE_SIZE = 0x10;
    private const TFHD_DEFAULT_SAMPLE_FLAGS = 0x20;
    private const TFHD_DEFAULT_BASE_IS_MOOF = 0x20000;
    private const TRUN_DATA_OFFSET = 0x1;
    private const TRUN_FIRST_SAMPLE_FLAGS = 0x4;
    private const TRUN_SAMPLE_DURATION = 0x100;
    private const TRUN_SAMPLE_SIZE = 0x200;
    private const TRUN_SAMPLE_FLAGS = 0x400;
    private const TRUN_SAMPLE_CTS = 0x800;

    /**
     * The frames of the file, in file order.
     *
     * Each entry is `['track' => int, 'codec' => string, 'type' => int, 'data' => string,
     * 'timestamp' => int (ms), 'keyframe' => bool]`, like {@see Matroska::$frames}.
     *
     * @var iterable<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}>
     */
    public readonly iterable $frames;

    /**
     * The tracks of the file, by track ID: `codec` (a Matroska codec ID), `type`, `private` (the
     * Matroska CodecPrivate: avcC/hvcC/vpcC/av1C, the AAC AudioSpecificConfig, or an OpusHead), the
     * video `width`/`height`, the audio `rate`/`channels`, and the media `timescale`.
     *
     * @var array<int, array{codec: string, type: int, private: ?string, width: int, height: int, rate: int, channels: int, timescale: int}>
     */
    public array $tracks = [];

    /** @var Closure(int): ?string Reads exactly the requested number of bytes, or null at EOF. */
    private Closure $read;
    /** Absolute offset of the next byte to be read. */
    private int $position = 0;

    /**
     * @var array<int, array{duration: int, size: int, flags: int}> Track fragment defaults (trex), by track ID.
     */
    private array $trex = [];

    public function __construct(
        LocalFile|RemoteUrl|ReadableStream $stream,
        ?Cancellation $cancellation = null,
    ) {
        $this->read = Tools::openBuffered($stream, $cancellation);
        $it = $this->demux();
        // Prime the generator so that the track list is populated before the caller iterates.
        $it->current();
        $this->frames = $it;
    }

    /**
     * Whether the file has a track with the given (Matroska) codec ID.
     *
     * @psalm-mutation-free
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
     * Read exactly `$length` bytes, or throw at EOF.
     */
    private function consume(int $length): string
    {
        if ($length === 0) {
            return '';
        }
        $data = ($this->read)($length);
        if ($data === null || \strlen($data) !== $length) {
            throw new Exception('Unexpected end of MP4 file');
        }
        $this->position += $length;
        return $data;
    }

    /**
     * Read the next top-level box header: its type and payload size, or null at EOF.
     *
     * @return array{type: string, size: int, start: int}|null
     */
    private function nextBox(): ?array
    {
        $start = $this->position;
        $header = ($this->read)(8);
        if ($header === null) {
            return null;
        }
        if (\strlen($header) !== 8) {
            throw new Exception('Truncated MP4 box header');
        }
        $this->position += 8;
        $size = self::u32($header, 0);
        $type = substr($header, 4, 4);
        $headerSize = 8;
        if ($size === 1) {
            $large = $this->consume(8);
            $size = self::u64($large, 0);
            $headerSize = 16;
        } elseif ($size === 0) {
            $size = PHP_INT_MAX; // to the end of the file
        }
        return ['type' => $type, 'size' => $size === PHP_INT_MAX ? -1 : $size - $headerSize, 'start' => $start];
    }

    /**
     * @return Generator<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}>
     */
    private function demux(): Generator
    {
        /** @var array<int, array{offset: int, size: int, track: int, timestamp: int, keyframe: bool}> Samples of a progressive file, by absolute offset. */
        $progressive = [];
        $moofStart = 0;
        /** @var array<int, array{offset: int, size: int, track: int, timestamp: int, keyframe: bool}> Samples described by the last moof, by absolute offset. */
        $pending = [];
        while (($box = $this->nextBox()) !== null) {
            switch ($box['type']) {
                case 'moov':
                    $this->parseMoov($this->consume($box['size']), $progressive);
                    if ($progressive !== []) {
                        ksort($progressive);
                    }
                    break;
                case 'moof':
                    $moofStart = $box['start'];
                    $pending = $this->parseMoof($this->consume($box['size']), $moofStart);
                    ksort($pending);
                    break;
                case 'mdat':
                    $dataStart = $this->position;
                    $samples = $pending !== [] ? $pending : $progressive;
                    $pending = [];
                    if ($samples === []) {
                        $this->skip($box['size']);
                        break;
                    }
                    // Samples are laid out in file order: read the mdat sequentially, yielding each one.
                    foreach ($samples as $offset => $sample) {
                        if ($offset < $this->position) {
                            continue; // overlapping/duplicate description, or before this mdat
                        }
                        if ($box['size'] >= 0 && $offset + $sample['size'] > $dataStart + $box['size']) {
                            break; // belongs to a later mdat
                        }
                        $this->skip($offset - $this->position);
                        $data = $this->consume($sample['size']);
                        $track = $this->tracks[$sample['track']] ?? null;
                        if ($track === null) {
                            continue;
                        }
                        yield [
                            'track' => $sample['track'],
                            'codec' => $track['codec'],
                            'type' => $track['type'],
                            'data' => $data,
                            'timestamp' => $sample['timestamp'],
                            'keyframe' => $sample['keyframe'],
                        ];
                        unset($progressive[$offset]);
                    }
                    if ($box['size'] >= 0) {
                        $this->skip($dataStart + $box['size'] - $this->position);
                    }
                    break;
                default:
                    if ($box['size'] < 0) {
                        return;
                    }
                    $this->skip($box['size']);
            }
        }
    }

    private function skip(int $length): void
    {
        while ($length > 0) {
            $chunk = min($length, 1 << 20);
            $this->consume($chunk);
            $length -= $chunk;
        }
    }

    /* ------------------------------------------------------------------ *
     *  moov: tracks, codecs, fragment defaults, and progressive sample tables.
     * ------------------------------------------------------------------ */

    /**
     * @param array<int, array{offset: int, size: int, track: int, timestamp: int, keyframe: bool}> $progressive
     */
    private function parseMoov(string $moov, array &$progressive): void
    {
        foreach (self::children($moov) as [$type, $payload]) {
            if ($type === 'trak') {
                $this->parseTrak($payload, $progressive);
            } elseif ($type === 'mvex') {
                foreach (self::children($payload) as [$childType, $child]) {
                    if ($childType === 'trex' && \strlen($child) >= 24) {
                        $this->trex[self::u32($child, 4)] = [
                            'duration' => self::u32($child, 12),
                            'size' => self::u32($child, 16),
                            'flags' => self::u32($child, 20),
                        ];
                    }
                }
            }
        }
    }

    /**
     * @param array<int, array{offset: int, size: int, track: int, timestamp: int, keyframe: bool}> $progressive
     */
    private function parseTrak(string $trak, array &$progressive): void
    {
        $trackId = 0;
        $width = 0;
        $height = 0;
        $timescale = 1000;
        $handler = '';
        $stbl = null;
        foreach (self::children($trak) as [$type, $payload]) {
            if ($type === 'tkhd') {
                $version = \ord($payload[0]);
                $trackId = self::u32($payload, $version === 1 ? 20 : 12);
                $width = self::u32($payload, \strlen($payload) - 8) >> 16;
                $height = self::u32($payload, \strlen($payload) - 4) >> 16;
            } elseif ($type === 'mdia') {
                foreach (self::children($payload) as [$mediaType, $media]) {
                    if ($mediaType === 'mdhd') {
                        $version = \ord($media[0]);
                        $timescale = self::u32($media, $version === 1 ? 20 : 12);
                    } elseif ($mediaType === 'hdlr') {
                        $handler = substr($media, 8, 4);
                    } elseif ($mediaType === 'minf') {
                        foreach (self::children($media) as [$minfType, $minf]) {
                            if ($minfType === 'stbl') {
                                $stbl = $minf;
                            }
                        }
                    }
                }
            }
        }
        if ($stbl === null || $trackId === 0) {
            return;
        }
        $trackType = match ($handler) {
            'vide' => self::TRACK_TYPE_VIDEO,
            'soun' => self::TRACK_TYPE_AUDIO,
            default => 0,
        };
        if ($trackType === 0) {
            return;
        }
        $tables = [];
        foreach (self::children($stbl) as [$type, $payload]) {
            $tables[$type] = $payload;
        }
        if (!isset($tables['stsd'])) {
            return;
        }
        $entry = self::parseStsd($tables['stsd'], $trackType);
        if ($entry === null) {
            return;
        }
        $this->tracks[$trackId] = [
            'codec' => $entry['codec'],
            'type' => $trackType,
            'private' => $entry['private'],
            'width' => $entry['width'] ?: $width,
            'height' => $entry['height'] ?: $height,
            'rate' => $entry['rate'],
            'channels' => $entry['channels'],
            'timescale' => max(1, $timescale),
        ];
        if (isset($tables['stco']) || isset($tables['co64'])) {
            $this->parseSampleTables($trackId, $tables, $progressive);
        }
    }

    /**
     * The first sample entry of a sample description box: the codec, its private data and format.
     *
     * @return array{codec: string, private: ?string, width: int, height: int, rate: int, channels: int}|null
     *
     * @psalm-pure
     */
    private static function parseStsd(string $stsd, int $trackType): ?array
    {
        if (\strlen($stsd) < 16 || self::u32($stsd, 4) === 0) {
            return null;
        }
        $entry = substr($stsd, 8);
        $size = self::u32($entry, 0);
        $format = substr($entry, 4, 4);
        $entry = substr($entry, 8, max(0, $size - 8));
        $result = ['codec' => '', 'private' => null, 'width' => 0, 'height' => 0, 'rate' => 0, 'channels' => 0];
        if ($trackType === self::TRACK_TYPE_VIDEO) {
            // VisualSampleEntry: 6 reserved, data_reference_index(2), 16 predefined/reserved, width(2), height(2), ...(50 more)
            $result['width'] = self::u16($entry, 24);
            $result['height'] = self::u16($entry, 26);
            $boxes = self::children(substr($entry, 78));
            foreach ($boxes as [$type, $payload]) {
                switch ($type) {
                    case 'avcC':
                        $result['codec'] = 'V_MPEG4/ISO/AVC';
                        $result['private'] = $payload;
                        break;
                    case 'hvcC':
                        $result['codec'] = 'V_MPEGH/ISO/HEVC';
                        $result['private'] = $payload;
                        break;
                    case 'vpcC':
                        $result['codec'] = $format === 'vp08' ? 'V_VP8' : 'V_VP9';
                        $result['private'] = substr($payload, 4); // drop the full-box header
                        break;
                    case 'av1C':
                        $result['codec'] = 'V_AV1';
                        $result['private'] = $payload;
                        break;
                }
            }
            if ($result['codec'] === '') {
                $result['codec'] = match ($format) {
                    'avc1', 'avc3' => 'V_MPEG4/ISO/AVC',
                    'hvc1', 'hev1' => 'V_MPEGH/ISO/HEVC',
                    'vp08' => 'V_VP8',
                    'vp09' => 'V_VP9',
                    'av01' => 'V_AV1',
                    default => '',
                };
            }
        } else {
            // AudioSampleEntry: 6 reserved, data_reference_index(2), version(2), revision(2), vendor(4), channels(2), samplesize(2), predefined(2), reserved(2), samplerate(4, 16.16)
            $version = self::u16($entry, 8);
            $result['channels'] = self::u16($entry, 16);
            $result['rate'] = self::u32($entry, 24) >> 16;
            $boxesOffset = 28;
            if ($version === 1) {
                $boxesOffset += 16;
            } elseif ($version === 2) {
                $boxesOffset = 64;
            }
            foreach (self::children(substr($entry, $boxesOffset)) as [$type, $payload]) {
                if ($type === 'esds') {
                    $result['codec'] = 'A_AAC';
                    $result['private'] = self::audioSpecificConfig($payload);
                } elseif ($type === 'dOps') {
                    $result['codec'] = 'A_OPUS';
                    $result['private'] = self::opusHeadFromDops($payload);
                    $result['rate'] = 48000;
                    $result['channels'] = \ord($payload[1]);
                }
            }
            if ($result['codec'] === '') {
                $result['codec'] = match ($format) {
                    'mp4a' => 'A_AAC',
                    'Opus' => 'A_OPUS',
                    default => '',
                };
            }
        }
        return $result['codec'] === '' ? null : $result;
    }

    /**
     * The AudioSpecificConfig (DecoderSpecificInfo, tag 5) inside an esds box.
     *
     * @psalm-pure
     */
    private static function audioSpecificConfig(string $esds): ?string
    {
        $offset = 4; // full-box header
        $length = \strlen($esds);
        while ($offset < $length) {
            $tag = \ord($esds[$offset++]);
            $size = 0;
            for ($i = 0; $i < 4 && $offset < $length; $i++) {
                $byte = \ord($esds[$offset++]);
                $size = ($size << 7) | ($byte & 0x7F);
                if (($byte & 0x80) === 0) {
                    break;
                }
            }
            if ($tag === 0x05) {
                return substr($esds, $offset, $size);
            }
            if ($tag === 0x03) {
                // ES_Descriptor: ES_ID(2), flags(1) [+ dependsOn(2)] [+ URL] [+ OCR(2)], then the DecoderConfigDescriptor
                $flags = \ord($esds[$offset + 2]);
                $offset += 3;
                if ($flags & 0x80) {
                    $offset += 2;
                }
                if ($flags & 0x40) {
                    $offset += 1 + \ord($esds[$offset]);
                }
                if ($flags & 0x20) {
                    $offset += 2;
                }
                continue;
            }
            if ($tag === 0x04) {
                // DecoderConfigDescriptor: 13 bytes, then the DecoderSpecificInfo
                $offset += 13;
                continue;
            }
            $offset += $size;
        }
        return null;
    }

    /**
     * Build an OpusHead (the Matroska CodecPrivate) from an OpusSpecificBox (dOps), which carries the
     * same fields big-endian and without the magic.
     *
     * @psalm-pure
     */
    private static function opusHeadFromDops(string $dops): string
    {
        $channels = \ord($dops[1]);
        $preSkip = self::u16($dops, 2);
        $rate = self::u32($dops, 4);
        $gain = self::u16($dops, 8);
        $family = \ord($dops[10]);
        $head = 'OpusHead'.pack('CCvVvC', 1, $channels, $preSkip, $rate, $gain, $family);
        if ($family !== 0) {
            $head .= substr($dops, 11);
        }
        return $head;
    }

    /**
     * Turn the sample tables of a progressive file into absolute-offset samples.
     *
     * @param array<string, string> $tables
     * @param array<int, array{offset: int, size: int, track: int, timestamp: int, keyframe: bool}> $progressive
     */
    private function parseSampleTables(int $trackId, array $tables, array &$progressive): void
    {
        $timescale = $this->tracks[$trackId]['timescale'];
        // Chunk offsets.
        $chunkOffsets = [];
        if (isset($tables['co64'])) {
            $count = self::u32($tables['co64'], 4);
            for ($i = 0; $i < $count; $i++) {
                $chunkOffsets[] = self::u64($tables['co64'], 8 + $i * 8);
            }
        } elseif (isset($tables['stco'])) {
            $count = self::u32($tables['stco'], 4);
            for ($i = 0; $i < $count; $i++) {
                $chunkOffsets[] = self::u32($tables['stco'], 8 + $i * 4);
            }
        }
        // Sample sizes.
        $sizes = [];
        $sampleCount = 0;
        if (isset($tables['stsz'])) {
            $uniform = self::u32($tables['stsz'], 4);
            $sampleCount = self::u32($tables['stsz'], 8);
            for ($i = 0; $i < $sampleCount; $i++) {
                $sizes[] = $uniform !== 0 ? $uniform : self::u32($tables['stsz'], 12 + $i * 4);
            }
        }
        // Samples per chunk.
        $stsc = [];
        if (isset($tables['stsc'])) {
            $count = self::u32($tables['stsc'], 4);
            for ($i = 0; $i < $count; $i++) {
                $stsc[] = [self::u32($tables['stsc'], 8 + $i * 12), self::u32($tables['stsc'], 12 + $i * 12)];
            }
        }
        // Durations.
        $durations = [];
        if (isset($tables['stts'])) {
            $count = self::u32($tables['stts'], 4);
            for ($i = 0; $i < $count; $i++) {
                $n = self::u32($tables['stts'], 8 + $i * 8);
                $delta = self::u32($tables['stts'], 12 + $i * 8);
                for ($j = 0; $j < $n; $j++) {
                    $durations[] = $delta;
                }
            }
        }
        // Sync samples.
        $sync = null;
        if (isset($tables['stss'])) {
            $sync = [];
            $count = self::u32($tables['stss'], 4);
            for ($i = 0; $i < $count; $i++) {
                $sync[self::u32($tables['stss'], 8 + $i * 4)] = true;
            }
        }
        $sample = 0;
        $time = 0;
        $chunkCount = \count($chunkOffsets);
        foreach ($chunkOffsets as $chunkIndex => $chunkOffset) {
            $perChunk = 0;
            foreach ($stsc as [$firstChunk, $samplesPerChunk]) {
                if ($firstChunk <= $chunkIndex + 1) {
                    $perChunk = $samplesPerChunk;
                }
            }
            $offset = $chunkOffset;
            for ($i = 0; $i < $perChunk && $sample < $sampleCount; $i++, $sample++) {
                $size = $sizes[$sample];
                $progressive[$offset] = [
                    'offset' => $offset,
                    'size' => $size,
                    'track' => $trackId,
                    'timestamp' => intdiv($time * 1000, $timescale),
                    'keyframe' => $sync === null || isset($sync[$sample + 1]),
                ];
                $time += $durations[$sample] ?? 0;
                $offset += $size;
            }
            if ($chunkIndex + 1 >= $chunkCount) {
                break;
            }
        }
    }

    /* ------------------------------------------------------------------ *
     *  moof: fragment runs.
     * ------------------------------------------------------------------ */

    /**
     * The samples described by a movie fragment, by absolute file offset.
     *
     * @return array<int, array{offset: int, size: int, track: int, timestamp: int, keyframe: bool}>
     *
     * @psalm-mutation-free
     */
    private function parseMoof(string $moof, int $moofStart): array
    {
        $samples = [];
        foreach (self::children($moof) as [$type, $payload]) {
            if ($type !== 'traf') {
                continue;
            }
            $trackId = 0;
            $baseOffset = $moofStart;
            $defaults = ['duration' => 0, 'size' => 0, 'flags' => 0];
            $decodeTime = 0;
            $runs = [];
            foreach (self::children($payload) as [$childType, $child]) {
                if ($childType === 'tfhd') {
                    $flags = self::u32($child, 0) & 0xFFFFFF;
                    $trackId = self::u32($child, 4);
                    $defaults = $this->trex[$trackId] ?? $defaults;
                    $offset = 8;
                    if ($flags & self::TFHD_BASE_DATA_OFFSET) {
                        $baseOffset = self::u64($child, $offset);
                        $offset += 8;
                    }
                    if ($flags & 0x2) { // sample description index
                        $offset += 4;
                    }
                    if ($flags & self::TFHD_DEFAULT_SAMPLE_DURATION) {
                        $defaults['duration'] = self::u32($child, $offset);
                        $offset += 4;
                    }
                    if ($flags & self::TFHD_DEFAULT_SAMPLE_SIZE) {
                        $defaults['size'] = self::u32($child, $offset);
                        $offset += 4;
                    }
                    if ($flags & self::TFHD_DEFAULT_SAMPLE_FLAGS) {
                        $defaults['flags'] = self::u32($child, $offset);
                    }
                } elseif ($childType === 'tfdt') {
                    $version = \ord($child[0]);
                    $decodeTime = $version === 1 ? self::u64($child, 4) : self::u32($child, 4);
                } elseif ($childType === 'trun') {
                    $runs[] = $child;
                }
            }
            $track = $this->tracks[$trackId] ?? null;
            if ($track === null) {
                continue;
            }
            $timescale = $track['timescale'];
            $time = $decodeTime;
            $offset = $baseOffset;
            foreach ($runs as $run) {
                $flags = self::u32($run, 0) & 0xFFFFFF;
                $count = self::u32($run, 4);
                $pos = 8;
                if ($flags & self::TRUN_DATA_OFFSET) {
                    $offset = $baseOffset + self::s32($run, $pos);
                    $pos += 4;
                }
                $firstFlags = null;
                if ($flags & self::TRUN_FIRST_SAMPLE_FLAGS) {
                    $firstFlags = self::u32($run, $pos);
                    $pos += 4;
                }
                for ($i = 0; $i < $count; $i++) {
                    $duration = $defaults['duration'];
                    $size = $defaults['size'];
                    $sampleFlags = $i === 0 && $firstFlags !== null ? $firstFlags : $defaults['flags'];
                    if ($flags & self::TRUN_SAMPLE_DURATION) {
                        $duration = self::u32($run, $pos);
                        $pos += 4;
                    }
                    if ($flags & self::TRUN_SAMPLE_SIZE) {
                        $size = self::u32($run, $pos);
                        $pos += 4;
                    }
                    if ($flags & self::TRUN_SAMPLE_FLAGS) {
                        $sampleFlags = self::u32($run, $pos);
                        $pos += 4;
                    }
                    if ($flags & self::TRUN_SAMPLE_CTS) {
                        $pos += 4;
                    }
                    $samples[$offset] = [
                        'offset' => $offset,
                        'size' => $size,
                        'track' => $trackId,
                        'timestamp' => intdiv($time * 1000, $timescale),
                        'keyframe' => ($sampleFlags & self::SAMPLE_NON_SYNC) === 0,
                    ];
                    $offset += $size;
                    $time += $duration;
                }
            }
        }
        return $samples;
    }

    /* ------------------------------------------------------------------ *
     *  Box and integer helpers.
     * ------------------------------------------------------------------ */

    /**
     * The child boxes of a box payload, as `[type, payload]` pairs.
     *
     * @return list<array{0: string, 1: string}>
     *
     * @psalm-pure
     */
    private static function children(string $data): array
    {
        $boxes = [];
        $offset = 0;
        $length = \strlen($data);
        while ($offset + 8 <= $length) {
            $size = self::u32($data, $offset);
            $type = substr($data, $offset + 4, 4);
            $headerSize = 8;
            if ($size === 1 && $offset + 16 <= $length) {
                $size = self::u64($data, $offset + 8);
                $headerSize = 16;
            } elseif ($size === 0) {
                $size = $length - $offset;
            }
            if ($size < $headerSize) {
                break;
            }
            $boxes[] = [$type, substr($data, $offset + $headerSize, $size - $headerSize)];
            $offset += $size;
        }
        return $boxes;
    }

    /** @psalm-pure */
    private static function u16(string $data, int $offset): int
    {
        return \strlen($data) >= $offset + 2 ? (int) unpack('n', substr($data, $offset, 2))[1] : 0;
    }

    /** @psalm-pure */
    private static function u32(string $data, int $offset): int
    {
        return \strlen($data) >= $offset + 4 ? (int) unpack('N', substr($data, $offset, 4))[1] : 0;
    }

    /** @psalm-pure */
    private static function s32(string $data, int $offset): int
    {
        $value = self::u32($data, $offset);
        return $value >= 0x80000000 ? $value - 0x100000000 : $value;
    }

    /** @psalm-pure */
    private static function u64(string $data, int $offset): int
    {
        return \strlen($data) >= $offset + 8 ? (int) unpack('J', substr($data, $offset, 8))[1] : 0;
    }
}
