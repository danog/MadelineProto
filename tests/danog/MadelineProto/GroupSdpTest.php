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

use danog\MadelineProto\Tgcalls\GroupSdp;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the SDP we synthesize for the group call SFU covers everything a group call carries.
 *
 * The SFU forwards RTP verbatim, so the payload types and header extension IDs are effectively part
 * of the wire protocol: they must be exactly the ones tgcalls assigns in
 * `GroupInstanceCustomImpl.cpp`, or participants decode each other's streams as the wrong codec.
 *
 * @internal
 */
final class GroupSdpTest extends TestCase
{
    private const TRANSPORT = [
        'ufrag' => 'abcd',
        'pwd' => 'efghijklmnopqrstuvwx',
        'fingerprints' => [['hash' => 'sha-256', 'fingerprint' => 'AA:BB', 'setup' => 'passive']],
        'candidates' => [],
    ];

    /**
     * An offer with our own outgoing audio and video, plus one participant's audio.
     */
    private static function offer(): string
    {
        return implode("\r\n", [
            'v=0',
            'o=- 1 2 IN IP4 127.0.0.1',
            's=-',
            't=0 0',
            'a=group:BUNDLE 0 1 2',
            'a=msid-semantic: WMS *',
            'm=audio 9 UDP/TLS/RTP/SAVPF 96',
            'a=mid:0',
            'a=sendonly',
            'm=video 9 UDP/TLS/RTP/SAVPF 97 98',
            'a=mid:1',
            'a=sendonly',
            'm=audio 9 UDP/TLS/RTP/SAVPF 96',
            'a=mid:2',
            'a=recvonly',
        ])."\r\n";
    }

    /**
     * @return list<string> The m-lines of an answer, in order.
     */
    private static function mLines(string $answer): array
    {
        return array_values(array_filter(
            explode("\r\n", $answer),
            static fn (string $line): bool => str_starts_with($line, 'm=')
        ));
    }

    public function testEveryGroupCallCodecIsOffered(): void
    {
        $answer = GroupSdp::buildAnswer(self::offer(), self::TRANSPORT, ['2' => 12345]);

        // OPUS and the raw PCM codec tgcalls pairs it with.
        $this->assertStringContainsString('a=rtpmap:111 opus/48000/2', $answer);
        $this->assertStringContainsString('a=fmtp:111 minptime=10;useinbandfec=1', $answer);
        $this->assertStringContainsString('a=rtpmap:112 L16/48000/1', $answer);

        // The three video codecs, at the payload types tgcalls assigns them.
        $this->assertStringContainsString('a=rtpmap:100 VP8/90000', $answer);
        $this->assertStringContainsString('a=rtpmap:101 rtx/90000', $answer);
        $this->assertStringContainsString('a=fmtp:101 apt=100', $answer);
        $this->assertStringContainsString('a=rtpmap:102 VP9/90000', $answer);
        $this->assertStringContainsString('a=fmtp:102 profile-id=0', $answer);
        $this->assertStringContainsString('a=fmtp:103 apt=102', $answer);
        $this->assertStringContainsString('a=rtpmap:104 H264/90000', $answer);
        $this->assertStringContainsString(
            'a=fmtp:104 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f',
            $answer
        );
        $this->assertStringContainsString('a=fmtp:105 apt=104', $answer);

        // The full feedback set, on every video codec but the retransmission streams.
        foreach ([100, 102, 104] as $payloadType) {
            foreach (['goog-remb', 'transport-cc', 'ccm fir', 'nack', 'nack pli'] as $feedback) {
                $this->assertStringContainsString("a=rtcp-fb:$payloadType $feedback\r\n", $answer);
            }
        }
        $this->assertStringNotContainsString('a=rtcp-fb:101 ', $answer);
    }

    public function testHeaderExtensionsMatchTgcalls(): void
    {
        $answer = GroupSdp::buildAnswer(self::offer(), self::TRANSPORT, ['2' => 12345]);

        // tgcalls offers the audio level extension on video m-lines too.
        $this->assertSame(
            3,
            substr_count($answer, 'a=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level'),
            'the audio level extension belongs on all three m-lines'
        );
        $this->assertSame(
            3,
            substr_count($answer, 'a=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time')
        );
        $this->assertSame(
            3,
            substr_count(
                $answer,
                'a=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01'
            )
        );
        // The orientation extension is video only.
        $this->assertSame(1, substr_count($answer, 'a=extmap:13 urn:3gpp:video-orientation'));
    }

    public function testOurOwnVideoMLineLeadsWithTheCodecWeCanSend(): void
    {
        $answer = GroupSdp::buildAnswer(self::offer(), self::TRANSPORT, ['2' => 12345]);

        [, $video] = self::mLines($answer);
        $this->assertSame('m=video 9 UDP/TLS/RTP/SAVPF 100 101 102 103 104 105', $video);
    }

    /**
     * Whatever the SFU announces wins, so a codec Telegram adds later is used without a change here.
     */
    public function testTheSfuAnnouncedTableIsHonoured(): void
    {
        $parsed = GroupSdp::parseJoinResponse([
            'transport' => self::TRANSPORT,
            'video' => [
                'endpoint' => 'unified0',
                'payload-types' => [
                    // Deliberately not in our built-in order, and with a codec we do not know.
                    ['id' => 104, 'name' => 'H264', 'clockrate' => 90000, 'rtcp-fbs' => [
                        ['type' => 'nack'],
                        ['type' => 'nack', 'subtype' => 'pli'],
                        ['type' => 'ccm fir'],
                    ]],
                    ['id' => 105, 'name' => 'rtx', 'clockrate' => 90000, 'parameters' => ['apt' => '104']],
                    ['id' => 100, 'name' => 'VP8', 'clockrate' => 90000],
                    ['id' => 101, 'name' => 'rtx', 'clockrate' => 90000, 'parameters' => ['apt' => '100']],
                    ['id' => 106, 'name' => 'AV1', 'clockrate' => 90000],
                ],
                'rtp-hdrexts' => [
                    ['id' => 4, 'uri' => 'urn:ietf:params:rtp-hdrext:sdes:mid'],
                ],
            ],
        ]);

        $answer = GroupSdp::buildAnswer(self::offer(), self::TRANSPORT, ['2' => 12345], $parsed['video']);

        $this->assertStringContainsString('a=rtpmap:106 AV1/90000', $answer);
        // A subtype may arrive either as its own key or glued to the type.
        $this->assertStringContainsString("a=rtcp-fb:104 nack pli\r\n", $answer);
        $this->assertStringContainsString("a=rtcp-fb:104 ccm fir\r\n", $answer);
        // The SFU's extension map replaces ours on video m-lines, but not on audio ones.
        $this->assertStringContainsString('a=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid', $answer);
        $this->assertStringNotContainsString('a=extmap:13 ', $answer);

        // Our own m-line still leads with VP8, the participant's keeps the SFU's own order.
        [, $ours] = self::mLines($answer);
        $this->assertSame('m=video 9 UDP/TLS/RTP/SAVPF 100 101 104 105 106', $ours);
    }

    /**
     * The production SFU announces values that do not describe real RTP, and an answer built from
     * them verbatim matches no codec the local stack has, so nothing can be sent or received.
     *
     * This is the `video` object of a real `updateGroupCallConnection`, reduced to the fields that
     * matter: note `clockrate: 9000` rather than 90000, `channels` on video codecs, and a bitrate
     * hint smuggled in under an `fmtp` key.
     */
    public function testTheSfuIsOnlyTrustedForCodecsAndPayloadTypes(): void
    {
        $parsed = GroupSdp::parseJoinResponse([
            'transport' => self::TRANSPORT,
            'video' => [
                'endpoint' => '1c2d2eb6d0aa30d6',
                'payload-types' => [
                    [
                        'id' => 100, 'name' => 'VP8', 'clockrate' => 9000, 'channels' => 1,
                        'parameters' => ['fmtp' => 'x-google-start-bitrate=800'],
                        'rtcp-fbs' => [['type' => 'goog-remb'], ['type' => 'nack', 'subtype' => 'pli']],
                    ],
                    ['id' => 101, 'clockrate' => 90000, 'name' => 'rtx', 'parameters' => ['apt' => '100']],
                    ['id' => 102, 'clockrate' => 9000, 'name' => 'VP9', 'parameters' => []],
                    ['id' => 103, 'clockrate' => 90000, 'name' => 'rtx', 'parameters' => ['apt' => '102']],
                    ['id' => 104, 'clockrate' => 9000, 'name' => 'H264', 'channels' => 1, 'parameters' => []],
                    ['id' => 105, 'clockrate' => 90000, 'name' => 'rtx', 'parameters' => ['apt' => '104']],
                ],
                'rtp-hdrexts' => [
                    ['id' => 2, 'uri' => 'http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time'],
                ],
                'server_sources' => [-259876949],
            ],
        ]);

        $answer = GroupSdp::buildAnswer(self::offer(), self::TRANSPORT, ['2' => 12345], $parsed['video']);

        // Every video codec must stay at 90kHz and carry no channel count.
        $this->assertStringContainsString('a=rtpmap:100 VP8/90000'."\r\n", $answer);
        $this->assertStringContainsString('a=rtpmap:102 VP9/90000'."\r\n", $answer);
        $this->assertStringContainsString('a=rtpmap:104 H264/90000'."\r\n", $answer);
        $this->assertStringNotContainsString('/9000'."\r\n", $answer);

        // The bogus parameter must not reach the answer, but ours must.
        $this->assertStringNotContainsString('x-google-start-bitrate', $answer);
        $this->assertStringContainsString('a=fmtp:102 profile-id=0', $answer);
        $this->assertStringContainsString(
            'a=fmtp:104 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f',
            $answer
        );

        // Retransmissions still point at the codec the SFU paired them with.
        $this->assertStringContainsString('a=fmtp:101 apt=100', $answer);
        $this->assertStringContainsString('a=fmtp:103 apt=102', $answer);
        $this->assertStringContainsString('a=fmtp:105 apt=104', $answer);

        // The payload types themselves come from the SFU, and its feedback list is honoured.
        [, $video] = self::mLines($answer);
        $this->assertSame('m=video 9 UDP/TLS/RTP/SAVPF 100 101 102 103 104 105', $video);
        $this->assertStringContainsString("a=rtcp-fb:100 nack pli\r\n", $answer);
    }

    /**
     * A codec Telegram enables later has no entry in our table, and must still be offered back at
     * the payload type the SFU chose for it.
     */
    public function testAnUnknownCodecKeepsItsPayloadType(): void
    {
        $parsed = GroupSdp::parseJoinResponse([
            'transport' => self::TRANSPORT,
            'video' => ['payload-types' => [
                ['id' => 106, 'name' => 'AV1', 'clockrate' => 9000, 'rtcp-fbs' => [['type' => 'nack']]],
                ['id' => 107, 'name' => 'rtx', 'clockrate' => 90000, 'parameters' => ['apt' => '106']],
            ]],
        ]);

        $answer = GroupSdp::buildAnswer(self::offer(), self::TRANSPORT, ['2' => 12345], $parsed['video']);

        $this->assertStringContainsString('a=rtpmap:106 AV1/90000'."\r\n", $answer);
        $this->assertStringContainsString('a=fmtp:107 apt=106', $answer);
        $this->assertStringContainsString("a=rtcp-fb:106 nack\r\n", $answer);
    }

    public function testAudioKeepsItsOwnTableWhenTheSfuAnnouncesVideoCodecs(): void
    {
        $parsed = GroupSdp::parseJoinResponse([
            'transport' => self::TRANSPORT,
            'video' => ['payload-types' => [['id' => 100, 'name' => 'VP8', 'clockrate' => 90000]]],
        ]);

        $answer = GroupSdp::buildAnswer(self::offer(), self::TRANSPORT, ['2' => 12345], $parsed['video']);

        [$audio] = self::mLines($answer);
        $this->assertSame('m=audio 9 UDP/TLS/RTP/SAVPF 111 112', $audio);
    }

    public function testAJoinResponseWithoutVideoInformationIsAccepted(): void
    {
        $parsed = GroupSdp::parseJoinResponse(['transport' => self::TRANSPORT]);

        $this->assertNull($parsed['video']);
        $this->assertFalse($parsed['stream']);
        $this->assertFalse($parsed['rtmp']);
        $this->assertSame(self::TRANSPORT, $parsed['transport']);
    }
}
