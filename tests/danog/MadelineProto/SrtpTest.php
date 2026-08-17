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

use Webrtc\Srtp\Enum\SrtpProfile;
use Webrtc\Srtp\Enum\SsrcType;
use Webrtc\Srtp\Exception\SrtpExceptionInterface;
use Webrtc\Srtp\Policy;
use Webrtc\Srtp\Session;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Tests the pure-PHP SRTP implementation used by one-to-one and group calls.
 *
 * @internal
 */
final class SrtpTest extends TestCase
{
    /**
     * The key derivation function of RFC 3711, checked against the test vectors of appendix B.3.
     */
    public function testRfc3711KeyDerivationVectors(): void
    {
        $masterKey = hex2bin('E1F97A0D3E018BE0D64FA32C06DE4139');
        $masterSalt = hex2bin('0EC675AD498AFEEBB6960B3AABE6');

        $derive = new ReflectionMethod(Session::class, 'derive');
        $derive->setAccessible(true);

        $this->assertSame(
            'c61e7a93744f39ee10734afe3ff7a087',
            bin2hex($derive->invoke(null, $masterKey, $masterSalt, 0x00, 16)),
            'cipher key'
        );
        $this->assertSame(
            'cebe321f6ff7716b6fd4ab49af256a156d38baa4',
            bin2hex($derive->invoke(null, $masterKey, $masterSalt, 0x01, 20)),
            'authentication key'
        );
        $this->assertSame(
            '30cbbc08863d8c85d49db34a9ae1',
            bin2hex($derive->invoke(null, $masterKey, $masterSalt, 0x02, 14)),
            'session salt'
        );
    }

    /**
     * @return list<array{SrtpProfile, int}>
     */
    public static function provideProfiles(): array
    {
        return [
            'AES_CM_128_HMAC_SHA1_80' => [SrtpProfile::AES128_CM_SHA1_80, 30],
            'AES_CM_128_HMAC_SHA1_32' => [SrtpProfile::AES128_CM_SHA1_32, 30],
            'AEAD_AES_128_GCM' => [SrtpProfile::AEAD_AES_128_GCM, 28],
            'AEAD_AES_256_GCM' => [SrtpProfile::AEAD_AES_256_GCM, 44],
        ];
    }

    private static function session(SrtpProfile $profile, string $keyMaterial): Session
    {
        return new Session(new Policy($profile, $keyMaterial, SsrcType::ANY_OUTBOUND));
    }

    private static function rtp(int $seq, int $ssrc, string $payload): string
    {
        return pack('CCnNN', 0x80, 111, $seq, $seq * 960, $ssrc).$payload;
    }

    /**
     * @dataProvider provideProfiles
     */
    public function testRtpRoundTrip(SrtpProfile $profile, int $keyLength): void
    {
        $keyMaterial = random_bytes($keyLength);
        $tx = self::session($profile, $keyMaterial);
        $rx = self::session($profile, $keyMaterial);

        for ($i = 0; $i < 8; $i++) {
            $packet = self::rtp(6000 + $i, 0xDEADBEEF, "payload-$i-".str_repeat('x', 40));
            $protected = $tx->protect($packet);

            $this->assertNotSame(
                substr($packet, 12),
                substr($protected, 12, \strlen($packet) - 12),
                'the payload must actually be encrypted'
            );
            $this->assertSame($packet, $rx->unprotect($protected));
        }
    }

    /**
     * @dataProvider provideProfiles
     */
    public function testRtcpRoundTrip(SrtpProfile $profile, int $keyLength): void
    {
        $keyMaterial = random_bytes($keyLength);
        $tx = self::session($profile, $keyMaterial);
        $rx = self::session($profile, $keyMaterial);

        $packet = pack('CCn', 0x80, 200, 6).pack('N', 0xDEADBEEF).str_repeat("\x11", 24);
        $this->assertSame($packet, $rx->unprotectRtcp($tx->protectRtcp($packet)));
    }

    /**
     * @dataProvider provideProfiles
     */
    public function testReplayIsRejected(SrtpProfile $profile, int $keyLength): void
    {
        $keyMaterial = random_bytes($keyLength);
        $tx = self::session($profile, $keyMaterial);
        $rx = self::session($profile, $keyMaterial);

        $protected = $tx->protect(self::rtp(1234, 0xCAFE, 'hello'));
        $this->assertNotSame('', $rx->unprotect($protected));

        $this->expectException(SrtpExceptionInterface::class);
        $rx->unprotect($protected);
    }

    /**
     * @dataProvider provideProfiles
     */
    public function testTamperingIsRejected(SrtpProfile $profile, int $keyLength): void
    {
        $keyMaterial = random_bytes($keyLength);
        $tx = self::session($profile, $keyMaterial);
        $rx = self::session($profile, $keyMaterial);

        $protected = $tx->protect(self::rtp(4321, 0xCAFE, 'hello world, this is a payload'));
        $protected[20] = \chr(\ord($protected[20]) ^ 0xFF);

        $this->expectException(SrtpExceptionInterface::class);
        $rx->unprotect($protected);
    }

    /**
     * The 16-bit sequence number wraps every 65536 packets; the rollover counter must follow.
     */
    public function testSequenceNumberRollover(): void
    {
        $keyMaterial = random_bytes(30);
        $tx = self::session(SrtpProfile::AES128_CM_SHA1_80, $keyMaterial);
        $rx = self::session(SrtpProfile::AES128_CM_SHA1_80, $keyMaterial);

        foreach ([65534, 65535, 0, 1, 2] as $seq) {
            $packet = self::rtp($seq, 0x1234, 'rollover');
            $this->assertSame($packet, $rx->unprotect($tx->protect($packet)));
        }
    }

    /**
     * Header extensions are authenticated but must stay in the clear.
     */
    public function testHeaderExtensionIsNotEncrypted(): void
    {
        $keyMaterial = random_bytes(30);
        $tx = self::session(SrtpProfile::AES128_CM_SHA1_80, $keyMaterial);
        $rx = self::session(SrtpProfile::AES128_CM_SHA1_80, $keyMaterial);

        // X flag set, one 4-byte extension word.
        $packet = pack('CCnNN', 0x90, 111, 7, 700, 0xABCD)
            .pack('nn', 0xBEDE, 1).pack('N', 0x10AABBCC)
            ."the payload";
        $protected = $tx->protect($packet);

        $this->assertSame(substr($packet, 0, 20), substr($protected, 0, 20));
        $this->assertSame($packet, $rx->unprotect($protected));
    }
}
