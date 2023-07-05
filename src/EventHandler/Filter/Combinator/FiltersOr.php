<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Combinator;

use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * ORs multiple filters.
 */
final class FiltersOr extends Filter
{
    /** @var array<Filter> */
    private readonly array $filters;
    public function __construct(Filter ...$filters)
    {
        Assert::notEmpty($filters);
        $this->filters = $filters;
    }
    public function apply(Update $update): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->apply($update)) {
                return true;
            }
        }
        return false;
    }
}
