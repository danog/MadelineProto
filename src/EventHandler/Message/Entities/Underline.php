<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing underlined text.
 */
final class Underline extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'underline', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityUnderline', 'offset' => $this->offset, 'length' => $this->length];
    }
}
