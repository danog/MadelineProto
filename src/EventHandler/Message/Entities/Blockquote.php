<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a block quote.
 */
final class Blockquote extends MessageEntity
{
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        int $offset,
        /** Length of message entity within message (in UTF-16 code units) */
        int $length,
        /** Whether the quote is collapsed (expandable) */
        public readonly bool $collapsed = false,
    ) {
        parent::__construct($offset, $length);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => $this->collapsed ? 'expandable_blockquote' : 'blockquote', 'offset' => $this->offset, 'length' => $this->length];
    }
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityBlockquote', 'collapsed' => $this->collapsed, 'offset' => $this->offset, 'length' => $this->length];
    }
}
