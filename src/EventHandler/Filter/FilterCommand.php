<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages containing the specified command.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterCommand extends Filter
{
    public function __construct(private readonly string $command)
    {
        Assert::true(\preg_match("/^\w+$/", $command) === 1, "An invalid command was specified!");
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->command === $this->command;
    }
}
