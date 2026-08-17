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

use Webrtc\Codecs\Codec;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTPParameter\RTCRtpCodecParameters;
use PHPUnit\Framework\TestCase;

/**
 * Tests that already-encoded media can be packetized without loading any codec library.
 *
 * This is what lets calls work on a plain PHP installation: media that is already in the format
 * Telegram expects (OPUS audio, VP8 or H.264 video) is passed straight through to RTP, so the FFI
 * extension is only needed for realtime transcoding.
 *
 * @internal
 */
final class WebrtcCodecTest extends TestCase
{
    /** Largest RTP payload the packetizers may emit, to stay inside a typical MTU. */
    private const MAX_PAYLOAD = 1300;

    public function testOpusIsPassedThroughUntouched(): void
    {
        $encoder = Codec::getEncoder(new RTCRtpCodecParameters('audio/opus', 48000, 2, 111));
        $frame = "\x58".random_bytes(80);

        [$payloads, $timestamp] = $encoder->pack(new EncodedPacket($frame, 2880));

        $this->assertSame([$frame], $payloads, 'OPUS frames must not be modified');
        $this->assertSame(2880, $timestamp, 'the RTP timestamp must be preserved');
    }

    /**
     * A VP8 frame larger than one packet must be split, with a payload descriptor on each part.
     */
    public function testVp8IsFragmented(): void
    {
        $encoder = Codec::getEncoder(new RTCRtpCodecParameters('video/VP8', 90000, 0, 96));
        $frame = random_bytes(3000);

        [$payloads, $timestamp] = $encoder->pack(new EncodedPacket($frame, 3000, true));

        $this->assertGreaterThan(1, \count($payloads), 'a 3000 byte frame cannot fit in one packet');
        $this->assertSame(3000, $timestamp);

        $recovered = '';
        foreach ($payloads as $index => $payload) {
            $this->assertLessThanOrEqual(self::MAX_PAYLOAD, \strlen($payload));
            // The first byte is the VP8 payload descriptor; only the first fragment starts a
            // partition, so its S bit is set and the later ones' is not.
            $descriptor = \ord($payload[0]);
            $this->assertSame($index === 0, ($descriptor & 0x10) !== 0, 'partition start bit');
            // Skip the descriptor: one byte plus the extension bytes it announces.
            $offset = 1;
            if ($descriptor & 0x80) {
                $extension = \ord($payload[1]);
                $offset++;
                if ($extension & 0x80) {
                    $offset += (\ord($payload[2]) & 0x80) ? 2 : 1;
                }
                if ($extension & 0x40) {
                    $offset++;
                }
                if ($extension & 0x20 || $extension & 0x10) {
                    $offset++;
                }
            }
            $recovered .= substr($payload, $offset);
        }
        $this->assertSame($frame, $recovered, 'reassembling the fragments must yield the frame');
    }

    /**
     * H.264 is split on NAL unit boundaries, and large units become FU-A fragments.
     */
    public function testH264IsPacketized(): void
    {
        $encoder = Codec::getEncoder(new RTCRtpCodecParameters('video/H264', 90000, 0, 102));
        $bitstream = "\x00\x00\x00\x01\x67".str_repeat("\x11", 20)
            ."\x00\x00\x00\x01\x65".str_repeat("\x22", 2000);

        [$payloads, $timestamp] = $encoder->pack(new EncodedPacket($bitstream, 6000, true));

        $this->assertNotEmpty($payloads);
        $this->assertSame(6000, $timestamp);
        foreach ($payloads as $payload) {
            $this->assertLessThanOrEqual(self::MAX_PAYLOAD, \strlen($payload));
            $this->assertNotSame('', $payload);
        }
    }

    /**
     * Building an encoder must not load any native library: that only happens on a real encode.
     */
    public function testEncodersAreConstructedWithoutFfi(): void
    {
        // Each codec needs a frame in its own format; H.264 in particular expects Annex-B.
        foreach ([
            ['audio/opus', 48000, 2, 111, "\x58".str_repeat("\x01", 60)],
            ['video/VP8', 90000, 0, 96, str_repeat("\x9d", 200)],
            ['video/H264', 90000, 0, 102, "\x00\x00\x00\x01\x65".str_repeat("\x22", 200)],
        ] as [$mime, $clock, $channels, $payloadType, $frame]) {
            $encoder = Codec::getEncoder(new RTCRtpCodecParameters($mime, $clock, $channels, $payloadType));
            $this->assertNotNull($encoder, "$mime encoder");
            // Packing an already-encoded frame must work regardless of whether FFI is available.
            [$payloads] = $encoder->pack(new EncodedPacket($frame, 0, true));
            $this->assertNotEmpty($payloads, "$mime produced no RTP payload");
        }
    }

    public function testEncodedPacketExposesItsFrame(): void
    {
        $packet = new EncodedPacket('abcdef', 1234, true, 42);

        $this->assertSame('abcdef', $packet->getData());
        $this->assertSame(6, $packet->getSize());
        $this->assertSame(1234, $packet->getTimestamp());
        $this->assertTrue($packet->isKeyframe());
        $this->assertSame(42, $packet->getAudioLevel());

        $delta = new EncodedPacket('x', 0, false);
        $this->assertFalse($delta->isKeyframe());
        $this->assertNull($delta->getAudioLevel());
    }
}
