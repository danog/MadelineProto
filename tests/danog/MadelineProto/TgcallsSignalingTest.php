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

use danog\MadelineProto\Tgcalls\EncryptedConnection;
use danog\MadelineProto\Tgcalls\GroupSdp;
use danog\MadelineProto\Tgcalls\SignalingSctpTransport;
use danog\MadelineProto\Tgcalls\V2Sdp;
use danog\MadelineProto\VoIP\SignalingProtocolVersion;
use PHPUnit\Framework\TestCase;

/**
 * Tests the tgcalls signaling layer: version negotiation, packet encryption and the translation
 * between tgcalls' structured media descriptions and SDP.
 *
 * @internal
 */
use function Amp\delay;

final class TgcallsSignalingTest extends TestCase
{
    /**
     * A representative offer, of the shape the local peer connection produces.
     */
    private const OFFER = "v=0\r\n"
        ."o=- 1 1 IN IP4 0.0.0.0\r\n"
        ."s=-\r\n"
        ."t=0 0\r\n"
        ."a=group:BUNDLE 0 1\r\n"
        ."m=audio 9 UDP/TLS/RTP/SAVPF 111\r\n"
        ."c=IN IP4 0.0.0.0\r\n"
        ."a=sendrecv\r\n"
        ."a=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\r\n"
        ."a=mid:0\r\n"
        ."a=rtcp-mux\r\n"
        ."a=ssrc:12345 cname:test\r\n"
        ."a=rtpmap:111 opus/48000/2\r\n"
        ."a=fmtp:111 minptime=10;useinbandfec=1\r\n"
        ."a=rtcp-fb:111 transport-cc\r\n"
        ."a=ice-ufrag:LOCALUF\r\n"
        ."a=ice-pwd:LOCALPWD0000000000000000\r\n"
        ."a=fingerprint:sha-256 AA:BB:CC\r\n"
        ."a=setup:actpass\r\n"
        ."m=video 9 UDP/TLS/RTP/SAVPF 96\r\n"
        ."c=IN IP4 0.0.0.0\r\n"
        ."a=sendrecv\r\n"
        ."a=mid:1\r\n"
        ."a=rtcp-mux\r\n"
        ."a=ssrc:67890 cname:test\r\n"
        ."a=rtpmap:96 VP8/90000\r\n"
        ."a=ice-ufrag:LOCALUF\r\n"
        ."a=ice-pwd:LOCALPWD0000000000000000\r\n"
        ."a=fingerprint:sha-256 AA:BB:CC\r\n"
        ."a=setup:actpass\r\n";

    // ------------------------------------------------------------ version negotiation

    /**
     * @return list<array{list<string>, ?string, bool}>
     */
    public static function provideVersions(): array
    {
        return [
            'telegram web only' => [['13.0.0'], '13.0.0', false],
            'reference impl' => [['11.0.0', '10.0.0'], '11.0.0', false],
            'sdp only' => [['10.0.0'], '10.0.0', false],
            'v2 impl' => [['9.0.0', '7.0.0'], '9.0.0', false],
            'libtgvoip only' => [['2.4.4', '2.7.7'], '2.7.7', true],
            // The newest version wins whenever the peer offers a choice.
            'everything' => [['2.4.4', '2.7.7', '7.0.0', '10.0.0', '13.0.0'], '13.0.0', false],
            'nothing advertised' => [[], '2.4.4', true],
            'nothing in common' => [['99.0.0'], null, false],
        ];
    }

    /**
     * @param list<string> $advertised
     *
     * @dataProvider provideVersions
     */
    public function testVersionNegotiation(array $advertised, ?string $expected, bool $legacy): void
    {
        $version = SignalingProtocolVersion::fromProtocol(['library_versions' => $advertised]);

        $this->assertSame($expected, $version?->value);
        if ($version !== null) {
            $this->assertSame($legacy, $version->isLegacy());
        }
    }

    /**
     * Every version we advertise must actually be implemented, or peers will pick one we cannot
     * speak and the call will connect but carry no media.
     */
    public function testAdvertisedVersionsAreAllImplemented(): void
    {
        $this->assertNotEmpty(SignalingProtocolVersion::supported());
        foreach (SignalingProtocolVersion::supported() as $version) {
            $this->assertTrue(
                SignalingProtocolVersion::from($version)->isImplemented(),
                "$version is advertised but not implemented"
            );
        }
    }

    /**
     * The three protocol axes must match what each tgcalls implementation actually does.
     */
    public function testVersionTraits(): void
    {
        $sdp = SignalingProtocolVersion::V10;
        $this->assertTrue($sdp->usesSdp());
        $this->assertTrue($sdp->usesReliableFraming());
        $this->assertFalse($sdp->supportsCompression());
        $this->assertFalse($sdp->isLegacy());

        $negotiate = SignalingProtocolVersion::V7;
        $this->assertFalse($negotiate->usesSdp());
        $this->assertFalse($negotiate->usesReliableFraming());

        $web = SignalingProtocolVersion::V13;
        $this->assertTrue($web->supportsCompression());
        $this->assertTrue($web->usesSctp());
    }

    // ------------------------------------------------------------ signaling crypto

    /**
     * @return array{EncryptedConnection, EncryptedConnection}
     */
    private static function pair(): array
    {
        $key = random_bytes(256);
        $noop = static function (int $cause): void {
        };
        return [
            new EncryptedConnection($key, true, $noop),
            new EncryptedConnection($key, false, $noop),
        ];
    }

    public function testFramedSignalingRoundTrip(): void
    {
        [$caller, $callee] = self::pair();

        $message = json_encode(['@type' => 'offer', 'sdp' => str_repeat('v=0', 50)]);
        $packet = $caller->prepareForSendingRawMessage((string) $message, true);

        $this->assertNotNull($packet);
        $this->assertSame([$message], $callee->handleIncomingRawPacket($packet));
        $this->assertSame([], $callee->handleIncomingRawPacket($packet), 'replays must be dropped');
    }

    /**
     * The bare packet mode used by the newer protocol versions.
     */
    public function testRawSignalingRoundTrip(): void
    {
        [$caller, $callee] = self::pair();

        $message = json_encode(['@type' => 'InitialSetup', 'ufrag' => 'abcd']);
        $packet = $caller->encryptRawPacket((string) $message);

        $this->assertStringNotContainsString('InitialSetup', $packet, 'the payload must be encrypted');
        $this->assertSame($message, $callee->decryptRawPacket($packet));
        $this->assertNull($callee->decryptRawPacket($packet), 'replays must be dropped');

        $forged = $packet;
        $forged[30] = \chr(\ord($forged[30]) ^ 0xFF);
        $this->assertNull($callee->decryptRawPacket($forged), 'forgeries must be dropped');
    }

    /**
     * The two directions use different keys, so a peer must not decrypt its own packets.
     */
    public function testDirectionsAreSeparate(): void
    {
        [$caller] = self::pair();

        $packet = $caller->encryptRawPacket('hello');
        $this->assertNull($caller->decryptRawPacket($packet));
    }

    // ------------------------------------------------------------ NegotiateChannels

    public function testContentsFromOffer(): void
    {
        $contents = V2Sdp::contentsFromOffer(self::OFFER);

        $this->assertCount(2, $contents);
        $this->assertSame('audio', $contents[0]['type']);
        $this->assertSame('12345', $contents[0]['ssrc']);
        $this->assertSame('video', $contents[1]['type']);
        $this->assertSame('67890', $contents[1]['ssrc']);

        $opus = $contents[0]['payloadTypes'][0];
        $this->assertSame(111, $opus['id']);
        $this->assertSame('opus', $opus['name']);
        $this->assertSame(48000, $opus['clockrate']);
        $this->assertSame(2, $opus['channels']);
        $this->assertSame(['minptime' => '10', 'useinbandfec' => '1'], $opus['parameters']);
        $this->assertSame([['type' => 'transport-cc', 'subtype' => '']], $opus['feedbackTypes']);

        $this->assertSame(
            [['id' => 1, 'uri' => 'urn:ietf:params:rtp-hdrext:ssrc-audio-level']],
            $contents[0]['rtpExtensions']
        );
    }

    public function testContentsFromOfferCanSelectOutgoingChannels(): void
    {
        $offer = str_replace(
            "m=video 9 UDP/TLS/RTP/SAVPF 96\r\nc=IN IP4 0.0.0.0\r\na=sendrecv",
            "m=video 9 UDP/TLS/RTP/SAVPF 96\r\nc=IN IP4 0.0.0.0\r\na=recvonly",
            self::OFFER,
        );

        $contents = V2Sdp::contentsFromOffer($offer, outgoingOnly: true);

        $this->assertCount(1, $contents);
        $this->assertSame('audio', $contents[0]['type']);
    }

    public function testInitialSetupFromDescription(): void
    {
        $setup = V2Sdp::initialSetupFromDescription(self::OFFER, 'active');

        $this->assertSame('LOCALUF', $setup['ufrag']);
        $this->assertSame('LOCALPWD0000000000000000', $setup['pwd']);
        $this->assertSame(
            [['hash' => 'sha-256', 'fingerprint' => 'AA:BB:CC', 'setup' => 'active']],
            $setup['fingerprints']
        );
    }

    /**
     * The description synthesized from the peer's messages must carry its transport parameters and
     * one m-line per content, aligned with our own offer.
     */
    public function testBuildRemoteDescription(): void
    {
        $initialSetup = [
            'ufrag' => 'PEERUF',
            'pwd' => 'PEERPWD00000000000000000',
            'fingerprints' => [['hash' => 'sha-256', 'fingerprint' => 'DD:EE:FF', 'setup' => 'actpass']],
        ];
        $contents = V2Sdp::contentsFromOffer(self::OFFER);
        $contents[0]['ssrc'] = '99999';

        $sdp = V2Sdp::buildRemoteDescription(self::OFFER, $initialSetup, $contents, true);

        $this->assertStringContainsString('a=ice-ufrag:PEERUF', $sdp);
        $this->assertStringContainsString('a=ice-pwd:PEERPWD00000000000000000', $sdp);
        $this->assertStringContainsString('a=fingerprint:sha-256 DD:EE:FF', $sdp);
        // An answer must commit to a concrete DTLS role.
        $this->assertStringContainsString('a=setup:active', $sdp);
        $this->assertStringNotContainsString('a=setup:actpass', $sdp);
        $this->assertStringContainsString('a=ssrc:99999', $sdp);
        $this->assertStringContainsString('a=rtpmap:111 opus/48000/2', $sdp);
        $this->assertStringContainsString('a=fmtp:111 minptime=10;useinbandfec=1', $sdp);
        $this->assertStringContainsString('a=rtcp-fb:111 transport-cc', $sdp);
        $this->assertStringContainsString('a=rtpmap:96 VP8/90000', $sdp);
        $this->assertStringContainsString('a=mid:0', $sdp);
        $this->assertStringContainsString('a=mid:1', $sdp);
        // The m-line count must match the offer, or the mids stop lining up.
        $this->assertSame(2, substr_count($sdp, "\r\nm="));
    }

    public function testBuildRemoteDescriptionRejectsIncompleteSetup(): void
    {
        $this->expectExceptionMessage('ICE credentials');
        V2Sdp::buildRemoteDescription(self::OFFER, ['ufrag' => '', 'pwd' => ''], [], true);
    }

    /**
     * Telegram may answer an audio+video offer with audio only. The rejected video m-line must be
     * retained to preserve its index, but does not need to establish codecs or a transport.
     */
    public function testBuildRemoteDescriptionRejectsMissingMedia(): void
    {
        $initialSetup = [
            'ufrag' => 'PEERUF',
            'pwd' => 'PEERPWD00000000000000000',
            'fingerprints' => [['hash' => 'sha-256', 'fingerprint' => 'DD:EE:FF', 'setup' => 'passive']],
        ];
        $contents = [V2Sdp::contentsFromOffer(self::OFFER)[0]];

        $sdp = V2Sdp::buildRemoteDescription(self::OFFER, $initialSetup, $contents, true);
        $video = substr($sdp, (int) strpos($sdp, 'm=video'));

        $this->assertStringStartsWith('m=video 0 UDP/TLS/RTP/SAVPF 96', $video);
        $this->assertStringContainsString('a=inactive', $video);
        $this->assertStringNotContainsString('a=rtcp-mux', $video);
        $this->assertStringNotContainsString('a=ice-ufrag:', $video);
        $this->assertStringNotContainsString('a=fingerprint:', $video);
        $this->assertStringNotContainsString('a=rtpmap:', $video);
    }

    public function testBuildRemoteOfferKeepsMissingMediaReusable(): void
    {
        $initialSetup = [
            'ufrag' => 'PEERUF',
            'pwd' => 'PEERPWD00000000000000000',
            'fingerprints' => [['hash' => 'sha-256', 'fingerprint' => 'DD:EE:FF', 'setup' => 'passive']],
        ];
        $contents = [V2Sdp::contentsFromOffer(self::OFFER)[0]];

        $sdp = V2Sdp::buildRemoteDescription(self::OFFER, $initialSetup, $contents, false);
        $video = substr($sdp, (int) strpos($sdp, 'm=video'));

        $this->assertStringStartsWith('m=video 9 UDP/TLS/RTP/SAVPF 96', $video);
        $this->assertStringContainsString('a=inactive', $video);
        $this->assertStringContainsString('a=rtpmap:96 VP8/90000', $video);
        $this->assertStringContainsString('a=ice-ufrag:PEERUF', $video);
    }

    public function testBuildRemoteDescriptionMatchesContentsByMediaType(): void
    {
        $initialSetup = [
            'ufrag' => 'PEERUF',
            'pwd' => 'PEERPWD00000000000000000',
            'fingerprints' => [['hash' => 'sha-256', 'fingerprint' => 'DD:EE:FF', 'setup' => 'passive']],
        ];
        $contents = array_reverse(V2Sdp::contentsFromOffer(self::OFFER));
        $contents[0]['ssrc'] = '88888';
        $contents[1]['ssrc'] = '99999';

        $sdp = V2Sdp::buildRemoteDescription(self::OFFER, $initialSetup, $contents, false);
        $audio = substr($sdp, (int) strpos($sdp, 'm=audio'), (int) strpos($sdp, 'm=video') - (int) strpos($sdp, 'm=audio'));
        $video = substr($sdp, (int) strpos($sdp, 'm=video'));

        $this->assertStringContainsString('a=rtpmap:111 opus/48000/2', $audio);
        $this->assertStringContainsString('a=ssrc:99999', $audio);
        $this->assertStringContainsString('a=rtpmap:96 VP8/90000', $video);
        $this->assertStringContainsString('a=ssrc:88888', $video);
    }

    // ------------------------------------------------------------ SCTP-framed signaling

    /**
     * Wire two associations back to back and pump packets between them.
     *
     */
    private static function pump(
        SignalingSctpTransport $a,
        SignalingSctpTransport $b,
        array &$aOut,
        array &$bOut
    ): void {
        for ($i = 0; $i < 60; $i++) {
            // The SCTP transport queues its sends onto the event loop rather than emitting
            // them inline, so the loop needs a turn before anything shows up in the buffers.
            delay(0);

            $moved = false;
            while ($aOut !== []) {
                $b->receive((string) array_shift($aOut));
                $moved = true;
            }
            while ($bOut !== []) {
                $a->receive((string) array_shift($bOut));
                $moved = true;
            }
            if (!$moved) {
                return;
            }
        }
    }

    public function testSctpSignalingCarriesMessagesBothWays(): void
    {
        $aOut = [];
        $bOut = [];
        $aGot = [];
        $bGot = [];

        $caller = new SignalingSctpTransport(
            true,
            static function (string $p) use (&$aOut): void {
                $aOut[] = $p;
            },
            static function (string $m) use (&$aGot): void {
                $aGot[] = $m;
            }
        );
        $callee = new SignalingSctpTransport(
            false,
            static function (string $p) use (&$bOut): void {
                $bOut[] = $p;
            },
            static function (string $m) use (&$bGot): void {
                $bGot[] = $m;
            }
        );

        self::pump($caller, $callee, $aOut, $bOut);
        $this->assertTrue($caller->isEstablished(), 'the caller established the association');
        $this->assertTrue($callee->isEstablished(), 'the callee established the association');

        $caller->send('MESSAGE-FROM-CALLER');
        self::pump($caller, $callee, $aOut, $bOut);
        $this->assertSame(['MESSAGE-FROM-CALLER'], $bGot);

        $callee->send('MESSAGE-FROM-CALLEE');
        self::pump($caller, $callee, $aOut, $bOut);
        $this->assertSame(['MESSAGE-FROM-CALLEE'], $aGot);

        // A message larger than one SCTP packet must be fragmented and reassembled.
        $large = str_repeat('X', 5000);
        $caller->send($large);
        self::pump($caller, $callee, $aOut, $bOut);
        $this->assertContains($large, $bGot, 'a fragmented message must arrive intact');
    }

    /**
     * Messages queued before the association is up must not be lost.
     */
    public function testSctpSignalingQueuesUntilEstablished(): void
    {
        $aOut = [];
        $bOut = [];
        $bGot = [];

        $caller = new SignalingSctpTransport(
            true,
            static function (string $p) use (&$aOut): void {
                $aOut[] = $p;
            },
            static function (string $m): void {
            }
        );
        // Send before anything has been exchanged with the peer.
        $caller->send('EARLY-MESSAGE');

        $callee = new SignalingSctpTransport(
            false,
            static function (string $p) use (&$bOut): void {
                $bOut[] = $p;
            },
            static function (string $m) use (&$bGot): void {
                $bGot[] = $m;
            }
        );

        self::pump($caller, $callee, $aOut, $bOut);
        $caller->send('LATER-MESSAGE');
        self::pump($caller, $callee, $aOut, $bOut);

        $this->assertSame(['EARLY-MESSAGE', 'LATER-MESSAGE'], $bGot, 'nothing may be dropped or reordered');
    }

    // ------------------------------------------------------------ SSRC conversion

    public function testSsrcSignConversionRoundTrips(): void
    {
        foreach ([0, 1, 0x7FFFFFFF, 0x80000000, 0xFFFFFFFF] as $unsigned) {
            $this->assertSame(
                $unsigned,
                GroupSdp::toUnsignedSsrc(GroupSdp::toSignedSsrc($unsigned)),
                "SSRC $unsigned must survive the round trip"
            );
        }
        $this->assertSame(-1, GroupSdp::toSignedSsrc(0xFFFFFFFF));
        $this->assertSame(0x7FFFFFFF, GroupSdp::toSignedSsrc(0x7FFFFFFF));
    }
}
