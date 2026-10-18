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

use danog\MadelineProto\Conversion;
use danog\MadelineProto\Exception;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\SecurityException;
use PHPUnit\Framework\TestCase;

/**
 * Parsing Telegram Desktop sessions, written here as tdesktop writes them (storage_domain.cpp, storage_account.cpp, main_account.cpp).
 */
final class TdesktopConversionTest extends TestCase
{
    private string $tdata;

    protected function setUp(): void
    {
        $this->tdata = sys_get_temp_dir().'/madeline-tdesktop-'.bin2hex(random_bytes(8)).'/tdata';
        mkdir($this->tdata, 0o777, true);
    }

    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\dirname($this->tdata), \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir(\dirname($this->tdata));
    }

    public function testWideIdsAndAccountIndexes(): void
    {
        $localKey = random_bytes(256);
        $keys = [2 => random_bytes(256), 4 => random_bytes(256)];
        $salt = random_bytes(32);
        $this->writeKeyData([
            $salt,
            self::encrypt($localKey, self::localKey('', $salt)),
            self::encrypt(self::int(2).self::int(0).self::int(1).self::int(1), $localKey),
        ], self::trailer(0, []));

        $wide = pack('J', -1).pack('J', 5_000_000_000).self::int(2).self::keys($keys).self::keys([1 => random_bytes(256)]);
        $this->writeMtp('data#2', self::int(0x4b).self::bytes($wide), $localKey);
        $legacy = self::int(1234).self::int(4).self::keys([4 => $keys[4]]).self::keys([]);
        $this->writeMtp('data', self::int(0x4b).self::bytes($legacy), $localKey);

        // The trailing active index selects the account by default
        self::assertSame(
            ['main_dc_id' => 2, 'user_id' => 5_000_000_000, 'auth_keys' => $keys],
            Conversion::parseTdesktop(\dirname($this->tdata)),
        );
        self::assertSame(
            ['main_dc_id' => 4, 'user_id' => 1234, 'auth_keys' => [4 => $keys[4]]],
            Conversion::parseTdesktop($this->tdata, account: 0),
        );
        $this->expectException(Exception::class);
        Conversion::parseTdesktop($this->tdata, account: 2);
    }

    public function testPasscodeWrap(): void
    {
        $localKey = random_bytes(256);
        $wrapSalt = random_bytes(32);
        $key = random_bytes(256);
        $this->writeKeyData([random_bytes(32), '', self::encrypt(self::int(1).self::int(0), $localKey)], self::trailer(3, [
            [0, 2, random_bytes(32), random_bytes(288)], // Stale
            [0, 3, $wrapSalt, self::encrypt($localKey, self::localKey('pass', $wrapSalt))],
        ]));
        $this->writeMtp('data', self::int(0x4b).self::bytes(pack('J', -1).pack('J', 101374607).self::int(1).self::keys([1 => $key]).self::keys([])), $localKey);

        self::assertSame(['main_dc_id' => 1, 'user_id' => 101374607, 'auth_keys' => [1 => $key]], Conversion::parseTdesktop($this->tdata, 'pass'));

        $this->expectException(SecurityException::class);
        Conversion::parseTdesktop($this->tdata, 'wrong');
    }

    public function testNoPasscode(): void
    {
        $this->writeKeyData([random_bytes(32), '', random_bytes(32)], self::trailer(1, [[0, 1, random_bytes(32), random_bytes(288)]]));

        $this->expectExceptionMessage('protected by a local passcode');
        Conversion::parseTdesktop($this->tdata);
    }

    public function testMemoryHardPasscodeWrap(): void
    {
        $this->writeKeyData([random_bytes(32), '', random_bytes(32)], self::trailer(1, [[1, 1, random_bytes(32), random_bytes(288)]]));

        $this->expectExceptionMessage('argon2id or scrypt');
        Conversion::parseTdesktop($this->tdata, 'pass');
    }

    public function testLegacyKeyData(): void
    {
        $localKey = random_bytes(256);
        $salt = random_bytes(32);
        $key = random_bytes(256);
        $this->writeKeyData([
            $salt,
            self::encrypt($localKey, self::localKey('pass', $salt)),
            self::encrypt(self::int(1).self::int(0), $localKey),
        ], null);
        $this->writeMtp('data', self::int(0x4b).self::bytes(self::int(1234).self::int(5).self::keys([5 => $key]).self::keys([])), $localKey);

        self::assertSame(['main_dc_id' => 5, 'user_id' => 1234, 'auth_keys' => [5 => $key]], Conversion::parseTdesktop($this->tdata, 'pass'));
    }

    public function testLegacyMap(): void
    {
        $localKey = random_bytes(256);
        $salt = random_bytes(32);
        $key = random_bytes(256);
        mkdir($this->tdata.'/'.self::filePart('data'));
        self::writeFile($this->tdata.'/'.self::filePart('data').'/map', [
            $salt,
            self::encrypt($localKey, openssl_pbkdf2('', $salt, 256, 4, 'sha1')),
            self::encrypt('', $localKey),
        ]);
        $this->writeMtp('data', self::int(0x01).self::int(1234).self::int(3).self::int(0x00).self::int(3).$key, $localKey);

        self::assertSame(['main_dc_id' => 3, 'user_id' => 1234, 'auth_keys' => [3 => $key]], Conversion::parseTdesktop($this->tdata));
    }

    /**
     * @param list<string> $blobs
     */
    private function writeKeyData(array $blobs, ?string $trailer): void
    {
        if ($trailer !== null) {
            $blobs[] = $trailer;
        }
        self::writeFile($this->tdata.'/key_data', $blobs);
    }

    private function writeMtp(string $dataName, string $blocks, string $localKey): void
    {
        self::writeFile($this->tdata.'/'.self::filePart($dataName), [self::encrypt($blocks, $localKey)]);
    }

    /**
     * @param list<array{int, int, string, string}> $wraps Kind, generation, salt, encrypted key
     */
    private static function trailer(int $committed, array $wraps): string
    {
        $result = self::int(0x4B443200).self::int(2).self::int($committed).self::int(\count($wraps));
        foreach ($wraps as [$kind, $generation, $salt, $keyEncrypted]) {
            $result .= self::int($kind).self::int($generation).self::int(65536).self::int(8).self::int(1).self::bytes($salt).self::bytes($keyEncrypted);
        }
        return $result;
    }

    /**
     * @param array<int, string> $keys
     */
    private static function keys(array $keys): string
    {
        $result = self::int(\count($keys));
        foreach ($keys as $dc => $key) {
            $result .= self::int($dc).$key;
        }
        return $result;
    }

    private static function int(int $value): string
    {
        return pack('N', $value);
    }

    private static function bytes(string $value): string
    {
        return pack('N', \strlen($value)).$value;
    }

    /** FileWriteDescriptor: the blobs, signed with MD5, written to the "s" file. */
    private static function writeFile(string $name, array $blobs): void
    {
        $data = implode('', array_map(self::bytes(...), $blobs));
        $version = pack('V', 6002000);
        file_put_contents($name.'s', 'TDF$'.$version.$data.md5($data.pack('V', \strlen($data)).$version.'TDF$', true));
    }

    /** PrepareEncrypted. */
    private static function encrypt(string $data, string $key): string
    {
        $data = pack('V', \strlen($data) + 4).$data;
        if (\strlen($data) % 16) {
            $data .= random_bytes(16 - \strlen($data) % 16);
        }
        $message_key = substr(sha1($data, true), 0, 16);
        [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, $key, false);
        return $message_key.Crypt::igeEncrypt($data, $aes_key, $aes_iv);
    }

    /** CreateLocalKey. */
    private static function localKey(string $passcode, string $salt): string
    {
        return openssl_pbkdf2(hash('sha512', $salt.$passcode.$salt, true), $salt, 256, $passcode === '' ? 1 : 100000, 'sha512');
    }

    /** ToFilePart of the first 8 bytes of the MD5 of the name, as a little-endian integer. */
    private static function filePart(string $name): string
    {
        $value = unpack('P', md5($name, true))[1];
        $result = '';
        for ($i = 0; $i < 16; $i++) {
            $result .= '0123456789ABCDEF'[$value & 0x0F];
            $value = ($value >> 4) & 0x0FFFFFFFFFFFFFFF;
        }
        return $result;
    }
}
