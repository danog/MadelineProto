<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A link to an anchor of the rich message.
 */
final class AnchorLink extends RichText
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** Name of the anchor */
        public readonly string $name,
    ) {
        $this->text = RichText::from($text);
    }
}
