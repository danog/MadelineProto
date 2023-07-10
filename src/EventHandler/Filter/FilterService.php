<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only service messages of any type.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterService extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof ServiceMessage;
    }
}
