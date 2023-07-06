<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Service;

use danog\MadelineProto\EventHandler\Service;

/**
 * Some members joined the chat or channel.
 */
final class DialogMembersJoined extends Service
{
    public function __construct(
        /** @var list<int> List of IDs of the user that joined the chat or channel. */
        public readonly array $joined
    ) {
    }
}
