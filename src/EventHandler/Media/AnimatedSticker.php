<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents an animated sticker.
 */
final class AnimatedSticker extends Sticker
{
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
    }
}
