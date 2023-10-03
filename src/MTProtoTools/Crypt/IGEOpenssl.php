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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use const OPENSSL_RAW_DATA;
use const OPENSSL_ZERO_PADDING;

/**
 * Openssl IGE implementation.
 *
 * @internal
 */
final class IGEOpenssl extends IGE
{
    protected function __construct(private string $key, string $iv)
    {
        $this->iv_part_1 = substr($iv, 0, 16);
        $this->iv_part_2 = substr($iv, 16);
    }
    public function encrypt(string $plaintext): string
    {
        $ciphertext = '';
        for ($i = 0, $length = \strlen($plaintext); $i < $length; $i += 16) {
            $plain = substr($plaintext, $i, 16);

            $cipher = openssl_encrypt($plain ^ $this->iv_part_1, 'aes-256-ecb', $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING) ^ $this->iv_part_2;

            $ciphertext .= $cipher;

            $this->iv_part_1 = $cipher;
            $this->iv_part_2 = $plain;
        }

        return $ciphertext;
    }
    public function decrypt(string $ciphertext): string
    {
        $plaintext = '';
        for ($i = 0, $length = \strlen($ciphertext); $i < $length; $i += 16) {
            $cipher = substr($ciphertext, $i, 16);

            $plain = openssl_decrypt($cipher ^ $this->iv_part_2, 'aes-256-ecb', $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING) ^ $this->iv_part_1;

            $plaintext .= $plain;

            $this->iv_part_1 = $cipher;
            $this->iv_part_2 = $plain;
        }

        return $plaintext;
    }
}
