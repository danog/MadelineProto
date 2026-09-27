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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler;

use AssertionError;
use danog\MadelineProto\CallNotAllowedException;
use danog\MadelineProto\EventHandler\Calls\CallDenialReason;
use danog\MadelineProto\EventHandler\Calls\GroupCall;
use danog\MadelineProto\MTProto;

/**
 * Represents a group or channel message.
 */
abstract class GroupOrChannelMessage extends Message
{
    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info, bool $scheduled)
    {
        parent::__construct($API, $rawMessage, $info, $scheduled);
    }

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
     * Enable [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) on a chat.
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
     * Disable [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) on a chat.
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
     * Get the video chat or livestream currently active in this chat, if any.
     */
    #[\Override]
    public function getCall(): ?GroupCall
    {
        return $this->getClient()->getGroupCall($this->chatId);
    }

    /**
     * Whether {@see self::requestCall()} will succeed, i.e. there is already an active video chat or
     * livestream, or we have the rights to create one (`manage_call` admin right).
     */
    #[\Override]
    public function canRequestCall(): bool
    {
        return $this->getCall() !== null || $this->getCallDenialReason() === null;
    }

    /**
     * Get the video chat or livestream currently active in this chat, or create one if there is none.
     *
     * Requires the `manage_call` admin right to create a new call, see
     * [video chats/livestreams »](https://core.telegram.org/api/group-calls#video-chats-livestreams).
     *
     * Note that the call is not joined automatically, use `join()` on the returned call to join it.
     *
     * @param string|null $title        Custom title for a newly created call, defaults to the chat name.
     * @param int|null    $scheduleDate If set, a newly created call is scheduled to start at the specified UNIX timestamp.
     * @param bool        $rtmpStream   Whether the media of a newly created call is published by an external RTMP application.
     *
     * @throws CallNotAllowedException If there is no active call, and we can't create one.
     */
    #[\Override]
    public function requestCall(?string $title = null, ?int $scheduleDate = null, bool $rtmpStream = false): GroupCall
    {
        $call = $this->getCall();
        if ($call !== null) {
            return $call;
        }
        $reason = $this->getCallDenialReason();
        if ($reason !== null) {
            throw new CallNotAllowedException($reason);
        }
        return $this->getClient()->createGroupCall($this->chatId, $title, $scheduleDate, $rtmpStream);
    }

    /**
     * Returns why we can't create a group call in this chat, or null if we can.
     */
    #[\Override]
    public function getCallDenialReason(): ?CallDenialReason
    {
        $client = $this->getClient();
        if ($client->isSelfBot()) {
            return CallDenialReason::BOTS_CANNOT_CREATE_GROUP_CALLS;
        }
        $chat = $client->getInfo($this->chatId)['Chat'] ?? [];
        if (($chat['left'] ?? false) || ($chat['deactivated'] ?? false)) {
            return CallDenialReason::NOT_A_MEMBER;
        }
        if (!($chat['creator'] ?? false) && !($chat['admin_rights']['manage_call'] ?? false)) {
            return CallDenialReason::MANAGE_CALL_RIGHT_REQUIRED;
        }
        return null;
    }
}
