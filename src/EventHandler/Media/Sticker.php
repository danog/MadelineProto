<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a sticker.
 */
final class Sticker extends AbstractSticker
{
    /** Whether this is a premium sticker and a premium sticker animation must be played. */
    public readonly bool $premiumSticker;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $stickerAttribute
    ) {
        parent::__construct($API, $rawMedia, $stickerAttribute);
        $this->premiumSticker = !$rawMedia['nopremium'];
    }
}
