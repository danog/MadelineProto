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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\TL\TL;
use phpseclib4\Math\BigInteger;

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
     * The OpenSSL key, built from the modulus and the exponent.
     */
    private ?\OpenSSLAsymmetricKey $openssl = null;
    /**
     * Load RSA key.
     *
     * @param TL     $TL      TL serializer
     * @param string $rsa_key RSA key
     */
    public static function load(TL $TL, string $rsa_key): self
    {
        $instance = new self;
        $key = Magic::$hasOpenssl ? openssl_pkey_get_public($rsa_key) : false;
        $details = $key === false ? false : openssl_pkey_get_details($key);
        if ($details !== false && isset($details['rsa']['n'], $details['rsa']['e'])) {
            $instance->n = new BigInteger($details['rsa']['n'], 256);
            $instance->e = new BigInteger($details['rsa']['e'], 256);
        } else {
            $key = \phpseclib4\Crypt\RSA::load($rsa_key);
            $instance->n = Tools::getVar($key, 'modulus');
            $instance->e = Tools::getVar($key, 'exponent');
        }
        $instance->fp = substr(sha1(($TL->serializeObject(['type' => 'bytes'], $instance->n->toBytes(), 'key')).($TL->serializeObject(['type' => 'bytes'], $instance->e->toBytes(), 'key')), true), -8);
        return $instance;
    }
    /**
     * Private constructor.
     *
     * @psalm-mutation-free
     */
    private function __construct()
    {
    }
    /**
     * Sleep function.
     *
     * @psalm-pure
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
        if (Magic::$hasOpenssl) {
            $this->openssl ??= openssl_pkey_get_public($this->toPem()) ?: null;
            // Raw RSA: the data is already padded (RSA_PAD), and smaller than the modulus.
            if ($this->openssl !== null
                && openssl_public_encrypt(str_pad($data->toBytes(), 256, "\0", STR_PAD_LEFT), $encrypted, $this->openssl, OPENSSL_NO_PADDING)
            ) {
                /** @var string $encrypted */
                return $encrypted;
            }
        }
        return str_pad($data->powMod($this->e, $this->n)->toBytes(), 256, "\0", STR_PAD_LEFT);
    }
    /**
     * The key in PEM form (SubjectPublicKeyInfo).
     */
    private function toPem(): string
    {
        $integer = static function (BigInteger $n): string {
            $bytes = $n->toBytes();
            // Positive: a leading 0 if the top bit is set.
            if (\ord($bytes[0]) & 0x80) {
                $bytes = "\0".$bytes;
            }
            return "\x02".self::derLength(\strlen($bytes)).$bytes;
        };
        $rsaPublicKey = $integer($this->n).$integer($this->e);
        $rsaPublicKey = "\x30".self::derLength(\strlen($rsaPublicKey)).$rsaPublicKey;
        // AlgorithmIdentifier: rsaEncryption, NULL parameters.
        $spki = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00"
            ."\x03".self::derLength(\strlen($rsaPublicKey) + 1)."\0".$rsaPublicKey;
        $spki = "\x30".self::derLength(\strlen($spki)).$spki;
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki), 64, "\n")."-----END PUBLIC KEY-----\n";
    }
    /**
     * A DER length.
     *
     * @psalm-pure
     */
    private static function derLength(int $length): string
    {
        if ($length < 0x80) {
            return \chr($length);
        }
        $bytes = ltrim(pack('N', $length), "\0");
        return \chr(0x80 | \strlen($bytes)).$bytes;
    }
}
