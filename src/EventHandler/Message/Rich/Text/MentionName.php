<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A mention of a user by ID.
 */
final class MentionName extends RichText
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** ID of the mentioned user */
        public readonly int $userId,
    ) {
        $this->text = RichText::from($text);
    }
}
