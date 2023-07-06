<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\MessageService;

use danog\MadelineProto\EventHandler\BasicFilter\Outgoing;
use danog\MadelineProto\MTProto;

/**
 * Represents an outgoing group service message.
 */
final class OutgoingGroupMessageService extends GroupMessageService implements Outgoing
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage, true);
    }
}
