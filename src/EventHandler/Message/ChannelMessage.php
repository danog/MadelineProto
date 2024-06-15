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
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCError\MsgIdInvalidError;

/**
 * Represents an incoming or outgoing channel message.
 */
final class ChannelMessage extends Message
{
    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info, bool $scheduled)
    {
        parent::__construct($API, $rawMessage, $info, $scheduled);
    }

    /**
     * Obtains the copy of the current message, that was sent to the linked group.
     *
     * Can be used to reply in the comment section, for example:
     *
     * ```php
     * $update->getDiscussion()->reply("Comment");
     * ```
     *
     */
    public function getDiscussion(): ?GroupMessage
    {
        try {
            $r = $this->getClient()->methodCallAsyncRead(
                'messages.getDiscussionMessage',
                ['peer' => $this->chatId, 'msg_id' => $this->id]
            )['messages'];
            $r = end($r);
            $v = $this->getClient()->wrapMessage($r);
            \assert($v instanceof GroupMessage);
            return $v;
        } catch (MsgIdInvalidError) {
            return null;
        }
    }

    /**
     * Disable message signatures in channels.
     *
     */
    public function disableSignatures(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleSignatures',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
    }

    /**
     * Enable message signatures in channels.
     *
     */
    public function enableSignatures(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleSignatures',
            [
                'channel' => $this->chatId,
                'enabled' => true,
            ]
        );
    }

    /**
     * Get info about a [channel/supergroup](https://core.telegram.org/api/channel) participant.
     *
     * @param  string|integer $member Participant to get info about.
     * @throws AssertionError
     */
    public function getMember(string|int $member): Participant
    {
        $client = $this->getClient();
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
     * Increase the view counter of a current message in the channel.
     *
     */
    public function view(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'messages.getMessagesViews',
            [
                'peer' => $this->chatId,
                'id' => [$this->id],
                'increment' => true,
            ]
        );
    }

    /**
     * Hide message history for new channel users.
     *
     */
    public function hideHistory(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleParticipantsHidden',
            [
                'channel' => $this->chatId,
                'enabled' => true,
            ]
        );
    }

    /**
     * Unhide message history for new channel users.
     *
     */
    public function unhideHistory(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleParticipantsHidden',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
    }

    /**
     * Enable [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) on a channel.
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
     * Disable [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) on a channel.
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
}
