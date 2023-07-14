<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a photo.
 */
final class Photo extends Media
{
    /** If true; the current media has attached mask stickers. */
    public readonly bool $hasStickers;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $protected);
        $this->hasStickers = $rawMedia['photo']['has_stickers'];
    }
}
