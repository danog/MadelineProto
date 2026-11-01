<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;

/**
 * An anchor: a position in the rich message that anchor links point to.
 */
final class Anchor extends Block
{
    public function __construct(
        /** Name of the anchor */
        public readonly string $name,
    ) {
    }
}
