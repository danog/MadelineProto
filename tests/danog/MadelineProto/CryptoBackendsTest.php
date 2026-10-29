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
use danog\MadelineProto\MTProtoTools\Crypt\IGE;
use danog\MadelineProto\MTProtoTools\Crypt\IGEOpenssl;
use danog\MadelineProto\MTProtoTools\Crypt\IGEPhp;
use danog\MadelineProto\RSA;
use danog\MadelineProto\Settings\Connection;
use danog\MadelineProto\Tgcalls\E2E\Crypto;
use danog\MadelineProto\TL\TL;
use phpseclib4\Crypt\AES;
use phpseclib4\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use ValueError;

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

    /** @return iterable<string, array{int}> */
    public static function igeSizes(): iterable
    {
        foreach ([16, 32, 48, 4096, 65536 + 16, 200000 - 200000 % 16] as $size) {
            yield "$size bytes" => [$size];
        }
    }

    /**
     * With OpenSSL, IGE encryption runs the whole chain as one CBC call; without it, IGE uses its own
     * AES rounds: both must match the definition (one ECB block at a time), also continued in chunks.
     *
     * @dataProvider igeSizes
     */
    public function testIgeBackendsMatchTheDefinition(int $size): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $plaintext = random_bytes($size);
        $ciphertext = self::igeDefinition($plaintext, $key, $iv);
        // Continue at a random block boundary.
        $cut = 16 * random_int(1, $size / 16);
        foreach ([true => IGEOpenssl::class, false => IGEPhp::class] as $openssl => $class) {
            Magic::$hasOpenssl = (bool) $openssl;
            self::assertInstanceOf($class, IGE::getInstance($key, $iv));
            self::assertSame($ciphertext, Crypt::igeEncrypt($plaintext, $key, $iv), $class);
            self::assertSame($plaintext, Crypt::igeDecrypt($ciphertext, $key, $iv), $class);

            $encrypt = IGE::getInstance($key, $iv);
            $decrypt = IGE::getInstance($key, $iv);
            self::assertSame('', $encrypt->encrypt(''));
            self::assertSame('', $decrypt->decrypt(''));
            self::assertSame($ciphertext, $encrypt->encrypt(substr($plaintext, 0, $cut)).$encrypt->encrypt(substr($plaintext, $cut)), $class);
            self::assertSame($plaintext, $decrypt->decrypt(substr($ciphertext, 0, $cut)).$decrypt->decrypt(substr($ciphertext, $cut)), $class);
            $state = substr($ciphertext, -16).substr($plaintext, -16);
            self::assertSame($state, $encrypt->getState(), $class);
            self::assertSame($state, $decrypt->getState(), $class);
        }
    }

    public function testIgeBackendsMatchAKnownCiphertext(): void
    {
        $key = hex2bin('000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f');
        $iv = hex2bin('202122232425262728292a2b2c2d2e2f303132333435363738393a3b3c3d3e3f');
        $plaintext = hex2bin('404142434445464748494a4b4c4d4e4f505152535455565758595a5b5c5d5e5f606162636465666768696a6b6c6d6e6f707172737475767778797a7b7c7d7e7f');
        $ciphertext = hex2bin('b6b23cb46d2f43de2c67fc9a3a9e35104fad6ed15177969c1cebc616bcfa482cb220e4d159bedfd570df191a805e9d9d13b6d62f0ea1e40541bd31ebe72f51c6');
        foreach ([true, false] as $openssl) {
            Magic::$hasOpenssl = $openssl;
            self::assertSame($ciphertext, Crypt::igeEncrypt($plaintext, $key, $iv));
            self::assertSame($plaintext, Crypt::igeDecrypt($ciphertext, $key, $iv));
        }
    }

    public function testIgeRejectsPartialBlocks(): void
    {
        foreach ([true, false] as $openssl) {
            Magic::$hasOpenssl = $openssl;
            $ige = IGE::getInstance(random_bytes(32), $iv = random_bytes(32));
            foreach (['encrypt', 'decrypt'] as $method) {
                try {
                    $ige->$method(random_bytes(24));
                    self::fail("$method accepted a partial block");
                } catch (ValueError) {
                    self::assertSame($iv, $ige->getState());
                }
            }
        }
    }

    public function testGeneratedPhpIgeBackendIsUpToDate(): void
    {
        $generated = tempnam(sys_get_temp_dir(), 'IGEPhp');
        try {
            exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../../../tools/gen_ige_php.php').' '.escapeshellarg($generated), result_code: $code);
            self::assertSame(0, $code);
            self::assertFileEquals($generated, __DIR__.'/../../../src/MTProtoTools/Crypt/IGEPhp.php', 'Run php tools/gen_ige_php.php');
        } finally {
            unlink($generated);
        }
    }

    /** IGE as defined: c_i = E(p_i ^ c_{i-1}) ^ p_{i-1}, with c_0 and p_0 the two halves of the IV. */
    private static function igeDefinition(string $plaintext, string $key, string $iv): string
    {
        [$previousCipher, $previousPlain] = str_split($iv, 16);
        $ciphertext = '';
        foreach (str_split($plaintext, 16) as $plain) {
            $previousCipher = openssl_encrypt($plain ^ $previousCipher, 'aes-256-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING) ^ $previousPlain;
            $previousPlain = $plain;
            $ciphertext .= $previousCipher;
        }
        return $ciphertext;
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
