<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\MessageService;

use danog\MadelineProto\EventHandler\MessageService;
use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing group service message.
 */
abstract class GroupMessageService extends MessageService
{
    /** ID of the sender of the message (equal to the chatId for anonymous admins) */
    public readonly int $senderId;

    /** @internal */
    protected function __construct(
        MTProto $API,
        array $rawMessage,
        bool $outgoing
    ) {
        parent::__construct($API, $rawMessage, $outgoing);

        $this->senderId = isset($rawMessage['from_id'])
            ? $this->API->getIdInternal($rawMessage['from_id'])
            : $this->chatId;
    }
}
