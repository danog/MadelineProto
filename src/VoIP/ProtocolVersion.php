<?php declare(strict_types=1);

namespace danog\MadelineProto\VoIP;

/** @internal */
enum ProtocolVersion
{
    case V1;
    case V2;
    case V3;

    public static function fromLibraryVersion(string $version): self
    {
        return match ($version) {
            '7.0.0' => self::V1,
            '8.0.0' => self::V2,
            '9.0.0' => self::V3,

            default => self::V2
        };
    }

    public function supportsCompression(): bool
    {
        return $this === self::V3;
    }
}
