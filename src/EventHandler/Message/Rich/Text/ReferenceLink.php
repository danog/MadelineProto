<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A link to a reference of the rich message.
 */
final class ReferenceLink extends RichText
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** Name of the reference */
        public readonly string $name,
    ) {
        $this->text = RichText::from($text);
    }
}
