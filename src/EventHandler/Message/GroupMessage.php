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

namespace danog\MadelineProto\EventHandler\Message;

use AssertionError;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Participant;
use danog\MadelineProto\EventHandler\Participant\Admin;
use danog\MadelineProto\EventHandler\Participant\Banned;
use danog\MadelineProto\EventHandler\Participant\Creator;
use danog\MadelineProto\EventHandler\Participant\Left;
use danog\MadelineProto\EventHandler\Participant\Member;
use danog\MadelineProto\EventHandler\Participant\MySelf;

/**
 * Represents an incoming or outgoing group message.
 */
final class GroupMessage extends Message
{
    /**
     * Get info about a [channel/supergroup](https://core.telegram.org/api/channel) participant.
     *
     * @param string|integer|null $member Participant to get info about; can be empty or null to get info about the sender of the message.
     * @throws AssertionError
     */
    public function getMember(string|int|null $member = null): Participant
    {
        $client = $this->getClient();
        $member ??= $this->senderId;
        $result = $client->methodCallAsyncRead(
            'channels.getParticipant',
            [
                'channel' => $this->chatId,
                'participant' => $member,
            ]
        )['participant'];

        return match ($result['_']) {
            'channelParticipant' => new Member($result),
            'channelParticipantLeft' => new Left($client, $result),
            'channelParticipantSelf' => new MySelf($result),
            'channelParticipantAdmin' => new Admin($result),
            'channelParticipantBanned' => new Banned($client, $result),
            'channelParticipantCreator' => new Creator($result),
            default => throw new AssertionError("undefined Participant type: {$result['_']}")
        };
    }

    /**
     * Ban message sender from current supergroup.
     *
     * @param int $untilDate Validity of said permissions (it is considered forever any value less then 30 seconds or more then 366 days).
     */
    public function ban(int $untilDate = 0): void
    {
        $chatBannedRights = [
            '_' => 'chatBannedRights',
            'view_messages' => false,
            'send_messages' => false,
            'send_media' => false,
            'send_stickers' => false,
            'send_gifs' => false,
            'send_games' => false,
            'send_inline' => false,
            'embed_links' => false,
            'send_polls' => false,
            'change_info' => false,
            'invite_users' => false,
            'pin_messages' => false,
            'manage_topics' => false,
            'send_photos' => false,
            'send_videos' => false,
            'send_roundvideos' => false,
            'send_audios' => false,
            'send_voices' => false,
            'send_docs' => false,
            'send_plain' => false,
            'until_date' => $untilDate
        ];
        $this->getClient()->methodCallAsyncRead(
            'channels.editBanned',
            [
                'channel' => $this->chatId,
                'participant' => $this->senderId,
                'banned_rights' => $chatBannedRights
            ]
        );
    }

    /**
     * Unban message sender from current supergroup.
     *
     * @param int $untilDate Validity of said permissions (it is considered forever any value less then 30 seconds or more then 366 days).
     */
    public function unban(int $untilDate = 0): void
    {
        $chatBannedRights = [
            '_' => 'chatBannedRights',
            'view_messages' => true,
            'send_messages' => true,
            'send_media' => true,
            'send_stickers' => true,
            'send_gifs' => true,
            'send_games' => true,
            'send_inline' => true,
            'embed_links' => true,
            'send_polls' => true,
            'change_info' => true,
            'invite_users' => true,
            'pin_messages' => true,
            'manage_topics' => true,
            'send_photos' => true,
            'send_videos' => true,
            'send_roundvideos' => true,
            'send_audios' => true,
            'send_voices' => true,
            'send_docs' => true,
            'send_plain' => true,
            'until_date' => $untilDate
        ];
        $this->getClient()->methodCallAsyncRead(
            'channels.editBanned',
            [
                'channel' => $this->chatId,
                'participant' => $this->senderId,
                'banned_rights' => $chatBannedRights
            ]
        );
    }

    /**
     * Kick message sender from current supergroup.
     */
    public function kick(): void
    {
        $this->ban();
        $this->unban();
    }

    /**
     * Delete all supergroup message.
     */
    public function deleteAll(bool $forEveryone = true, int $maxId = 0): void
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.deleteHistory',
            [
                'channel' => $this->chatId,
                'for_everyone' => $forEveryone,
                'max_id' => $maxId
            ]
        );
    }
}
