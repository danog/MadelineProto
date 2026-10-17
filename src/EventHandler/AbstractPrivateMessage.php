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

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\CallNotAllowedException;
use danog\MadelineProto\EventHandler\Calls\CallDenialReason;
use danog\MadelineProto\EventHandler\Calls\PrivateCall;
use danog\MadelineProto\EventHandler\Message\Service\DialogScreenshotTaken;
use danog\MadelineProto\MTProto;

/**
 * Represents a private or secret chat message.
 */
abstract class AbstractPrivateMessage extends Message
{
    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info, bool $scheduled)
    {
        parent::__construct($API, $rawMessage, $info, $scheduled);
    }

    /**
     * Notify the other user in a private chat that a screenshot of the chat was taken.
     *
     * @psalm-impure
     */
    abstract public function screenShot(): DialogScreenshotTaken;

    /**
     * ID of the other user in this private or secret chat.
     *
     * @psalm-mutation-free
     */
    abstract protected function getCallPeer(): int;

    /**
     * Get the pending or running one-to-one call with the other user of this chat, if any.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function getCall(): ?PrivateCall
    {
        return $this->getClient()->getCallByPeer($this->getCallPeer());
    }

    /**
     * Whether {@see self::requestCall()} will succeed, i.e. there is already a call with the other user,
     * or we can call them.
     *
     * @param bool $video Whether to check if a video call can be started.
     */
    #[\Override]
    public function canRequestCall(bool $video = false): bool
    {
        return $this->getCall() !== null || $this->getCallDenialReason($video) === null;
    }

    /**
     * Get the pending or running one-to-one call with the other user of this chat, or call them if there is none.
     *
     * @param bool $video Whether to start a video call.
     *
     * @throws CallNotAllowedException If the other user cannot be called.
     */
    #[\Override]
    public function requestCall(bool $video = false): PrivateCall
    {
        $call = $this->getCall();
        if ($call !== null) {
            return $call;
        }
        $reason = $this->getCallDenialReason($video);
        if ($reason !== null) {
            throw new CallNotAllowedException($reason);
        }
        return $this->getClient()->requestCall($this->getCallPeer(), $video);
    }

    /**
     * Returns why we can't call the other user, or null if we can.
     *
     * @param bool $video Whether to check if a video call can be started.
     */
    #[\Override]
    public function getCallDenialReason(bool $video = false): ?CallDenialReason
    {
        $client = $this->getClient();
        if ($client->isSelfBot()) {
            return CallDenialReason::BOTS_CANNOT_CALL;
        }
        $peer = $this->getCallPeer();
        $self = $client->getSelf();
        if ($self !== false && $peer === $self['id']) {
            return CallDenialReason::CANNOT_CALL_SELF;
        }
        $info = $client->getFullInfo($peer);
        /** @var array{phone_calls_available?: bool, phone_calls_private?: bool, video_calls_available?: bool} */
        $full = $info['full'] ?? [];
        if (!($full['phone_calls_available'] ?? false)) {
            return CallDenialReason::CALLS_UNAVAILABLE;
        }
        if ($full['phone_calls_private'] ?? false) {
            return CallDenialReason::PRIVACY_SETTINGS;
        }
        if ($video && !($full['video_calls_available'] ?? false)) {
            return CallDenialReason::VIDEO_CALLS_UNAVAILABLE;
        }
        return null;
    }
}
