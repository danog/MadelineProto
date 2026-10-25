<?php

declare(strict_types=1);

/**
 * Crypt module.
 *
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

namespace danog\MadelineProto\MTProtoTools\Crypt;

use FFI;
use FFI\CData;
use RuntimeException;
use Throwable;
use ValueError;

/**
 * Native OpenSSL IGE, with one FFI call per buffer.
 *
 * @internal
 */
final class IGEFFI extends IGE
{
    // Aligned storage for AES_KEY's 60 round-key words and round count,
    // accommodating both 32-bit and unsigned long OpenSSL word layouts.
    private const CDEF = '
        typedef union { unsigned long long alignment; unsigned char storage[512]; } IgeKey;
        int AES_set_encrypt_key(const char *key, int bits, void *schedule);
        int AES_set_decrypt_key(const char *key, int bits, void *schedule);
        void AES_ige_encrypt(const char *in, char *out, size_t length,
                             const void *schedule, char *iv, int encrypt);
    ';

    private static FFI|false|null $ffi = null;
    private CData $iv;
    private CData $schedule;
    private ?bool $encrypting = null;

    public static function isAvailable(): bool
    {
        if (self::$ffi !== null) {
            return self::$ffi !== false;
        }
        self::$ffi = false;
        if (!\extension_loaded('ffi')) {
            return false;
        }
        $libraries = match (PHP_OS_FAMILY) {
            'Linux', 'BSD' => ['libcrypto.so.3', 'libcrypto.so.1.1', 'libcrypto.so'],
            'Darwin' => [
                'libcrypto.3.dylib',
                'libcrypto.1.1.dylib',
                'libcrypto.dylib',
                '/opt/homebrew/opt/openssl@3/lib/libcrypto.3.dylib',
                '/usr/local/opt/openssl@3/lib/libcrypto.3.dylib',
            ],
            'Windows' => ['libcrypto-3-x64.dll', 'libcrypto-3.dll', 'libcrypto-1_1-x64.dll', 'libcrypto-1_1.dll'],
            default => [],
        };
        // Prefer symbols already loaded by PHP. FFI::cdef checks all required symbols.
        foreach ([null, ...$libraries] as $library) {
            try {
                self::$ffi = FFI::cdef(self::CDEF, $library);
                return true;
            } catch (Throwable) {
                // FFI may be restricted, or this library may not export IGE.
            }
        }
        return false;
    }

    protected function __construct(private string $key, string $iv)
    {
        if (\strlen($key) !== 32 || \strlen($iv) !== 32) {
            throw new ValueError('AES-256-IGE requires a 32-byte key and IV');
        }
        \assert(self::$ffi instanceof FFI);
        $this->iv = self::$ffi->new('char[32]');
        $this->schedule = self::$ffi->new('IgeKey');
        FFI::memcpy($this->iv, $iv, 32);
    }

    #[\Override]
    public function getState(): string
    {
        return FFI::string($this->iv, 32);
    }

    #[\Override]
    public function encrypt(string $plaintext): string
    {
        return $this->process($plaintext, true);
    }

    #[\Override]
    public function decrypt(string $ciphertext): string
    {
        return $this->process($ciphertext, false);
    }

    private function process(string $data, bool $encrypt): string
    {
        $length = \strlen($data);
        // OpenSSL's low-level API can abort the process for unaligned input.
        if ($length % 16 !== 0) {
            throw new ValueError('AES-IGE data length must be a multiple of 16');
        }
        if ($length === 0) {
            return '';
        }
        $ffi = self::$ffi;
        \assert($ffi instanceof FFI);
        if ($this->encrypting !== $encrypt) {
            $init = $encrypt ? 'AES_set_encrypt_key' : 'AES_set_decrypt_key';
            if ($ffi->$init($this->key, 256, FFI::addr($this->schedule)) !== 0) {
                throw new RuntimeException('Could not initialize AES-IGE key');
            }
            $this->encrypting = $encrypt;
        }
        $out = $ffi->new("char[$length]");
        $ffi->AES_ige_encrypt($data, $out, $length, FFI::addr($this->schedule), $this->iv, (int) $encrypt);
        return FFI::string($out, $length);
    }
}
