<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Combinator;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Filter\FilterAllowAll;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * ORs multiple filters.
 */
#[Attribute(Attribute::TARGET_METHOD)]
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
            $filter = $filter->initialize($API);
            if ($filter instanceof self) {
                $final = \array_merge($final, $filter->filters);
            } else {
                $final []= $filter;
            }
        }
        foreach ($final as $f) {
            if ($f instanceof FilterAllowAll) {
                return $f;
            }
        }
        return match (\count($final)) {
            0 => new FilterAllowAll,
            1 => $final[0],
            default => new self(...$final)
        };
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
