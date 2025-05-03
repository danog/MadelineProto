<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a spoiler.
 */
final class Spoiler extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'spoiler', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntitySpoiler', 'offset' => $this->offset, 'length' => $this->length];
    }
}
