<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\AbstractButtonQuery;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

#[Attribute(Attribute::TARGET_METHOD)]
class FilterCallback extends Filter
{
    public function __construct(
        private readonly string $content
    ) {
        Assert::notEmpty($content);
    }
    public function apply(Update $update): bool
    {
        return $update instanceof AbstractButtonQuery && (string) $update->data === $this->content;
    }
}
