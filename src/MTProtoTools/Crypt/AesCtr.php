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

use danog\MadelineProto\Magic;
use phpseclib4\Crypt\AES;

use const OPENSSL_RAW_DATA;

/**
 * Continuous AES-CTR: each call goes on from where the previous one stopped.
 *
 * With OpenSSL, or phpseclib without it.
 *
 * @internal
 */
final class AesCtr
{
    private ?AES $phpseclib = null;
    private string $cipher;
    /** The counter of the current block. */
    private string $counter;
    /** How many bytes of the keystream of the current block were already used. */
    private int $offset = 0;

    public function __construct(private readonly string $key, string $iv)
    {
        $this->cipher = 'aes-'.(\strlen($key) * 8).'-ctr';
        $this->counter = $iv;
        if (!Magic::$hasOpenssl) {
            $this->phpseclib = new AES('ctr');
            $this->phpseclib->enableContinuousBuffer();
            $this->phpseclib->setKey($key);
            $this->phpseclib->setIV($iv);
        }
    }

    /**
     * Encrypt (or decrypt) the next bytes.
     */
    public function encrypt(string $data): string
    {
        if ($this->phpseclib !== null) {
            return $this->phpseclib->encrypt($data);
        }
        if ($data === '') {
            return '';
        }
        // From the start of the current block, skipping the part of its keystream that was already used.
        $result = openssl_encrypt(str_repeat("\0", $this->offset).$data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->counter);
        \assert($result !== false);
        $total = $this->offset + \strlen($data);
        $this->counter = self::increment($this->counter, intdiv($total, 16));
        $result = substr($result, $this->offset);
        $this->offset = $total % 16;
        return $result;
    }

    /**
     * Add to a 128-bit big-endian counter, as OpenSSL increments it.
     *
     * @psalm-pure
     */
    private static function increment(string $counter, int $blocks): string
    {
        for ($i = 15; $blocks !== 0 && $i >= 0; $i--) {
            $sum = \ord($counter[$i]) + ($blocks & 0xFF);
            $counter[$i] = \chr($sum & 0xFF);
            $blocks = ($blocks >> 8) + ($sum >> 8);
        }
        return $counter;
    }
}
