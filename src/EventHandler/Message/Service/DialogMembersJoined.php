<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * Some members joined the chat or channel.
 */
final class DialogMembersJoined extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** @var list<int> List of IDs of the users that joined the chat or channel. */
        public readonly array $joined
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
