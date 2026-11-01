<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A plain text of a rich message.
 */
final class Plain extends RichText
{
    public function __construct(
        /** The text */
        public readonly string $text,
    ) {
    }
}
