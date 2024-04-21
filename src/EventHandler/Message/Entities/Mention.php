<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity mentioning the current user.
 */
final class Mention extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'mention', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityMention', 'offset' => $this->offset, 'length' => $this->length];
    }
}
