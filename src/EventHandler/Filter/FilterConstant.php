<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Update;

/**
 * Filter that always returns the specified boolean.
 */
final class FilterConstant extends Filter
{
    public function __construct(public readonly bool $result)
    {
    }
    public function apply(Update $update): bool
    {
        return $this->result;
    }
}
