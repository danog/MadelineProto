<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A link to an email address.
 */
final class Email extends RichText
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** The email address */
        public readonly string $email,
    ) {
        $this->text = RichText::from($text);
    }
}
