<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a text url: for in-text urls like https://google.com use Url.
 */
final class TextUrl extends MessageEntity
{
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length,

        /** The actual URL */
        public readonly string $url
    ) {
    }

    public function toBotAPI(): array
    {
        return ['type' => 'text_link', 'offset' => $this->offset, 'length' => $this->length, 'url' => $this->url];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityTextUrl', 'offset' => $this->offset, 'length' => $this->length, 'url' => $this->url];
    }
}
