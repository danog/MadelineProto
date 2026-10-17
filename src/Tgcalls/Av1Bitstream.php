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
 * Pure-PHP inspection of AV1 temporal units in the low-overhead bitstream format (every OBU with
 * its size field), as the RTP depacketizer delivers them: reads the picture size out of the
 * sequence header and builds the `av1C` configuration record a Matroska `V_AV1` track needs.
 *
 * @internal
 */
final class Av1Bitstream
{
    public const OBU_SEQUENCE_HEADER = 1;
    public const OBU_TEMPORAL_DELIMITER = 2;

    /**
     * Find the sequence header OBU of a temporal unit (with its size field), or null.
     */
    public static function sequenceHeader(string $tu): ?string
    {
        foreach (self::obus($tu) as [$type, $obu]) {
            if ($type === self::OBU_SEQUENCE_HEADER) {
                return $obu;
            }
        }
        return null;
    }

    /**
     * Whether a temporal unit starts a coded video sequence, i.e. carries a sequence header (WebRTC
     * encoders emit one with every keyframe).
     */
    public static function isKeyframe(string $tu): bool
    {
        return self::sequenceHeader($tu) !== null;
    }

    /**
     * Describe the stream from a keyframe: picture size and the `av1C` record.
     *
     * @return array{int, int, string} [width, height, AV1CodecConfigurationRecord]
     */
    public static function describe(string $tu): array
    {
        $header = self::sequenceHeader($tu);
        if ($header === null) {
            return [1280, 720, ''];
        }
        try {
            $seq = self::parseSequenceHeader(self::obuPayload($header));
        } catch (\Throwable) {
            return [1280, 720, ''];
        }
        $av1c = "\x81"
            .\chr((($seq['profile'] & 0x07) << 5) | ($seq['level'] & 0x1F))
            .\chr((($seq['tier'] & 1) << 7) | (($seq['highBitdepth'] & 1) << 6) | (($seq['twelveBit'] & 1) << 5)
                | (($seq['monochrome'] & 1) << 4) | (($seq['subsamplingX'] & 1) << 3) | (($seq['subsamplingY'] & 1) << 2)
                | ($seq['chromaSamplePosition'] & 0x03))
            ."\x00"
            .$header;
        return [$seq['width'], $seq['height'], $av1c];
    }

    /**
     * The OBUs of a temporal unit, as [type, whole OBU bytes] pairs.
     *
     * @return list<array{int, string}>
     */
    public static function obus(string $tu): array
    {
        $result = [];
        $offset = 0;
        $length = \strlen($tu);
        while ($offset < $length) {
            $header = \ord($tu[$offset]);
            $type = ($header >> 3) & 0x0F;
            $headerLength = 1 + (($header & 0x04) ? 1 : 0);
            $cursor = $offset + $headerLength;
            if ($header & 0x02) {
                $size = self::leb128($tu, $cursor);
            } else {
                $size = $length - $cursor;
            }
            $end = $cursor + $size;
            if ($size < 0 || $end > $length) {
                break;
            }
            $result[] = [$type, substr($tu, $offset, $end - $offset)];
            $offset = $end;
        }
        return $result;
    }

    /**
     * The payload of an OBU (after its header, extension and size field).
     */
    private static function obuPayload(string $obu): string
    {
        $header = \ord($obu[0]);
        $cursor = 1 + (($header & 0x04) ? 1 : 0);
        if ($header & 0x02) {
            self::leb128($obu, $cursor);
        }
        return substr($obu, $cursor);
    }

    private static function leb128(string $data, int &$offset): int
    {
        $value = 0;
        $shift = 0;
        $length = \strlen($data);
        do {
            if ($offset >= $length) {
                return -1;
            }
            $byte = \ord($data[$offset++]);
            $value |= ($byte & 0x7F) << $shift;
            $shift += 7;
        } while (($byte & 0x80) && $shift < 56);
        return $value;
    }

    /**
     * Parse a sequence_header_obu() up to and including color_config().
     *
     * @return array{profile: int, level: int, tier: int, width: int, height: int, highBitdepth: int, twelveBit: int, monochrome: int, subsamplingX: int, subsamplingY: int, chromaSamplePosition: int}
     *
     * @psalm-mutation-free
     */
    private static function parseSequenceHeader(string $payload): array
    {
        $br = new BitReader($payload);
        $profile = $br->bits(3);
        $br->bits(1); // still_picture
        $reduced = $br->bits(1);
        $decoderModelInfoPresent = 0;
        $bufferDelayLength = 0;
        $initialDisplayDelayPresent = 0;
        $level = 0;
        $tier = 0;
        if ($reduced) {
            $level = $br->bits(5);
        } else {
            if ($br->bits(1)) { // timing_info_present_flag
                $br->bits(32); // num_units_in_display_tick
                $br->bits(32); // time_scale
                if ($br->bits(1)) { // equal_picture_interval
                    self::uvlc($br); // num_ticks_per_picture_minus_1
                }
                $decoderModelInfoPresent = $br->bits(1);
                if ($decoderModelInfoPresent) {
                    $bufferDelayLength = $br->bits(5) + 1;
                    $br->bits(32); // num_units_in_decoding_tick
                    $br->bits(5); // buffer_removal_time_length_minus_1
                    $br->bits(5); // frame_presentation_time_length_minus_1
                }
            }
            $initialDisplayDelayPresent = $br->bits(1);
            $operatingPoints = $br->bits(5) + 1;
            for ($i = 0; $i < $operatingPoints; $i++) {
                $br->bits(12); // operating_point_idc
                $opLevel = $br->bits(5);
                $opTier = $opLevel > 7 ? $br->bits(1) : 0;
                if ($i === 0) {
                    $level = $opLevel;
                    $tier = $opTier;
                }
                if ($decoderModelInfoPresent && $br->bits(1)) { // decoder_model_present_for_this_op
                    $br->bits($bufferDelayLength); // decoder_buffer_delay
                    $br->bits($bufferDelayLength); // encoder_buffer_delay
                    $br->bits(1); // low_delay_mode_flag
                }
                if ($initialDisplayDelayPresent && $br->bits(1)) { // initial_display_delay_present_for_this_op
                    $br->bits(4);
                }
            }
        }
        $widthBits = $br->bits(4) + 1;
        $heightBits = $br->bits(4) + 1;
        $width = $br->bits($widthBits) + 1;
        $height = $br->bits($heightBits) + 1;
        if (!$reduced && $br->bits(1)) { // frame_id_numbers_present_flag
            $br->bits(4); // delta_frame_id_length_minus_2
            $br->bits(3); // additional_frame_id_length_minus_1
        }
        $br->bits(1); // use_128x128_superblock
        $br->bits(1); // enable_filter_intra
        $br->bits(1); // enable_intra_edge_filter
        if (!$reduced) {
            $br->bits(1); // enable_interintra_compound
            $br->bits(1); // enable_masked_compound
            $br->bits(1); // enable_warped_motion
            $br->bits(1); // enable_dual_filter
            $enableOrderHint = $br->bits(1);
            if ($enableOrderHint) {
                $br->bits(1); // enable_jnt_comp
                $br->bits(1); // enable_ref_frame_mvs
            }
            $forceScreenContentTools = $br->bits(1) ? 2 : $br->bits(1); // seq_choose_screen_content_tools
            if ($forceScreenContentTools > 0) {
                if (!$br->bits(1)) { // seq_choose_integer_mv
                    $br->bits(1); // seq_force_integer_mv
                }
            }
            if ($enableOrderHint) {
                $br->bits(3); // order_hint_bits_minus_1
            }
        }
        $br->bits(1); // enable_superres
        $br->bits(1); // enable_cdef
        $br->bits(1); // enable_restoration
        // color_config()
        $highBitdepth = $br->bits(1);
        $twelveBit = ($profile === 2 && $highBitdepth) ? $br->bits(1) : 0;
        $monochrome = $profile === 1 ? 0 : $br->bits(1);
        $colorPrimaries = 2;
        $transfer = 2;
        $matrix = 2;
        if ($br->bits(1)) { // color_description_present_flag
            $colorPrimaries = $br->bits(8);
            $transfer = $br->bits(8);
            $matrix = $br->bits(8);
        }
        $subsamplingX = 1;
        $subsamplingY = 1;
        $chromaSamplePosition = 0;
        if ($monochrome) {
            $br->bits(1); // color_range
        } elseif ($colorPrimaries === 1 && $transfer === 13 && $matrix === 0) {
            $subsamplingX = 0;
            $subsamplingY = 0;
        } else {
            $br->bits(1); // color_range
            if ($profile === 0) {
                $subsamplingX = 1;
                $subsamplingY = 1;
            } elseif ($profile === 1) {
                $subsamplingX = 0;
                $subsamplingY = 0;
            } else {
                if ($twelveBit) {
                    $subsamplingX = $br->bits(1);
                    $subsamplingY = $subsamplingX ? $br->bits(1) : 0;
                } else {
                    $subsamplingX = 1;
                    $subsamplingY = 0;
                }
            }
            if ($subsamplingX && $subsamplingY) {
                $chromaSamplePosition = $br->bits(2);
            }
        }
        return [
            'profile' => $profile,
            'level' => $level,
            'tier' => $tier,
            'width' => $width,
            'height' => $height,
            'highBitdepth' => $highBitdepth,
            'twelveBit' => $twelveBit,
            'monochrome' => $monochrome,
            'subsamplingX' => $subsamplingX,
            'subsamplingY' => $subsamplingY,
            'chromaSamplePosition' => $chromaSamplePosition,
        ];
    }

    /**
     * Read a uvlc() (leading zeros count, then that many value bits) as the AV1 spec defines it.
     */
    private static function uvlc(BitReader $br): int
    {
        $zeros = 0;
        while ($zeros < 32 && $br->bits(1) === 0) {
            $zeros++;
        }
        if ($zeros >= 32) {
            return (1 << 32) - 1;
        }
        return $br->bits($zeros) + (1 << $zeros) - 1;
    }
}
