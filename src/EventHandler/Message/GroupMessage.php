<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing group message.
 */
abstract class GroupMessage extends Message
{
    /** ID of the sender of the message (equal to the chatId for anonymous admins) */
    public readonly int $senderId;

    /** @internal */
    protected function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage);

        $this->senderId = isset($rawMessage['from_id'])
            ? $this->API->getIdInternal($rawMessage['from_id'])
            : $this->chatId;
    }
}
