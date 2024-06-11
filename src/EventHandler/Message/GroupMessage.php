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
use danog\DialogId\DialogId;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\Service\DialogTopicCreated;
use danog\MadelineProto\EventHandler\Message\Service\DialogTopicEdited;
use danog\MadelineProto\EventHandler\Participant;
use danog\MadelineProto\EventHandler\Topic\IconColor;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * Represents an incoming or outgoing group message.
 */
class GroupMessage extends Message
{
    /**
     * Get info about a [channel/supergroup](https://core.telegram.org/api/channel) participant.
     *
     * @param  string|integer|null $member Participant to get info about; can be empty or null to get info about the sender of the message.
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
        return Participant::fromRawParticipant($result);
    }

    /**
     * Hide the participants list in a [supergroup](https://core.telegram.org/api/channel).
     * The supergroup must have at least `hidden_members_group_size_min` participants in order to use this method, as specified by the [client configuration parameters »](https://core.telegram.org/api/config#client-configuration).
     *
     * @throws InvalidArgumentException
     */
    public function hideMembers(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleParticipantsHidden',
            [
                'channel' => $this->chatId,
                'enabled' => true,
            ]
        );
    }

    /**
     * Display the participants list in a [supergroup](https://core.telegram.org/api/channel).
     * The supergroup must have at least `hidden_members_group_size_min` participants in order to use this method, as specified by the [client configuration parameters »](https://core.telegram.org/api/config#client-configuration).
     *
     * @throws InvalidArgumentException
     */
    public function unhideMembers(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleParticipantsHidden',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
    }

    /**
     * Hide message history for new supergroup users.
     *
     * @throws InvalidArgumentException
     */
    public function hideHistory(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.togglePreHistoryHidden',
            [
                'channel' => $this->chatId,
                'enabled' => true,
            ]
        );
    }

    /**
     * Unhide message history for new supergroup users.
     *
     * @throws InvalidArgumentException
     */
    public function unhideHistory(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.togglePreHistoryHidden',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
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
            'until_date' => $untilDate,
        ];
        $this->getClient()->methodCallAsyncRead(
            'channels.editBanned',
            [
                'channel' => $this->chatId,
                'participant' => $this->senderId,
                'banned_rights' => $chatBannedRights,
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
            'until_date' => $untilDate,
        ];
        $this->getClient()->methodCallAsyncRead(
            'channels.editBanned',
            [
                'channel' => $this->chatId,
                'participant' => $this->senderId,
                'banned_rights' => $chatBannedRights,
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
                'max_id' => $maxId,
            ]
        );
    }

    /**
     * Delete all messages sent by a specific participant of a given supergroup.
     *
     * @param  string|integer|null      $member The participant whose messages should be deleted, if null or absent defaults to the sender of the message.
     * @throws InvalidArgumentException
     */
    public function deleteUserMessages(string|int|null $member = null): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $member ??= $this->senderId;
        $this->getClient()->methodCallAsyncRead(
            'channels.deleteParticipantHistory',
            [
                'channel' => $this->chatId,
                'participant' => $member,
            ]
        );
    }

    /**
     * Turn a [basic group into a supergroup](https://core.telegram.org/api/channel#migration).
     *
     * @return integer                  the channel id we migrated to
     * @throws InvalidArgumentException
     */
    public function toSuperGroup(): int
    {
        Assert::true(DialogId::isChat($this->chatId));
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'messages.migrateChat',
            [
                'chat_id' => $this->chatId,
            ]
        );
        $v = $client->getIdInternal($result['updates'][0]);
        \assert($v !== null);
        return $v;
    }

    /**
     * Enable the [native antispam system](https://core.telegram.org/api/antispam).
     *
     * @throws InvalidArgumentException
     */
    public function enableAntiSpam(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleAntiSpam',
            [
                'channel' => $this->chatId,
                'enabled' => true,
            ]
        );
    }

    /**
     * Disable the [native antispam system](https://core.telegram.org/api/antispam).
     *
     * @throws InvalidArgumentException
     */
    public function disableAntiSpam(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleAntiSpam',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
    }

    /**
     * Enable [forum functionality](https://core.telegram.org/api/forum) in a supergroup.
     *
     */
    public function enableTopics(): void
    {
        if (!$this->topicId) {
            $this->getClient()->methodCallAsyncRead(
                'channels.toggleForum',
                [
                    'channel' => $this->chatId,
                    'enabled' => true,
                ]
            );
        }
    }

    /**
     * Disable [forum functionality](https://core.telegram.org/api/forum) in a supergroup.
     *
     */
    public function disableTopics(): void
    {
        if ($this->topicId) {
            $this->getClient()->methodCallAsyncRead(
                'channels.toggleForum',
                [
                    'channel' => $this->chatId,
                    'enabled' => false,
                ]
            );
        }
    }

    /**
     * Create a [forum topic](https://core.telegram.org/api/forum); requires [`manage_topics` rights](https://core.telegram.org/api/rights).
     *
     * @param string        $title Topic title (maximum UTF-8 length: 128)
     * @param IconColor|int $icon  Icon color, or ID of the [custom emoji](https://core.telegram.org/api/custom-emoji) used as topic icon.
     *                             [Telegram Premium](https://core.telegram.org/api/premium) users can use any custom emoji, other users can only use the custom emojis contained in the [inputStickerSetEmojiDefaultTopicIcons](https://docs.madelineproto.xyz/API_docs/constructors/inputStickerSetEmojiDefaultTopicIcons.html) emoji pack.
     *                             If no custom emoji icon is specified, specifies the color of the fallback topic icon
     *
     * @throws InvalidArgumentException
     */
    public function createTopic(string $title, IconColor|int $icon = IconColor::NONE): DialogTopicCreated
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        if (!$this->topicId) {
            $this->enableTopics();
        }
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'channels.createForumTopic',
            [
                'channel' => $this->chatId,
                'title' => $title,
                ...\is_int($icon)
                    ? ['icon_emoji_id' => $icon]
                    : ['icon_color' => $icon->value],
            ]
        );
        return $client->wrapMessage($client->extractMessage($result));
    }

    /**
     * Edit a [forum topic](https://core.telegram.org/api/forum); requires [`manage_topics` rights](https://core.telegram.org/api/rights).
     *
     * @param  string                   $title   Topic title (maximum UTF-8 length: 128)
     * @param  integer                  $icon    ID of the [custom emoji](https://core.telegram.org/api/custom-emoji) used as topic icon. [Telegram Premium](https://core.telegram.org/api/premium) users can use any custom emoji, other users can only use the custom emojis contained in the [inputStickerSetEmojiDefaultTopicIcons](https://docs.madelineproto.xyz/API_docs/constructors/inputStickerSetEmojiDefaultTopicIcons.html) emoji pack. Pass 0 to switch to the fallback topic icon.
     * @param  integer|null             $topicId Topic ID, if absent defaults to the topic where this message was sent.
     * @throws InvalidArgumentException
     */
    public function editTopic(string $title, int $icon = 0, ?int $topicId = null): DialogTopicEdited
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $topicId ??= $this->topicId;
        Assert::notNull($topicId, "No topic ID was provided!");
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'channels.editForumTopic',
            [
                'channel' => $this->chatId,
                'topic_id' => $topicId,
                'title' => $title,
                'icon_emoji_id' => $icon,
            ]
        );
        return $client->wrapMessage($client->extractMessage($result));
    }

    /**
     * Open a [forum topic](https://core.telegram.org/api/forum); requires [`manage_topics` rights](https://core.telegram.org/api/rights).
     *
     * @param  integer|null             $topicId Topic ID, if absent defaults to the topic where this message was sent.
     * @throws InvalidArgumentException
     */
    public function openTopic(?int $topicId = null): DialogTopicEdited
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $topicId ??= $this->topicId;
        Assert::notNull($topicId, "No topic ID was provided!");
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'channels.editForumTopic',
            [
                'channel' => $this->chatId,
                'topic_id' => $topicId,
                'closed' => false,
            ]
        );
        return $client->wrapMessage($client->extractMessage($result));
    }

    /**
     * Close a [forum topic](https://core.telegram.org/api/forum); requires [`manage_topics` rights](https://core.telegram.org/api/rights).
     *
     * @param  integer|null             $topicId Topic ID, if absent defaults to the topic where this message was sent.
     * @throws InvalidArgumentException
     */
    public function closeTopic(?int $topicId = null): DialogTopicEdited
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $topicId ??= $this->topicId;
        Assert::notNull($topicId, "No topic ID was provided!");
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'channels.editForumTopic',
            [
                'channel' => $this->chatId,
                'topic_id' => $topicId,
                'closed' => true,
            ]
        );
        return $client->wrapMessage($client->extractMessage($result));
    }

    /**
     * Delete message history of a [forum topic](https://core.telegram.org/api/forum).
     *
     * @param  integer|null             $topicId Topic ID, if absent defaults to the topic where this message was sent.
     * @throws InvalidArgumentException
     */
    public function deleteTopic(?int $topicId = null): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $topicId ??= $this->topicId;
        Assert::notNull($topicId, "No topic ID was provided!");
        $this->getClient()->methodCallAsyncRead(
            'channels.deleteTopicHistory',
            [
                'channel' => $this->chatId,
                'top_msg_id' => $topicId,
            ]
        );
    }

    /**
     * Toggle supergroup slow mode: Users will only be able to send one message every `$seconds` seconds.
     *
     * @param integer $seconds Users will only be able to send one message every `$seconds` seconds
     * @throws InvalidArgumentException
     */
    public function enableSlowMode(int $seconds): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        Assert::false($seconds === 0);
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleSlowMode',
            [
                'channel' => $this->chatId,
                'seconds' => $seconds,
            ]
        );
    }

    /**
     * Disable supergroup slow mode.
     *
     * @throws InvalidArgumentException
     */
    public function disableSlowMode(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleSlowMode',
            [
                'channel' => $this->chatId,
                'seconds' => 0,
            ]
        );
    }

    /**
     * Enable or disable [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) on a chat.
     *
     */
    public function enableProtection(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'messages.toggleNoForwards',
            [
                'peer' => $this->chatId,
                'enabled' => true,
            ]
        );
    }

    /**
     * Enable or disable [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) on a chat.
     *
     */
    public function disableProtection(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'messages.toggleNoForwards',
            [
                'peer' => $this->chatId,
                'enabled' => false,
            ]
        );
    }

    /**
     * Enable to all users [should join a discussion group in order to comment on a post »](https://core.telegram.org/api/discussion#requiring-users-to-join-the-group).
     *
     */
    public function enableJoinToComment(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleJoinToSend',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
    }

    /**
     * Disable to all users [should join a discussion group in order to comment on a post »](https://core.telegram.org/api/discussion#requiring-users-to-join-the-group).
     *
     */
    public function disableJoinToComment(): void
    {
        Assert::true(DialogId::isSupergroupOrChannel($this->chatId));
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleJoinToSend',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
    }
}
