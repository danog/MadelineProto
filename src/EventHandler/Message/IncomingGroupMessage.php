<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\MTProto;

/**
 * Represents an incoming group message.
 */
final class IncomingGroupMessage extends GroupMessage implements Incoming
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage, false);
    }
}
