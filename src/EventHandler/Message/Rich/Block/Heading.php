<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A section heading.
 */
final class Heading extends Block
{
    /** The text of the heading */
    public readonly RichText $text;

    public function __construct(
        /** Size of the heading, from 1 (the biggest) to 6 */
        public readonly int $size,
        RichText|string $text,
    ) {
        $this->text = RichText::from($text);
    }
}
