<?php declare(strict_types=1);
/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @author Daniil Gentili <daniil@daniil.it>
 */

namespace danog\MadelineProto\Native;

/** @internal Exported by the optional madeline_crypto Rust extension. */
final class Crypto
{
    /** The PHP API version: 0.5.0. */
    public static function version(): string
    {
    }
    /** @return resource php-tokio's notification stream, to watch for readability. */
    public static function init(int $capacity, int $byte_limit)
    {
    }
    public static function available(): bool
    {
    }
    public static function wakeup(): void
    {
    }
    public static function stop(): void
    {
    }
    public static function shutdown(): void
    {
    }
    /** @return array{string, string} */
    public static function ige(string $data, string $key, string $iv, bool $encrypt): array
    {
    }
    /** @return array{string, string} */
    public static function decode(string $data, string $auth, string $message_key): array
    {
    }
}

// The per-packet primitives below are synchronous and need no init(). Their per-packet methods take
// strings only (no scalar coercion, as with strict_types). Invalid sizes or algorithms throw \ValueError.

/** AES-GCM (16, 24 or 32-byte key), key schedule and GHASH key computed once. Not serializable or clonable. */
final class AesGcm
{
    public function __construct(#[\SensitiveParameter] string $key)
    {
    }
    /** @param string $nonce 12 bytes. @return string ciphertext . tag (16) */
    public function encrypt(string $nonce, string $aad, string $plaintext): string
    {
    }
    /** @param string $nonce 12 bytes. @param string $tag 16 bytes. @return string|false false on authentication failure */
    public function decrypt(string $nonce, string $aad, string $ciphertext, string $tag): string|false
    {
    }
}
/** Stateful AES-CTR (16, 24 or 32-byte key, 16-byte initial counter block, 128-bit big-endian increment). */
final class AesCtr
{
    public function __construct(#[\SensitiveParameter] string $key, string $iv)
    {
    }
    /** Continues the keystream across calls, byte-granular. */
    public function process(string $data): string
    {
    }
    public static function once(#[\SensitiveParameter] string $key, string $iv, string $data): string
    {
    }
}
/** AES-CTR with the key scheduled once and a fresh 16-byte IV per call (SRTP AES-CM, CDN chunks). */
final class AesCtrKey
{
    public function __construct(#[\SensitiveParameter] string $key)
    {
    }
    public function apply(string $iv, string $data): string
    {
    }
}
/** AES-CBC without padding: the data length must be a multiple of 16. */
final class AesCbc
{
    public static function encrypt(#[\SensitiveParameter] string $key, string $iv, string $data): string
    {
    }
    public static function decrypt(#[\SensitiveParameter] string $key, string $iv, string $data): string
    {
    }
}
/** Synchronous AES-256-IGE (32-byte key, 32-byte MTProto IV), for small payloads. */
final class AesIge
{
    public static function encrypt(#[\SensitiveParameter] string $key, string $iv, string $data): string
    {
    }
    public static function decrypt(#[\SensitiveParameter] string $key, string $iv, string $data): string
    {
    }
}
/** HMAC (md5, sha1, sha256, sha384, sha512): the keyed inner/outer states are computed once. */
final class Hmac
{
    public function __construct(string $algo, #[\SensitiveParameter] string $key)
    {
    }
    public function mac(string $data): string
    {
    }
    public static function compute(string $algo, #[\SensitiveParameter] string $key, string $data): string
    {
    }
}
/** Hashes (md5, sha1, sha256, sha384, sha512), raw output. */
final class Hash
{
    public function __construct(string $algo)
    {
    }
    public function update(string $data): void
    {
    }
    /** The object can't be updated after final() (\Error). */
    public function final(): string
    {
    }
    public static function digest(string $algo, string $data): string
    {
    }
}
