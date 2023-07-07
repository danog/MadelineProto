<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages containing one of the specified commands.
 */
final class FilterCommands extends Filter
{
    private readonly array $commands;
    public function __construct(string ...$commands)
    {
        foreach ($commands as $command) {
            Assert::true(\preg_match("/^\w+$/", $command));
        }
        $this->commands = $commands;
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && \in_array($update->command, $this->commands, true);
    }
}
