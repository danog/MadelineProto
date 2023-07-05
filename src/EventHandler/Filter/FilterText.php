<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Filter text-only messages.
 */
final class FilterText extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->media === null;
    }
}
