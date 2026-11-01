<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

use JsonSerializable;

/**
 * A cell of a table block of a rich message.
 */
final class TableCell implements JsonSerializable
{
    use JsonSerializeTrait;

    /** Text of the cell, null for an empty cell */
    public readonly ?RichText $text;
    /** Horizontal alignment of the cell */
    public readonly Alignment $align;

    /**
     * @param ?Alignment $align Horizontal alignment of the cell, by default centered for header cells and left-aligned for the others
     */
    public function __construct(
        RichText|string|null $text = null,
        /** Whether this is a header cell */
        public readonly bool $header = false,
        /** Number of columns the cell spans */
        public readonly int $colspan = 1,
        /** Number of rows the cell spans */
        public readonly int $rowspan = 1,
        ?Alignment $align = null,
        /** Vertical alignment of the cell */
        public readonly VerticalAlignment $valign = VerticalAlignment::MIDDLE,
    ) {
        $this->text = $text === null ? null : RichText::from($text);
        $this->align = $align ?? ($header ? Alignment::CENTER : Alignment::LEFT);
    }
}
