<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\MTProto;

/**
 * Represents an outgoing group message.
 */
final class OutgoingGroupMessage extends GroupMessage implements Outgoing
{
    public readonly bool $out;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage);
        $this->out = true;
    }
}
