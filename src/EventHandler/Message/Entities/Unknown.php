<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity of a type not (yet) supported by MadelineProto, or an explicitly unknown entity (messageEntityUnknown).
 *
 * Telegram adds new entity types in new layers: they're wrapped in this class instead of failing to wrap the whole message.
 */
final class Unknown extends MessageEntity
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length,

        /** Raw MTProto entity */
        public readonly array $raw = ['_' => 'messageEntityUnknown'],
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'unknown', 'offset' => $this->offset, 'length' => $this->length];
    }
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toMTProto(): array
    {
        return [...$this->raw, 'offset' => $this->offset, 'length' => $this->length];
    }
}
