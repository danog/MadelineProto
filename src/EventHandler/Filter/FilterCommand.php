<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

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
     * @param string            $command Command
     * @param list<CommandType> $types   Command types, if empty all command types are allowed.
     */
    public function __construct(private readonly string $command, array $types = [CommandType::BANG, CommandType::DOT, CommandType::SLASH])
    {
        Assert::true(preg_match("/^[\w@]+$/", $command) === 1, "An invalid command was specified!");
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
