<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\MessageService;

use danog\MadelineProto\EventHandler\MessageService;
use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing channel service message.
 */
abstract class ChannelMessageService extends MessageService
{
    /** @internal */
    protected function __construct(
        MTProto $API,
        array $rawMessage,
        bool $out
    ) {
        parent::__construct($API, $rawMessage, $out);
    }
}
