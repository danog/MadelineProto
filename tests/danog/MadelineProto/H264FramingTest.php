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

use danog\MadelineProto\Tgcalls\H264Framing;
use PHPUnit\Framework\TestCase;

/**
 * Tests the reframing that lets the H.264 of a Matroska file be sent over RTP.
 *
 * @internal
 */
final class H264FramingTest extends TestCase
{
    private const START = "\x00\x00\x00\x01";

    /** An SPS and a PPS NAL unit, as they would appear in a real track. */
    private const SPS = "\x67\x42\xE0\x1F\x8C\x8D\x40";
    private const PPS = "\x68\xCE\x3C\x80";

    /**
     * Build an AVCDecoderConfigurationRecord holding the parameter sets above.
     *
     * @param int $lengthSize How many bytes prefix each NAL unit, 1 to 4.
     */
    private static function avcC(int $lengthSize = 4): string
    {
        return "\x01"                                    // configurationVersion
            ."\x42\xE0\x1F"                              // profile, compatibility, level
            .\chr(0xFC | ($lengthSize - 1))               // reserved bits + lengthSizeMinusOne
            ."\xE1"                                      // reserved bits + one SPS
            .pack('n', \strlen(self::SPS)).self::SPS
            ."\x01"                                      // one PPS
            .pack('n', \strlen(self::PPS)).self::PPS;
    }

    /**
     * Prefix a NAL unit with its length, the way Matroska and MP4 store it.
     */
    private static function lengthPrefixed(string $nal, int $lengthSize = 4): string
    {
        return substr(pack('N', \strlen($nal)), 4 - $lengthSize).$nal;
    }

    public function testLengthPrefixedFramesBecomeAnnexB(): void
    {
        $framing = new H264Framing(self::avcC());
        $this->assertTrue($framing->isLengthPrefixed());

        $slice = "\x41".str_repeat("\x11", 40);
        $result = $framing->convert(self::lengthPrefixed($slice), false);

        $this->assertSame(self::START.$slice, $result);
    }

    public function testEveryNalUnitOfAFrameIsConverted(): void
    {
        $framing = new H264Framing(self::avcC());

        $first = "\x41".str_repeat("\x22", 10);
        $second = "\x41".str_repeat("\x33", 20);
        $result = $framing->convert(self::lengthPrefixed($first).self::lengthPrefixed($second), false);

        $this->assertSame(self::START.$first.self::START.$second, $result);
    }

    /**
     * A participant may start watching at any moment, so the parameter sets have to travel with
     * every keyframe rather than only once at the start of the track.
     */
    public function testKeyframesCarryTheParameterSets(): void
    {
        $framing = new H264Framing(self::avcC());

        $idr = "\x65".str_repeat("\x44", 30);
        $result = $framing->convert(self::lengthPrefixed($idr), true);

        $this->assertSame(
            self::START.self::SPS.self::START.self::PPS.self::START.$idr,
            $result
        );
    }

    public function testDeltaFramesDoNotCarryTheParameterSets(): void
    {
        $framing = new H264Framing(self::avcC());

        $result = $framing->convert(self::lengthPrefixed("\x41ab"), false);

        $this->assertStringNotContainsString(self::SPS, $result);
    }

    /**
     * A two byte length field is unusual but legal, and misreading it would shred every frame.
     */
    public function testShorterLengthFieldsAreHonoured(): void
    {
        $framing = new H264Framing(self::avcC(2));

        $nal = "\x41".str_repeat("\x55", 5);
        $result = $framing->convert(self::lengthPrefixed($nal, 2), false);

        $this->assertSame(self::START.$nal, $result);
    }

    /**
     * Some muxers write a raw Annex B stream and declare no CodecPrivate at all; those frames are
     * already in the right shape and must be passed through untouched.
     */
    public function testStreamsWithoutAConfigurationRecordArePassedThrough(): void
    {
        $framing = new H264Framing(null);
        $this->assertFalse($framing->isLengthPrefixed());

        $frame = self::START."\x65".str_repeat("\x66", 12);
        $this->assertSame($frame, $framing->convert($frame, true));
        $this->assertSame($frame, $framing->convert($frame, false));
    }

    public function testATruncatedNalUnitStopsTheConversionInsteadOfOverreading(): void
    {
        $framing = new H264Framing(self::avcC());

        $good = "\x41".str_repeat("\x77", 4);
        // Announce 100 bytes but supply three.
        $truncated = pack('N', 100).'abc';
        $result = $framing->convert(self::lengthPrefixed($good).$truncated, false);

        $this->assertSame(self::START.$good, $result);
    }

    public function testAConfigurationRecordWithoutParameterSetsIsHandled(): void
    {
        // Declares zero SPS and zero PPS.
        $framing = new H264Framing("\x01\x42\xE0\x1F\xFF\xE0\x00");

        $nal = "\x65ab";
        $this->assertSame(self::START.$nal, $framing->convert(self::lengthPrefixed($nal), true));
    }
}
