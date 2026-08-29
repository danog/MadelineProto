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

namespace danog\MadelineProto\GroupCall;

use JsonSerializable;

/**
 * A participant of a group call, mirroring
 * [groupCallParticipant](https://core.telegram.org/constructor/groupCallParticipant).
 */
final class Participant implements JsonSerializable
{
    /**
     * @internal
     */
    public function __construct(
        /** Bot API ID of the peer that joined the call. */
        public readonly int $peerId,
        /** WebRTC audio source ID (SSRC) of this participant, `0` if unknown. */
        public readonly int $source,
        /** When did this participant join the call. */
        public readonly int $date,
        /** When was this participant last active, if ever. */
        public readonly ?int $activeDate,
        /** Whether the participant is muted. */
        public readonly bool $muted,
        /** Whether a muted participant may unmute themselves. */
        public readonly bool $canSelfUnmute,
        /** Whether we muted this participant only for ourselves. */
        public readonly bool $mutedByYou,
        /** Whether this participant is ourselves. */
        public readonly bool $self,
        /** Whether the participant just joined. */
        public readonly bool $justJoined,
        /** Whether the participant is transmitting video. */
        public readonly bool $videoJoined,
        /** Playback volume, from 1 to 20000 where 10000 is 100%. */
        public readonly int $volume,
        /** Bio of the participant, if any. */
        public readonly ?string $about,
        /** Raised hand rating, if the participant raised their hand. */
        public readonly ?int $raiseHandRating,
    ) {
    }

    /**
     * @internal
     *
     * @param self|null $cached The previously known state of this participant, if any: when the
     *                          `min` flag is set, `volume` and `muted_by_you` must be kept from it,
     *                          see https://core.telegram.org/constructor/groupCallParticipant.
     */
    public static function fromRaw(array $participant, int $peerId, ?self $cached = null): self
    {
        $min = $participant['min'];
        return new self(
            $peerId,
            $participant['source'] ?? 0,
            $participant['date'] ?? 0,
            $participant['active_date'] ?? null,
            $participant['muted'] ?? false,
            $participant['can_self_unmute'] ?? false,
            $min && $cached !== null ? $cached->mutedByYou : ($participant['muted_by_you'] ?? false),
            $participant['self'] ?? false,
            $participant['just_joined'] ?? false,
            $participant['video_joined'] ?? false,
            $min && $cached !== null ? $cached->volume : ($participant['volume'] ?? 10000),
            $participant['about'] ?? null,
            $participant['raise_hand_rating'] ?? null,
        );
    }

    /**
     * @internal
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
