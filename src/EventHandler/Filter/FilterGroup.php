<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only updates coming from groups.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterGroup extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof GroupMessage;
    }
}
