<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Combinator;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Update;

/**
 * NOTs a filter.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterNot extends Filter
{
    public function __construct(private readonly Filter $filter)
    {
    }
    public function initialize(EventHandler $API): ?Filter
    {
        $filter = $this->filter->initialize($API);
        if ($filter === null) {
            return $this;
        }
        if ($filter instanceof self) {
            return $filter->filter;
        }
        return new self($filter);
    }

    public function apply(Update $update): bool
    {
        return !$this->apply($update);
    }
}
