<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

/**
 * The [command set](https://core.telegram.org/api/bots/commands) of a certain bot in a certain chat has changed.
 */
final class BotCommands extends Update
{
    /** ID of the bot that changed its command set. */
    public readonly int $botId;

    /** The affected chat. */
    public readonly int $chatId;

    /** @var list<Command> New bot commands. */
    public readonly array $commands;

    /** @internal */
    public function __construct(MTProto $API, array $rawBotCommands)
    {
        parent::__construct($API);
        $this->botId = $rawBotCommands['bot_id'];
        $this->chatId = $API->getIdInternal($rawBotCommands['peer']);
        $this->commands = array_map(
            static fn (array $command): Command => new Command($command),
            $rawBotCommands['commands']
        );
    }
}
