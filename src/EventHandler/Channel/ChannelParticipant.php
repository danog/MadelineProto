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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Channel;

use danog\MadelineProto\EventHandler\ChatInvite;
use danog\MadelineProto\EventHandler\Participant;
use danog\MadelineProto\EventHandler\Participant\Left;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\MTProto;

/**
 * A participant has left, joined, was banned or admin'd in a [channel or supergroup](https://core.telegram.org/api/channel).
 */
final class ChannelParticipant extends Update
{
    /** Whether the participant joined using a [chat folder deep link »](https://core.telegram.org/api/links#chat-folder-links). */
    public readonly bool $viaChatlist;

    /** Channel ID */
    public readonly int $chatId;

    /** Date of the event */
    public readonly int $date;

    /** User that triggered the change (inviter, admin that kicked the user, or the even the userId itself) */
    public readonly int $actorId;

    /** User that was affected by the change */
    public readonly int $userId;

    /** Previous participant status */
    public readonly ?Participant $prevParticipant;

    /** New participant status */
    public readonly ?Participant $newParticipant;

    /** Chat invite used to join the channel/supergroup */
    public readonly ?ChatInvite $inviteLink;

    public function __construct(MTProto $API, array $rawChannelParticipant)
    {
        parent::__construct($API);
        $this->viaChatlist = $rawChannelParticipant['via_chatlist'];
        $this->chatId = $rawChannelParticipant['channel_id'];
        $this->date = $rawChannelParticipant['date'];
        $this->actorId = $rawChannelParticipant['actor_id'];
        $this->userId = $rawChannelParticipant['user_id'];
        $this->inviteLink = isset($rawChannelParticipant['invite'])
            ? ChatInvite::fromRawChatInvite($rawChannelParticipant['invite'])
            : null;
        // If null, user wasn't a participant.
        $this->prevParticipant = isset($rawChannelParticipant['prev_participant'])
            ? Participant::fromRawParticipant($rawChannelParticipant['prev_participant'])
            : new Left(['peer' => $this->userId]);
        // If null, user has left.
        $this->newParticipant = isset($rawChannelParticipant['new_participant'])
            ? Participant::fromRawParticipant($rawChannelParticipant['new_participant'])
            : new Left(['peer' => $this->userId]);
    }
}
