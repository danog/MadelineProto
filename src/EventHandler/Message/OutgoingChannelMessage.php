<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\EventHandler\BasicFilter\Outgoing;
use danog\MadelineProto\MTProto;

/**
 * Represents an outgoing channel message.
 */
final class OutgoingChannelMessage extends ChannelMessage implements Outgoing
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage, true);
    }
}
