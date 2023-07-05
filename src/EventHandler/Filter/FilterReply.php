<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Filter messages that reply to other messages.
 */
final class FilterReply extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->replyToMsgId !== null;
    }
}
