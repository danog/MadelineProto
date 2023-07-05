<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Filter only messages containing the specified command.
 */
final class FilterCommand extends Filter
{
    public function __construct(private readonly string $command)
    {
        Assert::true(\preg_match("/^\w+$/", $command));
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->command === $this->command;
    }
}
