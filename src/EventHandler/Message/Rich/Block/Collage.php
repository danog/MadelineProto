<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A collage of media blocks.
 */
final class Collage extends Block
{
    /** Caption of the block */
    public readonly ?Caption $caption;

    /**
     * @param list<Block> $blocks Blocks of the collage
     */
    public function __construct(
        /**
         * Blocks of the collage.
         *
         * @var list<Block>
         */
        public readonly array $blocks,
        Caption|RichText|string|null $caption = null,
    ) {
        $this->caption = Caption::from($caption);
    }
}
