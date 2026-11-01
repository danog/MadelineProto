<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

use danog\MadelineProto\EventHandler\Message\Rich\Block\Paragraph;
use danog\MadelineProto\TL\Conversion\Rich\InputConverter;
use JsonSerializable;

/**
 * An item of a list block of a rich message.
 *
 * Items of ordered lists have a label type and a value; the items of a list must be either all ordered or all unordered.
 */
final class ListItem implements JsonSerializable
{
    use JsonSerializeTrait;

    /**
     * Content of the item.
     *
     * @var list<Block>
     */
    public readonly array $blocks;

    /**
     * @param list<Block>|RichText|string $blocks Content of the item: blocks, or a text for a single paragraph
     */
    public function __construct(
        array|RichText|string $blocks,
        /** Whether the item has a checkbox */
        public readonly bool $hasCheckbox = false,
        /** Whether the checkbox of the item is checked */
        public readonly bool $isChecked = false,
        /** Label type of the item of an ordered list, null for unordered lists */
        public readonly ?ListLabelType $type = null,
        /** Numeric value of the item of an ordered list */
        public readonly ?int $value = null,
        /** Custom label of the item of an ordered list, null to use the label of its value */
        public readonly ?string $label = null,
    ) {
        $this->blocks = \is_array($blocks) ? $blocks : [new Paragraph($blocks)];
    }

    /**
     * Get the label of the item, as shown by Telegram clients (e.g. •, 3., iii.).
     */
    public function getLabel(): string
    {
        if ($this->type === null) {
            return "\u{2022}";
        }
        if ($this->label !== null && $this->label !== '') {
            return str_ends_with($this->label, '.') ? $this->label : "{$this->label}.";
        }
        return InputConverter::getOrderedListLabel($this->value ?? 0, $this->type->value);
    }
}
