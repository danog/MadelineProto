<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Query\ButtonQuery;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Filters based on the content of a button query.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class FilterButtonQueryData extends Filter
{
    public function __construct(
        private readonly string $content
    ) {
        Assert::notEmpty($content);
    }
    public function apply(Update $update): bool
    {
        return $update instanceof ButtonQuery && $update->data === $this->content;
    }
}
