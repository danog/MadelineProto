<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing bold text.
 */
final class Bold extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'bold', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityBold', 'offset' => $this->offset, 'length' => $this->length];
    }
}
