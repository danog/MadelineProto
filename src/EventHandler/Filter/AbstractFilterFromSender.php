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

use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\AbstractStory;
use danog\MadelineProto\EventHandler\BotCommands;
use danog\MadelineProto\EventHandler\ChatInviteRequester\BotChatInviteRequest;
use danog\MadelineProto\EventHandler\InlineQuery;
use danog\MadelineProto\EventHandler\Query\ButtonQuery;
use danog\MadelineProto\EventHandler\Story\StoryReaction;
use danog\MadelineProto\EventHandler\Typing;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\EventHandler\User\Blocked;
use danog\MadelineProto\EventHandler\User\BotStopped;
use danog\MadelineProto\EventHandler\User\Phone;
use danog\MadelineProto\EventHandler\User\Status;
use danog\MadelineProto\EventHandler\User\Username;

/**
 * Allow incoming or outgoing group messages made by a certain sender.
 *
 * @internal
 */
abstract class AbstractFilterFromSender extends Filter
{
    private readonly int $peerResolved;
    public function __construct(private readonly string|int $peer)
    {
    }
    public function initialize(EventHandler $API): Filter
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->peerResolved = $API->getId($this->peer);
        return $this;
    }
    public function apply(Update $update): bool
    {
        return ($update instanceof AbstractMessage && $update->senderId === $this->peerResolved) ||
            ($update instanceof AbstractStory && $update->senderId === $this->peerResolved) ||
            ($update instanceof StoryReaction && $update->senderId === $this->peerResolved) ||
            ($update instanceof ButtonQuery && $update->userId === $this->peerResolved) ||
            ($update instanceof InlineQuery && $update->userId === $this->peerResolved) ||
            ($update instanceof Typing && $update->userId === $this->peerResolved) ||
            ($update instanceof Blocked && $update->userId === $this->peerResolved) ||
            ($update instanceof BotStopped && $update->userId === $this->peerResolved) ||
            ($update instanceof Phone && $update->userId === $this->peerResolved) ||
            ($update instanceof Status && $update->userId === $this->peerResolved) ||
            ($update instanceof Username && $update->userId === $this->peerResolved) ||
            ($update instanceof BotCommands && $update->botId === $this->peerResolved) ||
            ($update instanceof BotChatInviteRequest && $update->userId === $this->peerResolved);
    }
}
