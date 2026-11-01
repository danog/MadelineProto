<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A details block: a summary that can be expanded to show its content.
 */
final class Details extends Block
{
    /** Summary of the block, always shown */
    public readonly RichText $summary;

    /**
     * @param list<Block> $blocks Content of the block, shown when expanded
     */
    public function __construct(
        RichText|string $summary,
        /**
         * Content of the block, shown when expanded.
         *
         * @var list<Block>
         */
        public readonly array $blocks,
        /** Whether the block is expanded by default */
        public readonly bool $open = false,
    ) {
        $this->summary = RichText::from($summary);
    }
}
