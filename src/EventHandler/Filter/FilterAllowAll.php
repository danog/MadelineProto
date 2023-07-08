<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Update;

/**
 * Allow all updates.
 */
final class FilterAllowAll extends Filter
{
    public function apply(Update $update): bool
    {
        return true;
    }
}
