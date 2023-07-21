<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * A message was pinned in a chat.
 */
final class DialogMessagePinned extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,
    ) {
        parent::__construct($API, $rawMessage, $info);
    }

    /**
     * Gets the pinned message.
     *
     * May return null if the pinned message was deleted.
     *
     * @template T as AbstractMessage
     *
     * @param class-string<T> $class Only return a reply if it is of the specified type, return null otherwise.
     *
     * @return ?T
     */
    public function getPinnedMessage(string $class = AbstractMessage::class): ?AbstractMessage
    {
        return $this->getReply($class);
    }
}
