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

namespace danog\MadelineProto\MTProtoTools\Crypt;

use Amp\ForbidSerialization;
use danog\MadelineProto\Native\AesIge;
use ValueError;

/**
 * Continuous IGE on the PHP thread with the extension's AES kernels: the fastest handler that never suspends.
 *
 * @internal
 */
final class IGENativeSync extends IGE
{
    use ForbidSerialization;

    protected function __construct(#[\SensitiveParameter] private readonly string $key, string $iv)
    {
        if (\strlen($key) !== 32 || \strlen($iv) !== 32) {
            throw new ValueError('AES-256-IGE requires a 32-byte key and IV');
        }
        $this->iv_part_1 = substr($iv, 0, 16);
        $this->iv_part_2 = substr($iv, 16);
    }

    #[\Override]
    public function encrypt(string $plaintext): string
    {
        $ciphertext = AesIge::encrypt($this->key, $this->iv_part_1.$this->iv_part_2, $plaintext);
        if ($ciphertext !== '') {
            $this->iv_part_1 = substr($ciphertext, -16);
            $this->iv_part_2 = substr($plaintext, -16);
        }
        return $ciphertext;
    }

    #[\Override]
    public function decrypt(string $ciphertext): string
    {
        $plaintext = AesIge::decrypt($this->key, $this->iv_part_1.$this->iv_part_2, $ciphertext);
        if ($plaintext !== '') {
            $this->iv_part_1 = substr($ciphertext, -16);
            $this->iv_part_2 = substr($plaintext, -16);
        }
        return $plaintext;
    }
}
