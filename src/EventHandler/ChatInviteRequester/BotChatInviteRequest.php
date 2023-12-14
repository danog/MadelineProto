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

namespace danog\MadelineProto\EventHandler\ChatInviteRequester;

use danog\MadelineProto\EventHandler\ChatInvite;
use danog\MadelineProto\EventHandler\ChatInviteRequester;
use danog\MadelineProto\MTProto;

/**
 * Indicates someone has requested to join a chat or channel (bots only).
 */
final class BotChatInviteRequest extends ChatInviteRequester
{
    /** When was the [join request »](https://core.telegram.org/api/invites#join-requests) made */
    public readonly int $date;

    /** The user ID that is asking to join the chat or channel */
    public readonly int $userId;

    /** Bio of the user */
    public readonly string $about;

    /** Chat invite link that was used by the user to send the [join request »](https://core.telegram.org/api/invites#join-requests) */
    public readonly ChatInvite $invite;

    /** @internal */
    public function __construct(MTProto $API, array $rawChatInviteRequester)
    {
        parent::__construct($API, $rawChatInviteRequester);
        $this->date = $rawChatInviteRequester['date'];
        $this->userId = $rawChatInviteRequester['user_id'];
        $this->about = $rawChatInviteRequester['about'];
        $this->invite = ChatInvite::fromRawChatInvite($rawChatInviteRequester['invite']);
    }
}
