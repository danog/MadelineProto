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

        /** @var list<int> List of IDs of the user that joined the chat or channel. */
        public readonly array $joined
    ) {
        parent::__construct($API, $rawMessage);
    }
}
