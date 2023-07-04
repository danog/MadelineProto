<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;

/**
 * Represents a generic sticker.
 */
abstract class AbstractSticker extends Media
{
    /** Emoji representation of sticker */
    public readonly string $emoji;

    /** Associated stickerset */
    public readonly array $stickerset;
}
