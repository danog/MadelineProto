<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message\ChannelMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only updates coming from channels.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterChannel extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof ChannelMessage;
    }
}
