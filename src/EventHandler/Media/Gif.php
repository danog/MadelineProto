<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a GIF (or an MPEG4 file without sound).
 */
final class Gif extends AbstractVideo
{
    /** If true; the current media has attached mask stickers. */
    public readonly bool $hasStickers;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $attribute
    ) {
        parent::__construct($API, $rawMedia, $attribute);
        $hasStickers = false;
        foreach ($rawMedia['attributes'] as ['_' => $t]) {
            if ($t === 'documentAttributeHasStickers') {
                $hasStickers = true;
                break;
            }
        }
        $this->hasStickers = $hasStickers;
    }
}
