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

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt\IGE;
use danog\MadelineProto\SecurityException;
use phpseclib3\Crypt\AES;
use phpseclib3\Math\BigInteger;

use const OPENSSL_RAW_DATA;
use const OPENSSL_ZERO_PADDING;

/**
 * @internal
 */
final class Crypt
{
    /**
     * AES KDF function for MTProto v2.
     *
     * @param string  $msg_key   Message key
     * @param string  $auth_key  Auth key
     * @param boolean $to_server To server/from server direction
     * @internal
     */
    public static function kdf(string $msg_key, string $auth_key, bool $to_server = true): array
    {
        $x = $to_server ? 0 : 8;
        $sha256_a = hash('sha256', $msg_key.substr($auth_key, $x, 36), true);
        $sha256_b = hash('sha256', substr($auth_key, 40 + $x, 36).$msg_key, true);
        $aes_key = substr($sha256_a, 0, 8).substr($sha256_b, 8, 16).substr($sha256_a, 24, 8);
        $aes_iv = substr($sha256_b, 0, 8).substr($sha256_a, 8, 16).substr($sha256_b, 24, 8);
        return [$aes_key, $aes_iv];
    }
    /**
     * AES KDF function for MTProto v2, VoIP.
     *
     * @internal
     */
    public static function voipKdf(string $msg_key, string $auth_key, bool $outgoing, bool $transport): array
    {
        $x = $outgoing ? 8 : 0;
        $x += $transport ? 0 : 128;
        $sha256_a = hash('sha256', $msg_key.substr($auth_key, $x, 36), true);
        $sha256_b = hash('sha256', substr($auth_key, 40 + $x, 36).$msg_key, true);
        $aes_key = substr($sha256_a, 0, 8).substr($sha256_b, 8, 16).substr($sha256_a, 24, 8);
        $aes_iv = substr($sha256_b, 0, 4).substr($sha256_a, 8, 8).substr($sha256_b, 24, 4);
        return [$aes_key, $aes_iv, $x];
    }
    /**
     * AES KDF function for MTProto v1.
     *
     * @param string  $msg_key   Message key
     * @param string  $auth_key  Auth key
     * @param boolean $to_server To server/from server direction
     * @internal
     */
    public static function oldKdf(string $msg_key, string $auth_key, bool $to_server = true): array
    {
        $x = $to_server ? 0 : 8;
        $sha1_a = sha1($msg_key.substr($auth_key, $x, 32), true);
        $sha1_b = sha1(substr($auth_key, 32 + $x, 16).$msg_key.substr($auth_key, 48 + $x, 16), true);
        $sha1_c = sha1(substr($auth_key, 64 + $x, 32).$msg_key, true);
        $sha1_d = sha1($msg_key.substr($auth_key, 96 + $x, 32), true);
        $aes_key = substr($sha1_a, 0, 8).substr($sha1_b, 8, 12).substr($sha1_c, 4, 12);
        $aes_iv = substr($sha1_a, 8, 12).substr($sha1_b, 0, 8).substr($sha1_c, 16, 4).substr($sha1_d, 0, 8);
        return [$aes_key, $aes_iv];
    }
    /**
     * CTR encrypt.
     *
     * @param string $message Message to encrypt
     * @param string $key     Key
     * @param string $iv      IV
     * @internal
     */
    public static function ctrEncrypt(string $message, string $key, string $iv): string
    {
        $cipher = new AES('ctr');
        $cipher->setKey($key);
        $cipher->setIV($iv);
        return @$cipher->encrypt($message);
    }
    /**
     * IGE encrypt.
     *
     * @param string $plaintext Message to encrypt
     * @param string $key       Key
     * @param string $iv        IV
     * @internal
     */
    public static function igeEncrypt(string $plaintext, string $key, string $iv): string
    {
        if (Magic::$hasOpenssl) {
            $iv_part_1 = substr($iv, 0, 16);
            $iv_part_2 = substr($iv, 16);
            $ciphertext = '';
            for ($i = 0, $length = \strlen($plaintext); $i < $length; $i += 16) {
                $plain = substr($plaintext, $i, 16);

                $cipher = openssl_encrypt($plain ^ $iv_part_1, 'aes-256-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING) ^ $iv_part_2;

                $ciphertext .= $cipher;

                $iv_part_1 = $cipher;
                $iv_part_2 = $plain;
            }

            return $ciphertext;
        }
        return IGE::getInstance($key, $iv)->encrypt($plaintext);
    }
    /**
     * IGE decrypt.
     *
     * @param string $ciphertext Message to decrypt
     * @param string $key        Key
     * @param string $iv         IV
     * @internal
     */
    public static function igeDecrypt(string $ciphertext, string $key, string $iv): string
    {
        if (Magic::$hasOpenssl) {
            $iv_part_1 = substr($iv, 0, 16);
            $iv_part_2 = substr($iv, 16);
            $plaintext = '';
            for ($i = 0, $length = \strlen($ciphertext); $i < $length; $i += 16) {
                $cipher = substr($ciphertext, $i, 16);

                $plain = openssl_decrypt($cipher ^ $iv_part_2, 'aes-256-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING) ^ $iv_part_1;

                $plaintext .= $plain;

                $iv_part_1 = $cipher;
                $iv_part_2 = $plain;
            }

            return $plaintext;
        }
        return IGE::getInstance($key, $iv)->decrypt($ciphertext);
    }
    /**
     * Check validity of g_a parameters.
     *
     * @internal
     */
    public static function checkG(BigInteger $g_a, BigInteger $p): bool
    {
        /*
         * ***********************************************************************
         * Check validity of g_a
         * 1 < g_a < p - 1
         */
        Logger::log('Executing g_a check (1/2)...', Logger::VERBOSE);
        if ($g_a->compare(Magic::$one) <= 0 || $g_a->compare($p->subtract(Magic::$one)) >= 0) {
            throw new SecurityException('g_a is invalid (1 < g_a < p - 1 is false).');
        }
        Logger::log('Executing g_a check (2/2)...', Logger::VERBOSE);
        if ($g_a->compare(Magic::$twoe1984) < 0 || $g_a->compare($p->subtract(Magic::$twoe1984)) >= 0) {
            throw new SecurityException('g_a is invalid (2^1984 < g_a < p - 2^1984 is false).');
        }
        return true;
    }
    /**
     * Check validity of p and g parameters.
     *
     * @internal
     */
    public static function checkPG(BigInteger $p, BigInteger $g): bool
    {
        /*
         * ***********************************************************************
         * Check validity of dh_prime
         * Is it a prime?
         */
        Logger::log('Executing p/g checks (1/2)...', Logger::VERBOSE);
        if (!$p->isPrime()) {
            throw new SecurityException("p isn't a safe 2048-bit prime (p isn't a prime).");
        }
        /*
         * ***********************************************************************
         * Check validity of p
         * Is (p - 1) / 2 a prime?
         *
         * Almost always fails quite possibly due to phpseclib
         */
        /*
                $this->logger->logger('Executing p/g checks (2/3)...', \danog\MadelineProto\Logger::VERBOSE);
                if (!$p->subtract(\danog\MadelineProto\Magic::$one)->divide(\danog\MadelineProto\Magic::$two)[0]->isPrime()) {
                throw new \danog\MadelineProto\SecurityException("p isn't a safe 2048-bit prime ((p - 1) / 2 isn't a prime).");
                }
        */
        /*
         * ***********************************************************************
         * Check validity of p
         * 2^2047 < p < 2^2048
         */
        Logger::log('Executing p/g checks (2/2)...', Logger::VERBOSE);
        if ($p->compare(Magic::$twoe2047) <= 0 || $p->compare(Magic::$twoe2048) >= 0) {
            throw new SecurityException("g isn't a safe 2048-bit prime (2^2047 < p < 2^2048 is false).");
        }
        /*
         * ***********************************************************************
         * Check validity of g
         * 1 < g < p - 1
         */
        Logger::log('Executing g check...', Logger::VERBOSE);
        if ($g->compare(Magic::$one) <= 0 || $g->compare($p->subtract(Magic::$one)) >= 0) {
            throw new SecurityException('g is invalid (1 < g < p - 1 is false).');
        }
        return true;
    }
}
