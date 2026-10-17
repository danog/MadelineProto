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

namespace danog\MadelineProto\EventHandler\Calls;

use JsonSerializable;

/**
 * A participant of a multi-party call, returned by {@see \danog\MadelineProto\EventHandler\MultiCall::getParticipants()}
 * and {@see \danog\MadelineProto\EventHandler\MultiCall::getParticipant()}.
 *
 * The concrete type depends on the call: a {@see \danog\MadelineProto\EventHandler\Calls\GroupCallParticipant}
 * for a group call (video chat or livestream, {@see AbstractGroupCall}), or a {@see ConferenceCallParticipant}
 * for an end-to-end encrypted {@see ConferenceCall}.
 */
abstract class MultiCallParticipant implements JsonSerializable
{
    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(
        /** Bot API ID of the peer this participant is (a user id, in a conference). */
        public readonly int $peerId,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
