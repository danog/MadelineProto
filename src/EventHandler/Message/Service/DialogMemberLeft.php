<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * A member left the chat or channel.
 */
final class DialogMemberLeft extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** ID of the user that left the channel */
        public readonly int $left,
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
