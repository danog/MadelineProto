<?php

declare(strict_types=1);

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

use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\Crypt\AesCtr;
use danog\MadelineProto\RSA;
use danog\MadelineProto\Settings\Connection;
use danog\MadelineProto\Tgcalls\E2E\Crypto;
use danog\MadelineProto\TL\TL;
use phpseclib4\Crypt\AES;
use phpseclib4\Math\BigInteger;
use PHPUnit\Framework\TestCase;

/**
 * OpenSSL and libsodium are used first, phpseclib only without them: every backend must give the same results.
 */
final class CryptoBackendsTest extends TestCase
{
    private bool $hasOpenssl;

    protected function setUp(): void
    {
        if (!\extension_loaded('openssl')) {
            self::markTestSkipped('The openssl extension is required to compare the backends');
        }
        Magic::start(light: false);
        $this->hasOpenssl = Magic::$hasOpenssl;
    }

    protected function tearDown(): void
    {
        Magic::$hasOpenssl = $this->hasOpenssl;
        Crypto::$backend = null;
    }

    /** @return iterable<string, array{string}> */
    public static function ivs(): iterable
    {
        yield 'random' => [random_bytes(16)];
        // The counter is 128-bit: the carry goes past the low 64 bits, and wraps around.
        yield 'carry past 64 bits' => [random_bytes(8).str_repeat("\xff", 8)];
        yield 'wraparound' => [str_repeat("\xff", 15)."\xf0"];
    }

    /** @dataProvider ivs */
    public function testContinuousCtrMatchesPhpseclib(string $iv): void
    {
        $key = random_bytes(32);
        $reference = new AES('ctr');
        $reference->enableContinuousBuffer();
        $reference->setKey($key);
        $reference->setIV($iv);

        Magic::$hasOpenssl = true;
        $openssl = new AesCtr($key, $iv);
        Magic::$hasOpenssl = false;
        $phpseclib = new AesCtr($key, $iv);

        // Chunks that don't end at block boundaries, of every length around one.
        foreach ([1, 15, 16, 17, 0, 3, 64, 31, 33, 1000, 5, 4096, 7] as $length) {
            $chunk = $length === 0 ? '' : random_bytes($length);
            $expected = $reference->encrypt($chunk);
            self::assertSame($expected, $openssl->encrypt($chunk), "$length bytes, with OpenSSL");
            self::assertSame($expected, $phpseclib->encrypt($chunk), "$length bytes, with phpseclib");
        }
    }

    public function testCtrEncryptMatchesPhpseclib(): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(16);
        $data = random_bytes(1000);
        Magic::$hasOpenssl = false;
        $expected = Crypt::ctrEncrypt($data, $key, $iv);
        Magic::$hasOpenssl = true;
        self::assertSame($expected, Crypt::ctrEncrypt($data, $key, $iv));
    }

    public function testRsaMatchesPhpseclib(): void
    {
        $tl = new TL(null);
        foreach ((new Connection)->getRsaKeys() as $pem) {
            Magic::$hasOpenssl = false;
            $phpseclib = RSA::load($tl, $pem);
            Magic::$hasOpenssl = true;
            $openssl = RSA::load($tl, $pem);
            self::assertTrue($phpseclib->n->equals($openssl->n));
            self::assertTrue($phpseclib->e->equals($openssl->e));
            self::assertSame($phpseclib->fp, $openssl->fp);

            // Data below the modulus, as RSA_PAD makes sure.
            $data = new BigInteger("\0".random_bytes(255), 256);
            $expected = str_pad($data->powMod($openssl->e, $openssl->n)->toBytes(), 256, "\0", STR_PAD_LEFT);
            self::assertSame($expected, $openssl->encrypt($data));
            // Unserialized: the OpenSSL key is built again from the modulus and the exponent.
            self::assertSame($expected, unserialize(serialize($openssl))->encrypt($data));
            Magic::$hasOpenssl = false;
            self::assertSame($expected, $phpseclib->encrypt($data));
        }
    }

    /** @return iterable<string, array{string}> */
    public static function curve25519Backends(): iterable
    {
        yield 'openssl' => ['openssl'];
        yield 'phpseclib' => ['phpseclib'];
    }

    /** @dataProvider curve25519Backends */
    public function testCurve25519MatchesSodium(string $backend): void
    {
        if (!\extension_loaded('sodium')) {
            self::markTestSkipped('The sodium extension is the reference');
        }
        if ($backend === 'openssl' && !\defined('OPENSSL_KEYTYPE_ED25519')) {
            self::markTestSkipped('OpenSSL Curve25519 needs PHP 8.4');
        }
        [$seed, $public] = Crypto::generateKeyPair();
        [$peerSeed, $peerPublic] = Crypto::generateKeyPair();
        $signature = Crypto::sign($seed, 'message');
        $shared = Crypto::computeSharedSecret($seed, $peerPublic);

        Crypto::$backend = $backend;
        self::assertSame($backend, Crypto::backend());
        self::assertSame($public, Crypto::publicKey($seed));
        self::assertSame($signature, Crypto::sign($seed, 'message'));
        self::assertTrue(Crypto::verify($signature, 'message', $public));
        self::assertFalse(Crypto::verify($signature, 'other message', $public));
        self::assertFalse(Crypto::verify($signature, 'message', $peerPublic));
        self::assertSame($shared, Crypto::computeSharedSecret($seed, $peerPublic));
        self::assertSame($shared, Crypto::computeSharedSecret($peerSeed, $public));
    }
}
