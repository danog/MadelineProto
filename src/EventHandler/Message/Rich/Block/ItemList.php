<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\ListItem;

/**
 * A list (ordered or unordered, depending on its items).
 */
final class ItemList extends Block
{
    /**
     * @param list<ListItem> $items Items of the list
     */
    public function __construct(
        /**
         * Items of the list.
         *
         * @var list<ListItem>
         */
        public readonly array $items,
    ) {
    }
}
