<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only updates of a certain class type.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterClass extends Filter
{
    public function __construct(private readonly string $class)
    {
        Assert::true(\is_subclass_of($class, Update::class), "$class is not a subclass of Update!");
    }
    public function apply(Update $update): bool
    {
        return $update instanceof $this->class;
    }
}
