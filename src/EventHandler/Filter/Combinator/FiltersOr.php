<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Combinator;

use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Filter\Handler;
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
    public function initialize(EventHandler $API): Filter
    {
        $final = [];
        foreach ($this->filters as $filter) {
            $filter = $filter->initialize($API) ?? $filter;
            if ($filter instanceof self) {
                $final = \array_merge($final, $filter->filters);
            }
        }
        foreach ($final as $f) {
            if ($f instanceof Handler) {
                return $f;
            }
        }
        if (\count($final) === 1) {
            return $final[0];
        }
        return new self(...$final);
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
