<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Represents a custom emoji.
 */
final class CustomEmoji extends MessageEntity
{
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length,

        /** Document ID of the [custom emoji](https://core.telegram.org/api/custom-emoji). */
        public readonly int $documentId
    ) {
    }

    public function toBotAPI(): array
    {
        return ['type' => 'custom_emoji', 'offset' => $this->offset, 'length' => $this->length, 'custom_emoji_id' => $this->documentId];
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityCustomEmoji', 'offset' => $this->offset, 'length' => $this->length, 'document_id' => $this->documentId];
    }
}
