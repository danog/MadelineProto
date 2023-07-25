<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a video sticker.
 */
final class VideoSticker extends Sticker
{
    public readonly float $duration;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $stickerAttribute,
        array $extraAttribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $stickerAttribute, $extraAttribute['w'], $extraAttribute['h'], $protected);
        $this->duration = $extraAttribute['duration'];
    }
}
