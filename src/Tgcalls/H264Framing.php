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

/**
 * Rewrites the H.264 frames of a Matroska file into the byte stream RTP expects.
 *
 * Matroska stores H.264 the way MP4 does: each frame is a series of NAL units prefixed by their
 * length, and the parameter sets that describe the stream live once in the track's `CodecPrivate`
 * as an `AVCDecoderConfigurationRecord`. RTP instead wants the Annex B byte stream, where NAL units
 * are separated by start codes, and it wants the parameter sets repeated in-band: a group call
 * participant may start watching at any point, and without an SPS and PPS ahead of the first
 * keyframe they see nothing at all.
 *
 * Nothing is decoded here, only reframed, so H.264 files play without the FFI extension just like
 * VP8 and VP9 ones do.
 *
 * @internal
 */
final class H264Framing
{
    /** The four byte Annex B start code, used for every NAL unit we emit. */
    private const START_CODE = "\x00\x00\x00\x01";

    /** How many bytes prefix each NAL unit in the source frames. */
    private int $lengthSize = 4;

    /** The SPS and PPS of the track, already in Annex B form, or `''` if the track declared none. */
    private string $parameterSets = '';

    /** Whether the source frames are length prefixed, as opposed to being Annex B already. */
    private bool $lengthPrefixed = false;

    /**
     * @param string|null $codecPrivate The track's `CodecPrivate`, if it declared one.
     */
    public function __construct(?string $codecPrivate)
    {
        if ($codecPrivate === null || \strlen($codecPrivate) < 7 || $codecPrivate[0] !== "\x01") {
            // No AVCDecoderConfigurationRecord: the muxer stored a raw Annex B stream, which some
            // tools do even though the specification asks for the length prefixed form.
            return;
        }
        $this->lengthPrefixed = true;
        $this->lengthSize = (\ord($codecPrivate[4]) & 0x03) + 1;
        $this->parameterSets = self::parseParameterSets($codecPrivate);
    }

    /**
     * Whether frames need any rewriting at all.
     */
    public function isLengthPrefixed(): bool
    {
        return $this->lengthPrefixed;
    }

    /**
     * Convert one frame into an Annex B byte stream.
     *
     * @param string $frame    The frame as the demuxer produced it.
     * @param bool   $keyframe Whether the parameter sets have to be repeated before it.
     */
    public function convert(string $frame, bool $keyframe): string
    {
        if (!$this->lengthPrefixed) {
            return $keyframe ? $this->parameterSets.$frame : $frame;
        }

        $result = $keyframe ? $this->parameterSets : '';
        $offset = 0;
        $length = \strlen($frame);
        while ($offset + $this->lengthSize <= $length) {
            $size = self::readLength($frame, $offset, $this->lengthSize);
            $offset += $this->lengthSize;
            if ($size <= 0 || $offset + $size > $length) {
                // A truncated or nonsensical NAL unit: everything after it is unusable too.
                break;
            }
            $result .= self::START_CODE.substr($frame, $offset, $size);
            $offset += $size;
        }

        return $result;
    }

    /**
     * Extract the SPS and PPS of an `AVCDecoderConfigurationRecord`, as an Annex B stream.
     */
    private static function parseParameterSets(string $record): string
    {
        $result = '';
        $length = \strlen($record);
        // Byte 5 holds the SPS count in its low five bits, the top three are reserved and set.
        $offset = 5;
        $count = \ord($record[$offset++]) & 0x1F;

        // The PPS count is a full byte, and follows the last SPS.
        for ($set = 0; $set < 2; $set++) {
            for ($i = 0; $i < $count; $i++) {
                if ($offset + 2 > $length) {
                    return $result;
                }
                /** @var int $size */
                $size = unpack('n', substr($record, $offset, 2))[1];
                $offset += 2;
                if ($size === 0 || $offset + $size > $length) {
                    return $result;
                }
                $result .= self::START_CODE.substr($record, $offset, $size);
                $offset += $size;
            }
            if ($set === 0) {
                if ($offset >= $length) {
                    return $result;
                }
                $count = \ord($record[$offset++]);
            }
        }

        return $result;
    }

    /**
     * Read a big endian NAL unit length of `$size` bytes.
     */
    private static function readLength(string $frame, int $offset, int $size): int
    {
        $value = 0;
        for ($i = 0; $i < $size; $i++) {
            $value = ($value << 8) | \ord($frame[$offset + $i]);
        }
        return $value;
    }
}
