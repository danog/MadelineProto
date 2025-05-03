<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2025 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Filter;

use AssertionError;
use Attribute;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\CommandType;
use danog\MadelineProto\EventHandler\Filter\Combinator\FiltersOr;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages containing the specified command, optionally postfixed with the bot's username.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterBotCommand extends Filter
{
    #[\Override]
    public function initialize(EventHandler $API): Filter
    {
        Assert::true($API->isSelfBot(), 'This filter can only be used by bots!');
        $filters = [new FilterCommand($this->command, [CommandType::SLASH])];
        foreach ($API->getInfo('me', API::INFO_TYPE_USERNAMES) as $username) {
            $filters[] = new FilterCommand("{$this->command}@$username", [CommandType::SLASH]);
        }
        return new FiltersOr(...$filters);
    }
    public function __construct(private readonly string $command)
    {
    }

    #[\Override]
    public function apply(Update $update): bool
    {
        throw new AssertionError("Unreachable!");
    }
}
