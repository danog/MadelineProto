<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Service;

use danog\MadelineProto\EventHandler\Service;

/**
 * A member left the chat or channel.
 */
final class DialogMemberLeft extends Service
{
    /** ID of the user that left the channel */
    public readonly int $left;
}
