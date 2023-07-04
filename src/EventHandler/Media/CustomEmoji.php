<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

/**
 * Represents a custom emoji sticker.
 */
final class CustomEmoji extends AbstractSticker
{
    /** Whether this custom emoji can be sent by non-Premium users */
    public readonly bool $free;
    /** Whether the color of this TGS custom emoji should be changed to the text color when used in messages, the accent color if used as emoji status, white on chat photos, or another appropriate color based on context. */
    public readonly bool $textColor;
}
