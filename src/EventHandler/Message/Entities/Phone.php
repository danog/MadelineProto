<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a phone number.
 */
final class Phone extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'phone_number', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityPhone', 'offset' => $this->offset, 'length' => $this->length];
    }
}
