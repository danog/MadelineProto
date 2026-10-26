<?php declare(strict_types=1);
/** @author Alexander Pankratov <s.pankratoff@gmail.com> */

namespace danog\MadelineProto\Native;

/** @internal Exported by the optional madeline_crypto Rust extension. */
final class Crypto
{
    public static function version(): string
    {
    }
    public static function init(int $capacity, int $byte_limit, int $window_us, int $fixed_width): int
    {
    }
    public static function available(): bool
    {
    }
    public static function wakeup(): void
    {
    }
    public static function pending(): int
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
