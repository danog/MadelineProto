<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a $cashtag.
 */
final class Cashtag extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'cashtag', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityCashtag', 'offset' => $this->offset, 'length' => $this->length];
    }
}
