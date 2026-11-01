<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button inline in the text.
 */
final class InlineButton extends RichText
{
    public function __construct(
        /** The button */
        public readonly Button $button,
    ) {
    }
}
