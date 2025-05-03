<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing strikethrough text.
 */
final class Strike extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'strikethrough', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityStrike', 'offset' => $this->offset, 'length' => $this->length];
    }
}
