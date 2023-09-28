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

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\AbstractStory;
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
 * Allow only messages coming from the admin (defined as the peers returned by getReportPeers).
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterFromAdmin extends Filter
{
    private readonly array $adminIds;
    public function initialize(EventHandler $API): Filter
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->adminIds = $API->getAdminIds();
        return $this;
    }
    public function apply(Update $update): bool
    {
        return ($update instanceof AbstractMessage && \in_array($update->senderId, $this->adminIds, true)) ||
            ($update instanceof AbstractStory && \in_array($update->senderId, $this->adminIds, true)) ||
            ($update instanceof StoryReaction && \in_array($update->senderId, $this->adminIds, true)) ||
            ($update instanceof ButtonQuery && \in_array($update->userId, $this->adminIds, true)) ||
            ($update instanceof InlineQuery && \in_array($update->userId, $this->adminIds, true)) ||
            ($update instanceof Typing && \in_array($update->userId, $this->adminIds, true)) ||
            ($update instanceof Blocked && \in_array($update->userId, $this->adminIds, true)) ||
            ($update instanceof BotStopped && \in_array($update->userId, $this->adminIds, true)) ||
            ($update instanceof Phone && \in_array($update->userId, $this->adminIds, true)) ||
            ($update instanceof Status && \in_array($update->userId, $this->adminIds, true)) ||
            ($update instanceof Username && \in_array($update->userId, $this->adminIds, true));
    }
}
