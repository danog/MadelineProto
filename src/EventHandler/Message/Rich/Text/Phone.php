<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A link to a phone number.
 */
final class Phone extends RichText
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** The phone number */
        public readonly string $phone,
    ) {
        $this->text = RichText::from($text);
    }
}
