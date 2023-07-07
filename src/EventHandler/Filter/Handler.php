<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Update;

/**
 * Filter that always allows all updates.
 */
final class Handler extends AbstractFilter
{
    public function __construct()
    {
    }
    public function apply(Update $update): bool
    {
        return true;
    }
}
