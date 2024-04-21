<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing italic text.
 */
final class Italic extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'italic', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityItalic', 'offset' => $this->offset, 'length' => $this->length];
    }
}
