<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use AssertionError;
use Attribute;
use danog\MadelineProto\EventHandler\CommandType;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages containing the specified command.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterCommand extends Filter
{
    /**
     * @var array<CommandType>
     */
    public readonly array $commandTypes;
    /**
     * @param string $command Command
     * @param list<CommandType> $types Command types, if empty all command types are allowed.
     */
    public function __construct(private readonly string $command, array $types = [CommandType::BANG, CommandType::DOT, CommandType::SLASH])
    {
        Assert::true(\preg_match("/^\w+$/", $command) === 1, "An invalid command was specified!");
        Assert::notEmpty($types, 'No command types were specified!');
        $c = [];
        foreach ($types as $type) {
            if (isset($c[$type->value])) {
                throw new AssertionError($type->value." was already specified!");
            }
            $c[$type->value] = true;
        }
        $this->commandTypes = $types;
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->command === $this->command && \in_array($update->commandType, $this->commandTypes, true);
    }
}
