<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a video.
 */
final class Video extends AbstractVideo
{
    /** If true; the current media has attached mask stickers. */
    public readonly bool $hasStickers;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $attribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $attribute, $protected);
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
