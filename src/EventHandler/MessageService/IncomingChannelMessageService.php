<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\MessageService;

use danog\MadelineProto\EventHandler\BasicFilter\Incoming;
use danog\MadelineProto\MTProto;

/**
 * Represents an incoming channel service message.
 */
final class IncomingChannelMessageService extends ChannelMessageService implements Incoming
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage, false);
    }
}
