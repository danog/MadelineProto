<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing an email@example.com.
 */
final class Email extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'email', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityEmail', 'offset' => $this->offset, 'length' => $this->length];
    }
}
