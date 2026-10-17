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
 * Pure-PHP inspection of H.265 (HEVC) Annex B access units, as the RTP depacketizer delivers them:
 * tells them apart from H.264, reads the picture size out of the SPS, builds the
 * HEVCDecoderConfigurationRecord (`hvcC`) a Matroska `V_MPEGH/ISO/HEVC` track needs, and converts
 * the access unit to the length-prefixed form the container stores.
 *
 * @internal
 */
final class HevcBitstream
{
    public const NAL_VPS = 32;
    public const NAL_SPS = 33;
    public const NAL_PPS = 34;
    public const NAL_AUD = 35;
    /** The IRAP range: BLA_W_LP .. CRA_NUT, the pictures a decoder can start on. */
    private const NAL_IRAP_FIRST = 16;
    private const NAL_IRAP_LAST = 21;

    /**
     * Whether an Annex B access unit is H.265 rather than H.264.
     *
     * The NAL unit headers differ in layout (H.264: 1 byte, H.265: 2 bytes whose second is 0x01 on
     * the base layer), so a parameter set is unambiguous: an H.264 SPS starts with 0x67, an H.265
     * VPS/SPS/PPS with 0x40/0x42/0x44 followed by 0x01. Keyframes carry their parameter sets in-band
     * in WebRTC, so this is decided on the frame that describes the track.
     *
     * @psalm-pure
     */
    public static function isHevc(string $frame): bool
    {
        foreach (self::nalUnits($frame) as $nal) {
            if (\strlen($nal) < 2) {
                continue;
            }
            $type = self::nalType($nal);
            if (\in_array($type, [self::NAL_VPS, self::NAL_SPS, self::NAL_PPS], true) && $nal[1] === "\x01") {
                return true;
            }
            if ((\ord($nal[0]) & 0x1F) === 7 && (\ord($nal[0]) & 0x60) !== 0) {
                return false; // An H.264 SPS.
            }
        }
        return false;
    }

    /**
     * The type of an H.265 NAL unit.
     *
     * @psalm-pure
     */
    public static function nalType(string $nal): int
    {
        return (\ord($nal[0]) >> 1) & 0x3F;
    }

    /**
     * Whether an access unit is a keyframe: an IRAP picture, or one carrying parameter sets.
     *
     * @psalm-pure
     */
    public static function isKeyframe(string $frame): bool
    {
        foreach (self::nalUnits($frame) as $nal) {
            if ($nal === '') {
                continue;
            }
            $type = self::nalType($nal);
            if (($type >= self::NAL_IRAP_FIRST && $type <= self::NAL_IRAP_LAST) || $type === self::NAL_SPS) {
                return true;
            }
        }
        return false;
    }

    /**
     * Describe the stream from a keyframe: picture size and the `hvcC` configuration record.
     *
     * @return array{int, int, string} [width, height, HEVCDecoderConfigurationRecord]
     *
     * @psalm-mutation-free
     */
    public static function describe(string $frame): array
    {
        $sets = [self::NAL_VPS => '', self::NAL_SPS => '', self::NAL_PPS => ''];
        foreach (self::nalUnits($frame) as $nal) {
            if (\strlen($nal) < 3) {
                continue;
            }
            $type = self::nalType($nal);
            if (isset($sets[$type]) && $sets[$type] === '') {
                $sets[$type] = $nal;
            }
        }
        if ($sets[self::NAL_SPS] === '') {
            return [1280, 720, ''];
        }
        $sps = self::parseSps($sets[self::NAL_SPS]);
        return [$sps['width'], $sps['height'], self::hvcc($sps, $sets)];
    }

    /**
     * Convert an Annex B access unit to 4-byte length-prefixed NAL units, dropping access unit
     * delimiters (the container does not store them).
     *
     * @psalm-pure
     */
    public static function toLengthPrefixed(string $frame): string
    {
        $out = '';
        foreach (self::nalUnits($frame) as $nal) {
            if ($nal === '' || self::nalType($nal) === self::NAL_AUD) {
                continue;
            }
            $out .= pack('N', \strlen($nal)).$nal;
        }
        return $out;
    }

    /**
     * Split an Annex B stream into NAL units (without start codes).
     *
     * @return list<string>
     *
     * @psalm-pure
     */
    public static function nalUnits(string $frame): array
    {
        $nals = [];
        $len = \strlen($frame);
        $i = 0;
        $start = -1;
        while ($i + 3 <= $len) {
            if ($frame[$i] === "\x00" && $frame[$i + 1] === "\x00" && $frame[$i + 2] === "\x01") {
                if ($start >= 0) {
                    $end = $i;
                    if ($end > $start && $frame[$end - 1] === "\x00") {
                        $end--;
                    }
                    $nals[] = substr($frame, $start, $end - $start);
                }
                $i += 3;
                $start = $i;
                continue;
            }
            $i++;
        }
        if ($start >= 0 && $start <= $len) {
            $nals[] = substr($frame, $start);
        }
        return $nals;
    }

    /**
     * Read the fields of an SPS that the container needs.
     *
     * @return array{width: int, height: int, chroma: int, bitDepthLuma: int, bitDepthChroma: int, ptl: string, subLayers: int, nested: int}
     *
     * @psalm-mutation-free
     */
    private static function parseSps(string $sps): array
    {
        $rbsp = self::stripEmulationPrevention(substr($sps, 2));
        $br = new BitReader($rbsp);
        $br->bits(4); // sps_video_parameter_set_id
        $maxSubLayersMinus1 = $br->bits(3);
        $nested = $br->bits(1);
        // profile_tier_level(1, sps_max_sub_layers_minus1): the general part is byte aligned here,
        // 12 bytes: profile space/tier/idc (1), compatibility flags (4), constraint flags (6), level (1).
        $ptl = substr($rbsp, 1, 12);
        $br->bits(8); // profile_space, tier, profile_idc
        $br->bits(32); // general_profile_compatibility_flags
        $br->bits(48); // general constraint flags
        $br->bits(8); // general_level_idc
        $profilePresent = [];
        $levelPresent = [];
        for ($i = 0; $i < $maxSubLayersMinus1; $i++) {
            $profilePresent[$i] = $br->bits(1);
            $levelPresent[$i] = $br->bits(1);
        }
        if ($maxSubLayersMinus1 > 0) {
            for ($i = $maxSubLayersMinus1; $i < 8; $i++) {
                $br->bits(2); // reserved_zero_2bits
            }
        }
        for ($i = 0; $i < $maxSubLayersMinus1; $i++) {
            if ($profilePresent[$i]) {
                $br->bits(88);
            }
            if ($levelPresent[$i]) {
                $br->bits(8);
            }
        }
        $br->ue(); // sps_seq_parameter_set_id
        $chroma = $br->ue();
        if ($chroma === 3) {
            $br->bits(1); // separate_colour_plane_flag
        }
        $width = $br->ue();
        $height = $br->ue();
        if ($br->bits(1)) { // conformance_window_flag
            $left = $br->ue();
            $right = $br->ue();
            $top = $br->ue();
            $bottom = $br->ue();
            $subWidth = ($chroma === 1 || $chroma === 2) ? 2 : 1;
            $subHeight = $chroma === 1 ? 2 : 1;
            $width -= ($left + $right) * $subWidth;
            $height -= ($top + $bottom) * $subHeight;
        }
        $bitDepthLuma = $br->ue() + 8;
        $bitDepthChroma = $br->ue() + 8;
        return [
            'width' => max(1, $width),
            'height' => max(1, $height),
            'chroma' => $chroma,
            'bitDepthLuma' => $bitDepthLuma,
            'bitDepthChroma' => $bitDepthChroma,
            'ptl' => str_pad($ptl, 12, "\x00"),
            'subLayers' => $maxSubLayersMinus1 + 1,
            'nested' => $nested,
        ];
    }

    /**
     * Build the HEVCDecoderConfigurationRecord (ISO/IEC 14496-15 8.3.3.1).
     *
     * @param array{width: int, height: int, chroma: int, bitDepthLuma: int, bitDepthChroma: int, ptl: string, subLayers: int, nested: int} $sps
     * @param array<int, string> $sets The VPS, SPS and PPS NAL units, keyed by type ('' if missing).
     *
     * @psalm-pure
     */
    private static function hvcc(array $sps, array $sets): string
    {
        $record = "\x01".$sps['ptl'];
        $record .= "\xF0\x00"; // min_spatial_segmentation_idc = 0 (with reserved bits set)
        $record .= "\xFC"; // parallelismType = 0
        $record .= \chr(0xFC | ($sps['chroma'] & 0x03));
        $record .= \chr(0xF8 | (($sps['bitDepthLuma'] - 8) & 0x07));
        $record .= \chr(0xF8 | (($sps['bitDepthChroma'] - 8) & 0x07));
        $record .= "\x00\x00"; // avgFrameRate
        // constantFrameRate = 0, numTemporalLayers, temporalIdNested, lengthSizeMinusOne = 3.
        $record .= \chr((($sps['subLayers'] & 0x07) << 3) | (($sps['nested'] & 0x01) << 2) | 0x03);
        $arrays = array_filter($sets, static fn (string $nal): bool => $nal !== '');
        $record .= \chr(\count($arrays));
        foreach ($arrays as $type => $nal) {
            $record .= \chr(0x80 | ($type & 0x3F)).pack('n', 1).pack('n', \strlen($nal)).$nal;
        }
        return $record;
    }

    /**
     * @psalm-pure
     */
    private static function stripEmulationPrevention(string $data): string
    {
        return preg_replace('/\x00\x00\x03/', "\x00\x00", $data) ?? $data;
    }
}
