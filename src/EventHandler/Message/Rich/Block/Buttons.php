<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Alignment;
use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Button;

/**
 * A row of buttons.
 */
final class Buttons extends Block
{
    /**
     * @param list<Button> $buttons The buttons
     */
    public function __construct(
        /**
         * The buttons.
         *
         * @var list<Button>
         */
        public readonly array $buttons,
        /** Alignment of the buttons, null for the default one */
        public readonly ?Alignment $align = null,
    ) {
    }
}
