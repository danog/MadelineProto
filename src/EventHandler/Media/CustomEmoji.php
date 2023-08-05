<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a custom emoji sticker.
 */
final class CustomEmoji extends AbstractSticker
{
    /** Whether this custom emoji can be sent by non-Premium users */
    public readonly bool $free;
    /** Whether the color of this TGS custom emoji should be changed to the text color when used in messages, the accent color if used as emoji status, white on chat photos, or another appropriate color based on context. */
    public readonly bool $textColor;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $stickerAttribute,
        array $photoAttribute,
        bool $protected,
    ) {
        parent::__construct(
            $API,
            $rawMedia,
            $stickerAttribute,
            $photoAttribute['w'],
            $photoAttribute['h'],
            $protected
        );
        $this->free = $stickerAttribute['free'];
        $this->textColor = $stickerAttribute['text_color'];
    }
}
