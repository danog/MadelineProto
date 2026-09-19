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
 * ids are HMAC-SHA256, and bulk encryption is AES-256-CBC. These come from the `sodium` and `openssl`
 * PHP extensions; {@see self::available()} reports whether the runtime has them.
 *
 * @internal
 */
final class Crypto
{
    /** Minimum padding prepended by encrypt_data (MessageEncryption.cpp MIN_PADDING). */
    private const MIN_PADDING = 16;

    /** Whether the runtime has the extensions required for E2E conference cryptography. */
    public static function available(): bool
    {
        return \extension_loaded('sodium') && \extension_loaded('openssl');
    }

    private static function assertAvailable(): void
    {
        if (!self::available()) {
            throw new \RuntimeException('End-to-end encrypted conference calls require the PHP sodium and openssl extensions.');
        }
    }

    /* ------------------------------------------------------------------ *
     *  KDF and MACs.
     * ------------------------------------------------------------------ */

    /**
     * The tde2e key-derivation function: `HMAC-SHA512(key = secret, msg = info)`, 64 bytes
     * (MessageEncryption.cpp `kdf_expand`). The purpose string is the *message*, the secret the key.
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
        $ciphertext = openssl_encrypt($padded, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $aesIv);
        \assert($ciphertext !== false);
        return [$msgId.$ciphertext, $largeMsgId];
    }

    /**
     * Decrypt and authenticate the output of {@see self::encryptData()}, throwing on any mismatch.
     */
    public static function decryptData(string $encrypted, string $secret, string $extra = ''): string
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
        $padded = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $aesIv);
        if ($padded === false) {
            throw new \RuntimeException('Could not decrypt data');
        }
        $expected = hash_hmac('sha256', $padded.$extra.pack('V', \strlen($extra)), $hmacKey, true);
        if (!hash_equals(substr($expected, 0, 16), $msgId)) {
            throw new \RuntimeException('Message id mismatch decrypting data');
        }
        $padLen = \ord($padded[0]);
        if ($padLen < self::MIN_PADDING || $padLen > \strlen($padded)) {
            throw new \RuntimeException('Invalid padding decrypting data');
        }
        return substr($padded, $padLen);
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
        $out = openssl_encrypt($header, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $aesIv);
        \assert($out !== false);
        return $out;
    }

    /**
     * Reverse {@see self::encryptHeader()}, returning the 32-byte header.
     */
    public static function decryptHeader(string $encryptedHeader, string $encryptedMessage, string $secret): string
    {
        self::assertAvailable();
        $encKey = substr(self::kdfExpand($secret, 'tde2e_encrypt_header'), 0, 32);
        [$aesKey, $aesIv] = self::aesFromHash($encKey, substr($encryptedMessage, 0, 16));
        $out = openssl_decrypt($encryptedHeader, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $aesIv);
        if ($out === false) {
            throw new \RuntimeException('Could not decrypt header');
        }
        return $out;
    }

    /** AES-256-CBC (key, iv) from `HMAC-SHA512(hashKey, msgId)[0:48]`. */
    private static function aesFromHash(string $hashKey, string $msgId): array
    {
        $kv = hash_hmac('sha512', $msgId, $hashKey, true);
        return [substr($kv, 0, 32), substr($kv, 32, 16)];
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
        self::assertAvailable();
        return sodium_crypto_sign_publickey(sodium_crypto_sign_seed_keypair($seed));
    }

    /** Ed25519-sign a message with a 32-byte seed, returning the 64-byte signature. */
    public static function sign(string $seed, string $message): string
    {
        self::assertAvailable();
        $secretKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($seed));
        return sodium_crypto_sign_detached($message, $secretKey);
    }

    /** Verify an Ed25519 signature against a 32-byte public key. */
    public static function verify(string $signature, string $message, string $publicKey): bool
    {
        self::assertAvailable();
        if (\strlen($signature) !== 64 || \strlen($publicKey) !== 32) {
            return false;
        }
        return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
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
        self::assertAvailable();
        $secretKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($seed));
        $x25519Secret = sodium_crypto_sign_ed25519_sk_to_curve25519($secretKey);
        $x25519Public = sodium_crypto_sign_ed25519_pk_to_curve25519($peerPublicKey);
        $raw = sodium_crypto_scalarmult($x25519Secret, $x25519Public);
        // Note the key/msg order here is the opposite of kdfExpand: the purpose string is the key.
        return substr(hash_hmac('sha512', $raw, 'tde2e_shared_secret', true), 0, 32);
    }

    /* ------------------------------------------------------------------ *
     *  Hashing helpers.
     * ------------------------------------------------------------------ */

    /** SHA-256, raw bytes. */
    public static function sha256(string $data): string
    {
        return hash('sha256', $data, true);
    }

    /**
     * Derive the actual group encryption key from the raw shared key for protocol version >= 1:
     * `HMAC-SHA512(key = raw, msg = blockHash)[0:32]` (Call.cpp update_group_shared_key).
     */
    public static function deriveGroupKey(string $rawSharedKey, string $blockHash): string
    {
        return substr(hash_hmac('sha512', $blockHash, $rawSharedKey, true), 0, 32);
    }
}
