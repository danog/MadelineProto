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

use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\Crypt\IGEPhp;
use danog\MadelineProto\Native\AesCbc;
use danog\MadelineProto\Native\AesCtr;
use danog\MadelineProto\Native\AesCtrKey;
use danog\MadelineProto\Native\AesGcm;
use danog\MadelineProto\Native\AesIge;
use danog\MadelineProto\Native\Hash;
use danog\MadelineProto\Native\Hmac;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ValueError;

/**
 * The madeline_crypto 0.5 primitives against the PHP builtins they replace, on random inputs.
 */
final class NativePrimitivesTest extends TestCase
{
    private const ALGOS = ['md5', 'sha1', 'sha256', 'sha384', 'sha512'];
    /** Packet sizes plus the edges of blocks and of OpenSSL's internal chunks. */
    private const SIZES = [0, 1, 15, 16, 17, 31, 63, 64, 65, 127, 128, 129, 160, 1200, 4095, 65537];

    protected function setUp(): void
    {
        if (!Crypt::native()) {
            self::markTestSkipped('The madeline_crypto extension (API 0.5) is not loaded');
        }
    }

    public function testAesGcmMatchesOpenssl(): void
    {
        foreach ([16, 24, 32] as $keyLength) {
            $key = self::rnd($keyLength);
            $gcm = new AesGcm($key);
            $cipher = 'aes-'.($keyLength * 8).'-gcm';
            foreach (self::SIZES as $size) {
                $plain = self::rnd($size);
                $nonce = self::rnd(12);
                $aad = self::rnd(random_int(0, 40));
                $expected = openssl_encrypt($plain, $cipher, $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
                $sealed = $gcm->encrypt($nonce, $aad, $plain);
                self::assertSame($expected.$tag, $sealed, "$cipher, $size bytes");
                self::assertSame($plain, $gcm->decrypt($nonce, $aad, $expected, $tag));
                $forged = $tag;
                $byte = random_int(0, 15);
                $forged[$byte] = $forged[$byte] ^ "\x01";
                self::assertFalse($gcm->decrypt($nonce, $aad, $expected, $forged));
                self::assertFalse($gcm->decrypt($nonce, $aad."x", $expected, $tag));
            }
        }
        foreach ([[self::rnd(15)], [self::rnd(33)]] as [$key]) {
            $this->assertValueError(static fn () => new AesGcm($key));
        }
        $gcm = new AesGcm(self::rnd(16));
        $this->assertValueError(static fn () => $gcm->encrypt(self::rnd(16), '', 'x'));
        $this->assertValueError(static fn () => $gcm->decrypt(self::rnd(12), '', 'x', self::rnd(8)));
    }

    public function testAesCtrMatchesOpenssl(): void
    {
        foreach ([16, 24, 32] as $keyLength) {
            $key = self::rnd($keyLength);
            $cipher = 'aes-'.($keyLength * 8).'-ctr';
            // An IV close to the 128-bit wraparound, and random ones.
            foreach ([str_repeat("\xff", 15)."\xf0", self::rnd(16)] as $iv) {
                $plain = self::rnd(5000);
                $expected = openssl_encrypt($plain, $cipher, $key, OPENSSL_RAW_DATA, $iv);
                self::assertSame($expected, AesCtr::once($key, $iv, $plain));
                // Byte-granular continuation.
                $ctr = new AesCtr($key, $iv);
                $out = '';
                for ($offset = 0; $offset < \strlen($plain); $offset += $length) {
                    $length = random_int(0, 100);
                    $out .= $ctr->process(substr($plain, $offset, $length));
                }
                self::assertSame($expected, $out);
                // The stateful wrapper of the transport.
                $wrapper = new Crypt\AesCtr($key, $iv);
                self::assertSame($expected, $wrapper->encrypt(substr($plain, 0, 7)).$wrapper->encrypt(substr($plain, 7)));
                $keyed = new AesCtrKey($key);
                foreach (self::SIZES as $size) {
                    $iv = self::rnd(16);
                    $data = self::rnd($size);
                    self::assertSame(openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv), $keyed->apply($iv, $data));
                }
            }
        }
        $this->assertValueError(static fn () => new AesCtr(self::rnd(16), self::rnd(8)));
        $this->assertValueError(static fn () => (new AesCtrKey(self::rnd(16)))->apply(self::rnd(12), 'x'));
    }

    public function testAesCbcAndIgeMatchTheirFallbacks(): void
    {
        foreach ([16, 24, 32] as $keyLength) {
            $key = self::rnd($keyLength);
            $iv = self::rnd(16);
            $cipher = 'aes-'.($keyLength * 8).'-cbc';
            foreach ([0, 16, 32, 1024] as $size) {
                $plain = self::rnd($size);
                $expected = (string) openssl_encrypt($plain, $cipher, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
                self::assertSame($expected, AesCbc::encrypt($key, $iv, $plain));
                self::assertSame($plain, AesCbc::decrypt($key, $iv, $expected));
            }
        }
        $this->assertValueError(static fn () => AesCbc::encrypt(self::rnd(32), self::rnd(16), 'x'));

        $key = self::rnd(32);
        $iv = self::rnd(32);
        $plain = self::rnd(4096);
        $reference = (new ReflectionClass(IGEPhp::class))->newInstanceWithoutConstructor();
        (new \ReflectionMethod($reference, '__construct'))->invoke($reference, $key, $iv);
        $expected = $reference->encrypt($plain);
        self::assertSame($expected, AesIge::encrypt($key, $iv, $plain));
        self::assertSame($plain, AesIge::decrypt($key, $iv, $expected));
        self::assertSame($expected, Crypt::igeEncrypt($plain, $key, $iv));
        // The synchronous handler continues the chain like the others.
        $sync = Crypt\IGE::getSyncInstance($key, $iv);
        self::assertSame($expected, $sync->encrypt(substr($plain, 0, 48)).$sync->encrypt(substr($plain, 48)));
        self::assertSame($reference->getState(), $sync->getState());
        $this->assertValueError(static fn () => AesIge::encrypt($key, $iv, 'x'));
        $this->assertValueError(static fn () => AesIge::encrypt($key, self::rnd(16), ''));
    }

    public function testHashesAndHmacsMatchPhp(): void
    {
        foreach (self::ALGOS as $algo) {
            foreach (self::SIZES as $size) {
                $data = self::rnd($size);
                self::assertSame(hash($algo, $data, true), Hash::digest($algo, $data), "$algo, $size bytes");
                $incremental = new Hash($algo);
                for ($offset = 0; $offset < $size; $offset += $length) {
                    $length = random_int(1, 200);
                    $incremental->update(substr($data, $offset, $length));
                }
                self::assertSame(hash($algo, $data, true), $incremental->final());
                foreach ([0, 20, 64, 128, 129, 300] as $keyLength) {
                    $key = self::rnd($keyLength);
                    $expected = hash_hmac($algo, $data, $key, true);
                    self::assertSame($expected, Hmac::compute($algo, $key, $data));
                    $hmac = new Hmac($algo, $key);
                    self::assertSame($expected, $hmac->mac($data));
                    self::assertSame($expected, $hmac->mac($data));
                }
            }
        }
        $finished = new Hash('sha1');
        $finished->final();
        try {
            $finished->update('x');
            self::fail('A finalized hash was updated');
        } catch (\Error $e) {
            self::assertNotInstanceOf(ValueError::class, $e);
        }
        $this->assertValueError(static fn () => Hash::digest('sha3-256', ''));
        $this->assertValueError(static fn () => new Hmac('crc32b', ''));
        // The helpers.
        $data = self::rnd(100);
        self::assertSame(sha1($data, true), Crypt::sha1($data));
        self::assertSame(hash('sha256', $data, true), Crypt::sha256($data));
        self::assertSame(hash('sha512', $data, true), Crypt::sha512($data));
        self::assertSame(md5($data, true), Crypt::md5($data));
        self::assertSame(hash_hmac('sha256', $data, 'k', true), Crypt::hmacSha256($data, 'k'));
    }

    /**
     * Serializing would silently drop the key: the classes are not serializable (nor clonable), and neither are the
     * MadelineProto wrappers that hold them.
     */
    public function testKeyObjectsAreNeitherClonableNorSerializable(): void
    {
        $key = self::rnd(32);
        $objects = [
            new AesGcm(self::rnd(16)), new AesCtr(self::rnd(16), self::rnd(16)), new AesCtrKey(self::rnd(16)),
            new Hmac('sha1', 'k'), new Hash('sha1'),
            new Crypt\AesCtr(self::rnd(16), self::rnd(16)), Crypt\IGE::getSyncInstance($key, self::rnd(32)),
        ];
        self::assertInstanceOf(Crypt\IGENativeSync::class, $objects[6]);
        foreach ($objects as $object) {
            if (str_starts_with($object::class, 'danog\\MadelineProto\\Native\\')) {
                self::assertSame('Error', self::failure(static fn () => clone $object), 'Cloned '.$object::class);
            }
            self::assertNotSame('', self::failure(static fn () => serialize($object)), 'Serialized '.$object::class);
        }
        foreach ([AesGcm::class, AesCtr::class, AesCtrKey::class, AesCbc::class, AesIge::class, Hmac::class, Hash::class] as $class) {
            $payload = 'O:'.\strlen($class).':"'.$class.'":0:{}';
            self::assertSame('Exception', self::failure(static fn () => unserialize($payload)), "Unserialized $class");
        }
    }

    /** The class of what the operation threw, or '' if it didn't. */
    private static function failure(\Closure $operation): string
    {
        try {
            $operation();
        } catch (\Throwable $e) {
            return $e::class;
        }
        return '';
    }

    private function assertValueError(\Closure $operation): void
    {
        try {
            $operation();
        } catch (ValueError) {
            $this->addToAssertionCount(1);
            return;
        }
        self::fail('No ValueError');
    }

    private static function rnd(int $length): string
    {
        return $length ? random_bytes($length) : '';
    }

}
