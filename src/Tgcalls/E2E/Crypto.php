<?php declare(strict_types=1);

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

namespace danog\MadelineProto\Tgcalls\E2E;

/**
 * Cryptographic primitives for Telegram end-to-end encrypted conference calls, ported byte-for-byte
 * from TDLib's `tde2e` (see td/tde2e/td/e2e/{MessageEncryption,Keys,Call,Blockchain}.cpp and
 * tdutils/td/utils/Ed25519.cpp). Every routine is a pure function so it can be unit-tested against
 * the reference in isolation.
 *
 * Signatures are Ed25519, key agreement is X25519 over the Ed25519 keys converted to Montgomery form
 * (exactly what libsodium's crypto_sign_ed25519_*_to_curve25519 do), the KDF is HMAC-SHA512, message
 * ids are HMAC-SHA256, and bulk encryption is AES-256-CBC. The fast path uses the `sodium` and
 * `openssl` extensions; when they are absent it falls back to phpseclib (Ed25519, AES) and a pure-PHP
 * X25519, so no PHP extension beyond the always-present `hash` is required — both backends are
 * cross-validated to produce byte-identical results.
 *
 * @internal
 */
final class Crypto
{
    /** Minimum padding prepended by encrypt_data (MessageEncryption.cpp MIN_PADDING). */
    private const MIN_PADDING = 16;

    /**
     * Always available: the fast path uses the `sodium` and `openssl` extensions, and a pure-PHP
     * fallback (phpseclib Ed25519, a phpseclib X25519 over Ed25519 keys, and phpseclib AES) covers
     * runtimes without them. phpseclib is a hard dependency of MadelineProto.
     *
     * @psalm-pure
     */
    public static function available(): bool
    {
        return true;
    }

    /**
     * @psalm-mutation-free
     */
    private static function assertAvailable(): void
    {
    }

    /* ------------------------------------------------------------------ *
     *  KDF and MACs.
     * ------------------------------------------------------------------ */

    /**
     * The tde2e key-derivation function: `HMAC-SHA512(key = secret, msg = info)`, 64 bytes
     * (MessageEncryption.cpp `kdf_expand`). The purpose string is the *message*, the secret the key.
     *
     * @psalm-pure
     */
    public static function kdfExpand(string $secret, string $info): string
    {
        return hash_hmac('sha512', $info, $secret, true);
    }

    /* ------------------------------------------------------------------ *
     *  encrypt_data / decrypt_data (MessageEncryption.cpp).
     * ------------------------------------------------------------------ */

    /**
     * Encrypt a payload with a secret, binding it to `extra` authenticated data.
     *
     * @return array{0: string, 1: string} `[output, largeMsgId]` where output is `msgId(16) || AES-CBC`
     *         and largeMsgId is the full 32-byte HMAC-SHA256 (used as the media-packet signing input).
     */
    public static function encryptData(string $payload, string $secret, string $extra = ''): array
    {
        self::assertAvailable();
        // Padding is a prefix whose first byte is its own length, sized so prefix+payload is a
        // multiple of 16 with at least MIN_PADDING bytes of padding.
        $len = \strlen($payload);
        $padLen = ((self::MIN_PADDING + 15 + $len) & ~15) - $len;
        $prefix = random_bytes($padLen);
        $prefix[0] = \chr($padLen);
        $padded = $prefix.$payload;

        $large = self::kdfExpand($secret, 'tde2e_encrypt_data');
        $encKey = substr($large, 0, 32);
        $hmacKey = substr($large, 32, 32);

        $tail = $padded.$extra.pack('V', \strlen($extra));
        $largeMsgId = hash_hmac('sha256', $tail, $hmacKey, true);
        $msgId = substr($largeMsgId, 0, 16);

        [$aesKey, $aesIv] = self::aesFromHash($encKey, $msgId);
        $ciphertext = self::aesEncrypt($aesKey, $aesIv, $padded);
        return [$msgId.$ciphertext, $largeMsgId];
    }

    /**
     * Decrypt and authenticate the output of {@see self::encryptData()}, throwing on any mismatch.
     */
    public static function decryptData(string $encrypted, string $secret, string $extra = ''): string
    {
        return self::decryptDataWithMsgId($encrypted, $secret, $extra)[0];
    }

    /**
     * Like {@see self::decryptData()}, but also returns the full 32-byte large message id, which the
     * media-packet layer needs to verify the sender's signature.
     *
     * @return array{0: string, 1: string} `[plaintext, largeMsgId]`
     */
    public static function decryptDataWithMsgId(string $encrypted, string $secret, string $extra = ''): array
    {
        self::assertAvailable();
        if (\strlen($encrypted) < 16 || (\strlen($encrypted) - 16) % 16 !== 0) {
            throw new \RuntimeException('Malformed encrypted data');
        }
        $msgId = substr($encrypted, 0, 16);
        $ciphertext = substr($encrypted, 16);

        $large = self::kdfExpand($secret, 'tde2e_encrypt_data');
        $encKey = substr($large, 0, 32);
        $hmacKey = substr($large, 32, 32);

        [$aesKey, $aesIv] = self::aesFromHash($encKey, $msgId);
        $padded = self::aesDecrypt($aesKey, $aesIv, $ciphertext);
        if ($padded === false) {
            throw new \RuntimeException('Could not decrypt data');
        }
        $largeMsgId = hash_hmac('sha256', $padded.$extra.pack('V', \strlen($extra)), $hmacKey, true);
        if (!hash_equals(substr($largeMsgId, 0, 16), $msgId)) {
            throw new \RuntimeException('Message id mismatch decrypting data');
        }
        $padLen = \ord($padded[0]);
        if ($padLen < self::MIN_PADDING || $padLen > \strlen($padded)) {
            throw new \RuntimeException('Invalid padding decrypting data');
        }
        return [substr($padded, $padLen), $largeMsgId];
    }

    /* ------------------------------------------------------------------ *
     *  encrypt_header / decrypt_header (MessageEncryption.cpp).
     * ------------------------------------------------------------------ */

    /**
     * Wrap a 32-byte header keyed to a secret and bound to an already-encrypted message (via its
     * leading 16-byte msg id). Returns exactly 32 bytes.
     */
    public static function encryptHeader(string $header, string $encryptedMessage, string $secret): string
    {
        self::assertAvailable();
        if (\strlen($header) !== 32 || \strlen($encryptedMessage) < 16) {
            throw new \RuntimeException('Invalid header or message for encrypt_header');
        }
        $encKey = substr(self::kdfExpand($secret, 'tde2e_encrypt_header'), 0, 32);
        [$aesKey, $aesIv] = self::aesFromHash($encKey, substr($encryptedMessage, 0, 16));
        return self::aesEncrypt($aesKey, $aesIv, $header);
    }

    /**
     * Reverse {@see self::encryptHeader()}, returning the 32-byte header.
     */
    public static function decryptHeader(string $encryptedHeader, string $encryptedMessage, string $secret): string
    {
        self::assertAvailable();
        $encKey = substr(self::kdfExpand($secret, 'tde2e_encrypt_header'), 0, 32);
        [$aesKey, $aesIv] = self::aesFromHash($encKey, substr($encryptedMessage, 0, 16));
        $out = self::aesDecrypt($aesKey, $aesIv, $encryptedHeader);
        if ($out === false) {
            throw new \RuntimeException('Could not decrypt header');
        }
        return $out;
    }

    /**
     * AES-256-CBC (key, iv) from `HMAC-SHA512(hashKey, msgId)[0:48]`.
     *
     * @psalm-pure
     */
    private static function aesFromHash(string $hashKey, string $msgId): array
    {
        $kv = hash_hmac('sha512', $msgId, $hashKey, true);
        return [substr($kv, 0, 32), substr($kv, 32, 16)];
    }

    /** AES-256-CBC encrypt without padding (input is already block-aligned): openssl, else phpseclib. */
    private static function aesEncrypt(string $key, string $iv, string $data): string
    {
        if (\extension_loaded('openssl')) {
            $out = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
            \assert($out !== false);
            return $out;
        }
        $aes = new \phpseclib4\Crypt\AES('cbc');
        $aes->setKey($key);
        $aes->setIV($iv);
        $aes->disablePadding();
        return $aes->encrypt($data);
    }

    /** AES-256-CBC decrypt without padding: openssl, else phpseclib. Returns false on failure. */
    private static function aesDecrypt(string $key, string $iv, string $data): string|false
    {
        if (\extension_loaded('openssl')) {
            return openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        }
        if ($data === '' || \strlen($data) % 16 !== 0) {
            return false;
        }
        $aes = new \phpseclib4\Crypt\AES('cbc');
        $aes->setKey($key);
        $aes->setIV($iv);
        $aes->disablePadding();
        return $aes->decrypt($data);
    }

    /* ------------------------------------------------------------------ *
     *  Ed25519 signatures.
     * ------------------------------------------------------------------ */

    /**
     * Generate a keypair.
     *
     * @return array{0: string, 1: string} `[seed(32), publicKey(32)]`; the seed is the private key.
     */
    public static function generateKeyPair(): array
    {
        $seed = random_bytes(32);
        return [$seed, self::publicKey($seed)];
    }

    /** The 32-byte Ed25519 public key of a 32-byte seed. */
    public static function publicKey(string $seed): string
    {
        if (\extension_loaded('sodium')) {
            return sodium_crypto_sign_publickey(sodium_crypto_sign_seed_keypair($seed));
        }
        $curve = new \phpseclib4\Crypt\EC\Curves\Ed25519();
        $point = $curve->multiplyPoint($curve->getBasePoint(), $curve->extractSecret($seed)['dA']);
        return \phpseclib4\Crypt\EC\Formats\Keys\libsodium::savePublicKey($curve, $point);
    }

    /** Ed25519-sign a message with a 32-byte seed, returning the 64-byte signature. */
    public static function sign(string $seed, string $message): string
    {
        if (\extension_loaded('sodium')) {
            return sodium_crypto_sign_detached($message, sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($seed)));
        }
        // phpseclib loads an Ed25519 private key from the 32-byte seed followed by its public key.
        return \phpseclib4\Crypt\EC::loadFormat('libsodium', $seed.self::publicKey($seed))->sign($message);
    }

    /** Verify an Ed25519 signature against a 32-byte public key. */
    public static function verify(string $signature, string $message, string $publicKey): bool
    {
        if (\strlen($signature) !== 64 || \strlen($publicKey) !== 32) {
            return false;
        }
        if (\extension_loaded('sodium')) {
            return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
        }
        try {
            return \phpseclib4\Crypt\EC::loadFormat('libsodium', $publicKey)->verify($message, $signature);
        } catch (\Throwable) {
            return false;
        }
    }

    /* ------------------------------------------------------------------ *
     *  X25519 key agreement (Keys.cpp compute_shared_secret).
     * ------------------------------------------------------------------ */

    /**
     * The tde2e shared secret between our seed and a peer's Ed25519 public key: X25519 over the keys
     * converted to Montgomery form, then `HMAC-SHA512(key = "tde2e_shared_secret", msg = x25519)[0:32]`.
     */
    public static function computeSharedSecret(string $seed, string $peerPublicKey): string
    {
        if (\extension_loaded('sodium')) {
            $secretKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($seed));
            $raw = sodium_crypto_scalarmult(
                sodium_crypto_sign_ed25519_sk_to_curve25519($secretKey),
                sodium_crypto_sign_ed25519_pk_to_curve25519($peerPublicKey)
            );
        } else {
            $raw = self::x25519(self::edSeedToX25519($seed), self::edPublicToX25519($peerPublicKey));
        }
        // Note the key/msg order here is the opposite of kdfExpand: the purpose string is the key.
        return substr(hash_hmac('sha512', $raw, 'tde2e_shared_secret', true), 0, 32);
    }

    /* ------------------------------------------------------------------ *
     *  Pure-PHP X25519 fallback (RFC 7748 + Ed25519->Montgomery conversion), used without sodium.
     * ------------------------------------------------------------------ */

    /** Curve25519 field prime, 2^255 - 19. */
    private static function fieldPrime(): \phpseclib4\Math\BigInteger
    {
        return (new \phpseclib4\Math\BigInteger(2))->pow(new \phpseclib4\Math\BigInteger(255))->subtract(new \phpseclib4\Math\BigInteger(19));
    }

    /**
     * Ed25519 seed -> clamped X25519 scalar: SHA-512(seed)[0:32] with the RFC 7748 clamp.
     *
     * @psalm-pure
     */
    private static function edSeedToX25519(string $seed): string
    {
        $s = substr(hash('sha512', $seed, true), 0, 32);
        $s[0] = \chr(\ord($s[0]) & 248);
        $s[31] = \chr((\ord($s[31]) & 127) | 64);
        return $s;
    }

    /** Ed25519 public key -> Montgomery u: u = (1 + y) / (1 - y) mod p, with the sign bit cleared. */
    private static function edPublicToX25519(string $publicKey): string
    {
        $p = self::fieldPrime();
        $one = new \phpseclib4\Math\BigInteger(1);
        $y = (new \phpseclib4\Math\BigInteger(strrev($publicKey), 256))
            ->bitwise_and((new \phpseclib4\Math\BigInteger(2))->pow(new \phpseclib4\Math\BigInteger(255))->subtract($one));
        $u = $one->add($y)->multiply($one->subtract($y)->modInverse($p))->powMod($one, $p);
        return self::toLe32($u);
    }

    /** X25519 scalar multiplication (RFC 7748 Montgomery ladder). */
    private static function x25519(string $scalar, string $u): string
    {
        $p = self::fieldPrime();
        $one = new \phpseclib4\Math\BigInteger(1);
        $a24 = new \phpseclib4\Math\BigInteger(121665);
        $k = new \phpseclib4\Math\BigInteger(strrev($scalar), 256);
        $x1 = new \phpseclib4\Math\BigInteger(strrev($u), 256);
        $x2 = $one;
        $z2 = new \phpseclib4\Math\BigInteger(0);
        $x3 = $x1;
        $z3 = $one;
        $swap = 0;
        for ($t = 254; $t >= 0; $t--) {
            $kt = $k->bitwise_rightShift($t)->bitwise_and($one)->equals($one) ? 1 : 0;
            $swap ^= $kt;
            if ($swap) {
                [$x2, $x3] = [$x3, $x2];
                [$z2, $z3] = [$z3, $z2];
            }
            $swap = $kt;
            $A = $x2->add($z2)->powMod($one, $p);
            $AA = $A->multiply($A)->powMod($one, $p);
            $B = $x2->subtract($z2)->powMod($one, $p);
            $BB = $B->multiply($B)->powMod($one, $p);
            $E = $AA->subtract($BB)->powMod($one, $p);
            $C = $x3->add($z3)->powMod($one, $p);
            $D = $x3->subtract($z3)->powMod($one, $p);
            $DA = $D->multiply($A)->powMod($one, $p);
            $CB = $C->multiply($B)->powMod($one, $p);
            $x3 = $DA->add($CB);
            $x3 = $x3->multiply($x3)->powMod($one, $p);
            $z3 = $DA->subtract($CB);
            $z3 = $z3->multiply($z3)->powMod($one, $p)->multiply($x1)->powMod($one, $p);
            $x2 = $AA->multiply($BB)->powMod($one, $p);
            $z2 = $E->multiply($AA->add($a24->multiply($E)->powMod($one, $p)))->powMod($one, $p);
        }
        if ($swap) {
            [$x2, $z2] = [$x3, $z3];
        }
        return self::toLe32($x2->multiply($z2->modInverse($p))->powMod($one, $p));
    }

    /** Encode a field element as a 32-byte little-endian string. */
    private static function toLe32(\phpseclib4\Math\BigInteger $n): string
    {
        return strrev(str_pad($n->toBytes(), 32, "\0", STR_PAD_LEFT));
    }

    /* ------------------------------------------------------------------ *
     *  Hashing helpers.
     * ------------------------------------------------------------------ */

    /**
     * SHA-256, raw bytes.
     *
     * @psalm-pure
     */
    public static function sha256(string $data): string
    {
        return hash('sha256', $data, true);
    }

    /**
     * Derive the actual group encryption key from the raw shared key for protocol version >= 1:
     * `HMAC-SHA512(key = raw, msg = blockHash)[0:32]` (Call.cpp update_group_shared_key).
     *
     * @psalm-pure
     */
    public static function deriveGroupKey(string $rawSharedKey, string $blockHash): string
    {
        return substr(hash_hmac('sha512', $blockHash, $rawSharedKey, true), 0, 32);
    }
}
