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
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use ValueError;

use const OPENSSL_RAW_DATA;
use const OPENSSL_ZERO_PADDING;

/**
 * Openssl IGE implementation.
 *
 * @internal
 */
final class IGEOpenssl extends IGE
{
    private const ZERO_BLOCK = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    /**
     * @psalm-mutation-free
     */
    protected function __construct(#[\SensitiveParameter] private string $key, string $iv)
    {
        $this->iv_part_1 = substr($iv, 0, 16);
        $this->iv_part_2 = substr($iv, 16);
    }
    /**
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function encrypt(string $plaintext): string
    {
        $length = \strlen($plaintext);
        if ($length % 16) {
            throw new ValueError('AES-IGE data length must be a multiple of 16');
        }
        if ($length === 0) {
            return '';
        }
        // c_i = E(p_i ^ c_{i-1}) ^ p_{i-1}. With z_i = E(p_i ^ c_{i-1}) and c_{i-1} = z_{i-1} ^ p_{i-2},
        // z_i = E((p_i ^ p_{i-2}) ^ z_{i-1}): that is CBC over p_i ^ p_{i-2} with IV c_0 (p_0 being the
        // second IV half and p_{-1} zero), so the whole chain takes one OpenSSL call.
        $previous = $this->iv_part_2.substr($plaintext, 0, $length - 16);
        $chained = openssl_encrypt(
            $plaintext ^ (self::ZERO_BLOCK.substr($previous, 0, $length - 16)),
            'aes-256-cbc',
            $this->key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->iv_part_1,
        );
        \assert($chained !== false);
        $ciphertext = $chained ^ $previous;

        $this->iv_part_1 = substr($ciphertext, -16);
        $this->iv_part_2 = substr($plaintext, -16);

        return $ciphertext;
    }
    /**
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function decrypt(string $ciphertext): string
    {
        $length = \strlen($ciphertext);
        if ($length % 16) {
            throw new ValueError('AES-IGE data length must be a multiple of 16');
        }
        // Decryption can't be chained in one call: each block's input depends on the previous output.
        // p_i = D(c_i ^ p_{i-1}) ^ c_{i-1}: one-block CBC decryption with IV c_{i-1} applies the second XOR.
        $previousCipher = $this->iv_part_1;
        $previousPlain = $this->iv_part_2;
        $plaintext = '';
        for ($i = 0; $i < $length; $i += 16) {
            $cipher = substr($ciphertext, $i, 16);
            $previousPlain = openssl_decrypt($cipher ^ $previousPlain, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $previousCipher);
            \assert($previousPlain !== false);
            $plaintext .= $previousPlain;
            $previousCipher = $cipher;
        }
        $this->iv_part_1 = $previousCipher;
        $this->iv_part_2 = $previousPlain;

        return $plaintext;
    }
}
