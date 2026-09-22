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

use Amp\ByteStream\ReadableBuffer;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\GroupSdp;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;
use Webrtc\Codecs\Codec;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTPParameter\RTCRtpCodecParameters;

/**
 * Tests that every video codec a Telegram group call can decode can also be transmitted.
 *
 * tgcalls hands its incoming video channels the full VP8/VP9/H.264 table
 * (`GroupInstanceCustomImpl.cpp`), so all three have to survive the whole outgoing path: the
 * demuxer has to recognise the track, the payloader has to frame it, and the SDP answer has to pin
 * our own m-line to it so the peer decodes it as the right codec.
 *
 * @internal
 */
final class GroupVideoCodecTest extends TestCase
{
    private const TRANSPORT = [
        'ufrag' => 'abcd',
        'pwd' => 'efghijklmnopqrstuvwx',
        'fingerprints' => [['hash' => 'sha-256', 'fingerprint' => 'AA:BB', 'setup' => 'passive']],
        'candidates' => [],
    ];

    /**
     * Encode an EBML element: its ID (already including the length marker) and its payload.
     */
    private static function element(string $id, string $payload): string
    {
        return $id.pack('N', \strlen($payload) | (1 << 28)).$payload;
    }

    /**
     * Build a minimal Matroska file holding one video track in the given codec.
     */
    private static function buildFile(string $codecId, ?string $codecPrivate = null, string $frame = 'FRAME'): string
    {
        $entry = self::element("\xD7", "\x01")            // TrackNumber = 1
            .self::element("\x83", "\x01")                // TrackType = video
            .self::element("\x86", $codecId);             // CodecID
        if ($codecPrivate !== null) {
            $entry .= self::element("\x63\xA2", $codecPrivate);
        }
        $tracks = self::element("\x16\x54\xAE\x6B", self::element("\xAE", $entry));
        $info = self::element("\x15\x49\xA9\x66", self::element("\x2A\xD7\xB1", "\x0F\x42\x40"));
        $cluster = self::element(
            "\x1F\x43\xB6\x75",
            self::element("\xE7", "\x00")
            // SimpleBlock: track vint, 16-bit relative timestamp, flags with the keyframe bit.
            .self::element("\xA3", "\x81".pack('n', 0)."\x80".$frame)
        );

        return self::element("\x18\x53\x80\x67", $info.$tracks.$cluster);
    }

    private static function call(): CallControllerInterface
    {
        return new class implements CallControllerInterface {
            public array $logs = [];

            public function log(string $message, int $level = Logger::NOTICE): void
            {
                $this->logs[] = $message;
            }

            public function isCallEnded(): bool
            {
                return false;
            }

            public function __toString(): string
            {
                return 'test call';
            }
        };
    }

    /**
     * Play a file and return the codec the source settled on, plus the first frame it queued.
     *
     * @return array{?string, ?array{data: string, timestamp: int, keyframe: bool}}
     */
    private static function play(string $file): array
    {
        $dj = self::playDj($file);

        return [$dj->getVideoCodec(), $dj->pullVideo()];
    }

    /**
     * Play a file to completion and return the source itself, for inspecting its negotiated state.
     */
    private static function playDj(string $file): DjLoop
    {
        $dj = new DjLoop(self::call());
        $dj->play(new ReadableBuffer($file));
        // play() defers the streaming reader onto the event loop, so let it run to completion.
        EventLoop::run();

        return $dj;
    }

    /**
     * @return list<array{string, string, ?string}> Matroska codec ID, SDP name, CodecPrivate
     */
    public static function codecs(): array
    {
        return [
            'VP8' => ['V_VP8', 'VP8', null],
            'VP9' => ['V_VP9', 'VP9', null],
            // A minimal AVCDecoderConfigurationRecord: four byte lengths, no parameter sets.
            'H264' => ['V_MPEG4/ISO/AVC', 'H264', "\x01\x42\xE0\x1F\xFF\xE0\x00"],
        ];
    }

    /**
     * @dataProvider codecs
     */
    public function testTheDemuxerRecognisesEverySendableCodec(
        string $codecId,
        string $name,
        ?string $codecPrivate
    ): void {
        // H.264 frames are length prefixed, everything else is stored exactly as RTP wants it.
        $frame = $codecPrivate === null ? 'FRAME' : pack('N', 5)."\x65abcd";

        [$codec, $queued] = self::play(self::buildFile($codecId, $codecPrivate, $frame));

        $this->assertSame($name, $codec);
        $this->assertNotNull($queued, 'the frame must reach the playback queue');
        $this->assertTrue($queued['keyframe']);
    }

    /**
     * @dataProvider codecs
     */
    public function testEverySendableCodecHasAPayloader(string $codecId, string $name): void
    {
        $encoder = Codec::getEncoder(new RTCRtpCodecParameters("video/$name", 90000, 0, 100));

        [$payloads] = $encoder->pack(new EncodedPacket("\x00\x00\x00\x01\x65".random_bytes(2000), 9000, true));

        $this->assertNotEmpty($payloads, "$name produced no RTP payload");
        foreach ($payloads as $payload) {
            $this->assertLessThanOrEqual(1300, \strlen($payload), "$name payload exceeds the MTU");
        }
    }

    /**
     * @dataProvider codecs
     */
    public function testTheAnswerPinsOurOwnMLineToTheCodecWeArePlaying(string $codecId, string $name): void
    {
        $offer = implode("\r\n", [
            'v=0',
            'o=- 1 2 IN IP4 127.0.0.1',
            's=-',
            't=0 0',
            'a=msid-semantic: WMS *',
            'm=video 9 UDP/TLS/RTP/SAVPF 97',
            'a=mid:0',
            'a=sendonly',
            'm=video 9 UDP/TLS/RTP/SAVPF 97',
            'a=mid:1',
            'a=recvonly',
        ])."\r\n";

        $answer = GroupSdp::buildAnswer($offer, self::TRANSPORT, ['1' => 4242], null, $name);

        $mLines = array_values(array_filter(
            explode("\r\n", $answer),
            static fn (string $line): bool => str_starts_with($line, 'm=')
        ));

        $expected = match ($name) {
            'VP8' => GroupSdp::VP8_PAYLOAD_TYPE,
            'VP9' => GroupSdp::VP9_PAYLOAD_TYPE,
            'H264' => GroupSdp::H264_PAYLOAD_TYPE,
        };
        $ours = explode(' ', $mLines[0]);
        $this->assertSame((string) $expected, $ours[3], "our m-line must lead with $name");

        // Every codec is still offered, only the order changes.
        $this->assertCount(9, $ours, 'the full table must stay on the m-line');
        // The participant's m-line keeps the canonical tgcalls order.
        $this->assertSame(
            'm=video 9 UDP/TLS/RTP/SAVPF 100 101 102 103 104 105',
            $mLines[1]
        );
    }

    /**
     * The fmtp parameters advertised for our outgoing video must be read from the file's bitstream
     * (its configuration record, or a keyframe for VP9), not hardcoded.
     */
    public function testItAdvertisesTheFilesRealCodecParameters(): void
    {
        // AV1 av1C: seq_profile 0, seq_level_idx 8 (level 4.0), seq_tier 0.
        $dj = self::playDj(self::buildFile('V_AV1', "\x81\x08\x0c\x00"));
        $this->assertSame('AV1', $dj->getVideoCodec());
        $this->assertSame(['profile' => '0', 'level-idx' => '8', 'tier' => '0'], $dj->getVideoParameters());

        // H264 avcC bytes 1-3 (profile/compat/level) form the SDP profile-level-id.
        $dj = self::playDj(self::buildFile('V_MPEG4/ISO/AVC', "\x01\x4D\x40\x1F\xFF\xE0\x00", pack('N', 5)."\x65abcd"));
        $this->assertSame('H264', $dj->getVideoCodec());
        $this->assertSame('4d401f', $dj->getVideoParameters()['profile-level-id']);

        // VP9 without a configuration record advertises the common profile 0.
        $dj = self::playDj(self::buildFile('V_VP9'));
        $this->assertSame('VP9', $dj->getVideoCodec());
        $this->assertSame(['profile-id' => '0'], $dj->getVideoParameters());
    }

    /**
     * An audio-only or unsupported file must not leave a stale codec pinned from a previous one.
     */
    public function testAFileWithNoSendableVideoReportsNoCodec(): void
    {
        [$codec, $queued] = self::play(self::buildFile('V_MPEGH/ISO/HEVC'));

        $this->assertNull($codec);
        $this->assertNull($queued);
    }
}
