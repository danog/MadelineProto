<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a phone number.
 */
final class Phone extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'phone', 'offset' => $this->offset, 'length' => $this->length];
    }
}
