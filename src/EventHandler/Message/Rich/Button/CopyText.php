<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button that copies a text to the clipboard.
 */
final class CopyText extends Button
{
    public function __construct(
        RichText|string $text,
        /** The text to copy */
        public readonly string $copyText,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
