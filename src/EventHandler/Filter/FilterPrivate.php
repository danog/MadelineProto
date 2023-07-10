<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only updates coming from private chats.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterPrivate extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof PrivateMessage;
    }
}
