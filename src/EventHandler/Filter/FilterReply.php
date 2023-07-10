<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow messages that reply to other messages.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterReply extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->replyToMsgId !== null;
    }
}
