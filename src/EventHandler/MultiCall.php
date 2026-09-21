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

/**
 * Common interface for the multi-party call types — {@see Calls\GroupCall} (video chats, livestreams) and
 * {@see Calls\ConferenceCall} (end-to-end encrypted conference calls) — on
 * top of the media surface every call shares ({@see Call}).
 *
 * It covers what a call with more than two participants adds over a one-to-one {@see Calls\PrivateCall} call:
 * leaving without ending it for everyone else, and enumerating who is in it. Type-specific operations
 * (a group call's {@see EventHandler\Calls\GroupCall::invite()} / per-participant recording, a conference's
 * verification emojis / encrypted messages / participant removal) live on the concrete classes.
 */
interface MultiCall extends Call
{
    /**
     * Leave the call, keeping it running for the other participants (unlike {@see Call::discard()},
     * which ends it).
     */
    public function leave(): static;

    /**
     * The participants currently known to be in the call, keyed by their id. The element type is
     * call-type specific (a {@see \danog\MadelineProto\GroupCall\Participant} for a group call, chain state for a
     * conference), so the concrete class documents it.
     *
     * @return array<int, mixed>
     */
    public function getParticipants(): array;
}
