<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A block quote.
 */
final class Blockquote extends Block
{
    /** Credit of the quote (e.g. its author) */
    public readonly ?RichText $credit;

    /**
     * @param list<Block> $blocks Content of the quote
     */
    public function __construct(
        /**
         * Content of the quote.
         *
         * @var list<Block>
         */
        public readonly array $blocks,
        RichText|string|null $credit = null,
    ) {
        $this->credit = $credit === null ? null : RichText::from($credit);
    }
}
