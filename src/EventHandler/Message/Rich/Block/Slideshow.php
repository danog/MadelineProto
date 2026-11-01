<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A slideshow of media blocks.
 */
final class Slideshow extends Block
{
    /** Caption of the block */
    public readonly ?Caption $caption;

    /**
     * @param list<Block> $blocks Blocks of the slideshow
     */
    public function __construct(
        /**
         * Blocks of the slideshow.
         *
         * @var list<Block>
         */
        public readonly array $blocks,
        Caption|RichText|string|null $caption = null,
    ) {
        $this->caption = Caption::from($caption);
    }
}
