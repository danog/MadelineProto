<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a block quote.
 */
final class Blockquote extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'block_quote', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityBlockquote', 'offset' => $this->offset, 'length' => $this->length];
    }
}
