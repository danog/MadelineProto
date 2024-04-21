<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a codeblock.
 */
final class Code extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'code', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityCode', 'offset' => $this->offset, 'length' => $this->length];
    }
}
