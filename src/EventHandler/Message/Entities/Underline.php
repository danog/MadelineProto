<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing underlined text.
 */
final class Underline extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'underline', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityUnderline', 'offset' => $this->offset, 'length' => $this->length];
    }
}
