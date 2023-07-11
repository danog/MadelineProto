<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\MTProto;

/**
 * Represents a photo uploaded as a document.
 */
final class DocumentPhoto extends Media
{
    /** If true; the current media has attached mask stickers. */
    public readonly bool $hasStickers;

    public readonly int $width;
    public readonly int $height;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $attribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $protected);
        $this->width = $attribute['w'];
        $this->height = $attribute['h'];
        $hasStickers = false;
        foreach ($rawMedia['document']['attributes'] as ['_' => $t]) {
            if ($t === 'documentAttributeHasStickers') {
                $hasStickers = true;
                break;
            }
        }
        $this->hasStickers = $hasStickers;
    }
}
