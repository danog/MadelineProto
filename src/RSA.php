<?php

declare(strict_types=1);

/**
 * RSA module.
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

namespace danog\MadelineProto;

use danog\MadelineProto\TL\TL;
use phpseclib3\Math\BigInteger;

use const STR_PAD_LEFT;

/**
 * RSA class.
 *
 * @internal
 */
final class RSA
{
    /**
     * Exponent.
     *
     */
    public BigInteger $e;
    /**
     * Modulus.
     *
     */
    public BigInteger $n;
    /**
     * Fingerprint.
     *
     */
    public string $fp;
    /**
     * Load RSA key.
     *
     * @param TL     $TL      TL serializer
     * @param string $rsa_key RSA key
     */
    public static function load(TL $TL, string $rsa_key): self
    {
        $key = \phpseclib3\Crypt\RSA::load($rsa_key);
        $instance = new self;
        $instance->n = Tools::getVar($key, 'modulus');
        $instance->e = Tools::getVar($key, 'exponent');
        $instance->fp = substr(sha1(($TL->serializeObject(['type' => 'bytes'], $instance->n->toBytes(), 'key')).($TL->serializeObject(['type' => 'bytes'], $instance->e->toBytes(), 'key')), true), -8);
        return $instance;
    }
    /**
     * Private constructor.
     */
    private function __construct()
    {
    }
    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return ['e', 'n', 'fp'];
    }
    /**
     * Encrypt data.
     *
     * @param BigInteger $data Data to encrypt
     */
    public function encrypt(BigInteger $data): string
    {
        return str_pad($data->powMod($this->e, $this->n)->toBytes(), 256, "\0", STR_PAD_LEFT);
    }
}
