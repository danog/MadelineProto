<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * An anchor: a position in the rich message that anchor links point to.
 */
final class Anchor extends RichText
{
    public function __construct(
        /** Name of the anchor */
        public readonly string $name,
    ) {
    }
}
