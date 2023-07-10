<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow any non-service message.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterMessage extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof Message;
    }
}
