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

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Calls\ConferenceCall;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * A service message about an [end-to-end encrypted conference call »](https://core.telegram.org/api/group-calls#conference-calls):
 * an invitation to one (incoming or outgoing), a missed one, or one that ended.
 */
final class DialogConferenceCall extends ServiceMessage
{
    /**
     * @internal
     *
     * @param list<int> $otherParticipants
     */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,
        /** ID of the conference call. */
        public readonly int $callId,
        /** Whether we missed the call. */
        public readonly bool $missed,
        /** Whether the call is still active. */
        public readonly bool $active,
        /** Whether this is a video call. */
        public readonly bool $video,
        /** Duration of the call in seconds, if it ended. */
        public readonly ?int $duration,
        /**
         * Bot API IDs of the other participants of the call.
         *
         * @var list<int>
         */
        public readonly array $otherParticipants,
    ) {
        parent::__construct($API, $rawMessage, $info);
    }

    /**
     * Accept the invitation: join the conference call.
     *
     * @param bool $muted Whether to join muted.
     */
    public function join(bool $muted = false): ConferenceCall
    {
        return $this->getClient()->joinConferenceCallByInviteMessage($this->id, $muted);
    }

    /**
     * Decline the invitation.
     */
    public function decline(): void
    {
        $this->getClient()->declineConferenceCallInvite($this->id);
    }
}
