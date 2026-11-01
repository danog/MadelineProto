<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;
use danog\MadelineProto\EventHandler\Message\Rich\TableCell;

/**
 * A table.
 */
final class Table extends Block
{
    /** Caption of the table */
    public readonly ?RichText $caption;

    /**
     * @param list<list<TableCell>> $cells Rows of cells of the table
     */
    public function __construct(
        /**
         * Rows of cells of the table.
         *
         * @var list<list<TableCell>>
         */
        public readonly array $cells,
        RichText|string|null $caption = null,
        /** Whether the table has borders */
        public readonly bool $bordered = false,
        /** Whether the rows of the table are striped */
        public readonly bool $striped = false,
        /** Whether the table is compact */
        public readonly bool $compact = false,
    ) {
        $this->caption = $caption === null ? null : RichText::from($caption);
    }
}
