<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing an in-text url: https://google.com; for text urls, use TextUrl.
 */
final class Url extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'url', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityUrl', 'offset' => $this->offset, 'length' => $this->length];
    }
}
