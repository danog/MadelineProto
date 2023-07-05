<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Update;

/**
 * NOTs a filter.
 */
final class FilterNot extends Filter
{
    public function __construct(public readonly Filter $filter)
    {
    }
    public function apply(Update $update): bool
    {
        return !$this->apply($update);
    }
}
