<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only incoming messages.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterIncoming extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof AbstractMessage && !$update->out;
    }
}
