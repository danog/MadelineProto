<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A custom emoji.
 */
final class CustomEmoji extends RichText
{
    public function __construct(
        /** ID of the custom emoji */
        public readonly int $documentId,
        /** Alternative text of the custom emoji, shown by clients that can't show it */
        public readonly string $alternativeText,
    ) {
    }
}
