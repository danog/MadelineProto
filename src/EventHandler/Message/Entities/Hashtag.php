<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * #hashtag message entity.
 */
final class Hashtag extends MessageEntity
{
    public function toBotAPI(): array
    {
        return ['type' => 'hashtag', 'offset' => $this->offset, 'length' => $this->length];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityHashtag', 'offset' => $this->offset, 'length' => $this->length];
    }
}
