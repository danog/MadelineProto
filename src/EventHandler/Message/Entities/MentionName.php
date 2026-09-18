<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a [user mention](https://core.telegram.org/api/mentions).
 */
final class MentionName extends MessageEntity
{

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length,

        /** Identifier of the user that was mentioned */
        public readonly int $userId
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'text_mention', 'offset' => $this->offset, 'length' => $this->length, 'user' => ['id' => $this->userId]];
    }
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityMentionName', 'offset' => $this->offset, 'length' => $this->length, 'user_id' => $this->userId];
    }
}
