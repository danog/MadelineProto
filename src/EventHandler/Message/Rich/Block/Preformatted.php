<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A preformatted block (e.g. code).
 */
final class Preformatted extends Block
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** Programming language of the code, or an empty string */
        public readonly string $language = '',
    ) {
        $this->text = RichText::from($text);
    }
}
