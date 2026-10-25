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
 * IGE over OpenSSL EVP, with one native call per buffer.
 *
 * @internal
 */
final class IGEEVP extends IGE
{
    private const CDEF = 'int madeline_ige_crypt(const char *in, char *out, size_t size, const char *key, char *iv, int encrypt);';
    private static FFI|false|null $ffi = null;
    private CData $iv;

    public static function isAvailable(): bool
    {
        if (self::$ffi !== null) {
            return self::$ffi !== false;
        }
        self::$ffi = false;
        if (!\extension_loaded('ffi')) {
            return false;
        }
        $name = match (PHP_OS_FAMILY) {
            'Darwin' => 'libmadeline-ige.dylib',
            'Windows' => 'madeline-ige.dll',
            default => 'libmadeline-ige.so',
        };
        $build = \dirname(__DIR__, 3).'/native/build/';
        foreach (array_filter([getenv('MADELINE_IGE_LIBRARY'), $build.$name, $build.'Release/'.$name, $name]) as $library) {
            try {
                $ffi = FFI::cdef(self::CDEF, $library);
                // Check that this library can initialize EVP with the current OpenSSL provider configuration.
                if ($ffi->madeline_ige_crypt('', $ffi->new('char[1]'), 0, str_repeat("\0", 32), $ffi->new('char[32]'), 1) === 1) {
                    self::$ffi = $ffi;
                    return true;
                }
            } catch (Throwable) {
                // Missing/incompatible helper or restricted FFI: try the next library, then the next backend.
            }
        }
        return false;
    }

    protected function __construct(private readonly string $key, string $iv)
    {
        if (\strlen($key) !== 32 || \strlen($iv) !== 32) {
            throw new ValueError('AES-256-IGE requires a 32-byte key and IV');
        }
        \assert(self::$ffi instanceof FFI);
        $this->iv = self::$ffi->new('char[32]');
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
        if ($length % 16 !== 0) {
            throw new ValueError('AES-IGE data length must be a multiple of 16');
        }
        if ($length === 0) {
            return '';
        }
        $ffi = self::$ffi;
        \assert($ffi instanceof FFI);
        $out = $ffi->new("char[$length]");
        if ($ffi->madeline_ige_crypt($data, $out, $length, $this->key, $this->iv, (int) $encrypt) !== 1) {
            throw new RuntimeException('Could not process AES-IGE with OpenSSL EVP');
        }
        return FFI::string($out, $length);
    }
}
