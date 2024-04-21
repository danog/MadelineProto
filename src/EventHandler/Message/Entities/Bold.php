<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing bold text.
 */
final class Bold extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'bold', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityBold', 'offset' => $this->offset, 'length' => $this->length];
    }
}
