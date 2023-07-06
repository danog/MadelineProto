<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\MessageService;

use danog\MadelineProto\EventHandler\BasicFilter\Incoming;
use danog\MadelineProto\MTProto;

/**
 * Represents an incoming group service message.
 */
final class IncomingGroupMessageService extends GroupMessageService implements Incoming
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage, false);
    }
}
