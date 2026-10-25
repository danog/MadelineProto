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
 * @author    Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\Crypt\IGE;
use danog\MadelineProto\MTProtoTools\Crypt\IGEEVP;
use PHPUnit\Framework\TestCase;

final class IGETest extends TestCase
{
    private const KEY = '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f';
    private const IV = '202122232425262728292a2b2c2d2e2f303132333435363738393a3b3c3d3e3f';
    private const PLAIN = '404142434445464748494a4b4c4d4e4f505152535455565758595a5b5c5d5e5f606162636465666768696a6b6c6d6e6f707172737475767778797a7b7c7d7e7f';
    private const ENCRYPTED = 'b6b23cb46d2f43de2c67fc9a3a9e35104fad6ed15177969c1cebc616bcfa482cb220e4d159bedfd570df191a805e9d9d13b6d62f0ea1e40541bd31ebe72f51c6';

    protected function setUp(): void
    {
        Magic::start(light: false);
    }

    public function testKnownCiphertextCanBeReadAndWritten(): void
    {
        $key = hex2bin(self::KEY);
        $iv = hex2bin(self::IV);
        $plain = hex2bin(self::PLAIN);
        $encrypted = hex2bin(self::ENCRYPTED);
        self::assertSame($encrypted, Crypt::igeEncrypt($plain, $key, $iv));
        self::assertSame($plain, Crypt::igeDecrypt($encrypted, $key, $iv));
        self::assertSame($encrypted, IGE::getInstance($key, $iv)->encrypt($plain));
        self::assertSame($plain, IGE::getInstance($key, $iv)->decrypt($encrypted));
    }

    public function testAvailableEvpBackendIsPreferred(): void
    {
        if (!IGEEVP::isAvailable()) {
            self::markTestSkipped('The optional EVP-IGE helper is not available');
        }
        self::assertInstanceOf(IGEEVP::class, IGE::getInstance(hex2bin(self::KEY), hex2bin(self::IV)));
    }

    /** @return iterable<string, array{bool}> */
    public static function directions(): iterable
    {
        yield 'encrypt' => [false];
        yield 'decrypt' => [true];
    }

    /** @dataProvider directions */
    public function testRejectedPartPreservesTransferState(bool $decrypt): void
    {
        if (!IGEEVP::isAvailable()) {
            self::markTestSkipped('The optional EVP-IGE helper is not available');
        }
        $iv = hex2bin(self::IV);
        $cipher = IGE::getInstance(hex2bin(self::KEY), $iv);
        $method = $decrypt ? 'decrypt' : 'encrypt';
        try {
            $cipher->$method(str_repeat('x', 15));
            self::fail('An incomplete AES block must be rejected');
        } catch (\ValueError) {
            self::assertSame($iv, $cipher->getState());
        }
        self::assertSame(hex2bin($decrypt ? self::PLAIN : self::ENCRYPTED), $cipher->$method(hex2bin($decrypt ? self::ENCRYPTED : self::PLAIN)));
    }

    /** @return iterable<string, array{bool}> */
    public static function transfers(): iterable
    {
        yield 'continuous transfer' => [false];
        yield 'resume between chunks' => [true];
    }

    /** @dataProvider transfers */
    public function testFileCanBeTransferredInChunks(bool $resume): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $file = random_bytes(1024 * 1024);
        $encoder = IGE::getInstance($key, $iv);
        $encrypted = '';
        foreach (str_split($file, 65536) as $chunk) {
            $encrypted .= $encoder->encrypt('').$encoder->encrypt($chunk);
            if ($resume) {
                $encoder = IGE::getInstance($key, $encoder->getState());
            }
        }
        self::assertSame(Crypt::igeEncrypt($file, $key, $iv), $encrypted);
        self::assertSame($file, Crypt::igeDecrypt($encrypted, $key, $iv));

        $decoder = IGE::getInstance($key, $iv);
        $downloaded = '';
        // Receiving chunks need not have the same boundaries as sending chunks.
        foreach (str_split($encrypted, 32768) as $chunk) {
            $downloaded .= $decoder->decrypt('').$decoder->decrypt($chunk);
            if ($resume) {
                $decoder = IGE::getInstance($key, $decoder->getState());
            }
        }
        self::assertSame($file, $downloaded);
    }

    public function testEmptyPayload(): void
    {
        $key = hex2bin(self::KEY);
        $iv = hex2bin(self::IV);
        self::assertSame('', Crypt::igeEncrypt('', $key, $iv));
        self::assertSame('', Crypt::igeDecrypt('', $key, $iv));
        $cipher = IGE::getInstance($key, $iv);
        self::assertSame('', $cipher->encrypt('').$cipher->decrypt(''));
        self::assertSame($iv, $cipher->getState());
    }
}
