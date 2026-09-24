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

/**
 * A participant of a [live story »](https://core.telegram.org/api/group-calls#live-stories)
 * ({@see LiveStory}): a {@see groupCallParticipant} that additionally carries the Telegram Stars the
 * participant has donated ({@see self::$paidStarsTotal}).
 */
final class LiveStoryParticipant extends AbstractGroupCallParticipant
{
    /**
     * @internal
     *
     * @param list<int> $videoSources
     * @param list<int> $presentationSources
     * @param int<1, 20000> $volume Playback volume, from 1 to 20000 where 10000 is 100%.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        int $peerId,
        int $source,
        int $date,
        ?int $activeDate,
        bool $muted,
        bool $canSelfUnmute,
        bool $mutedByYou,
        bool $self,
        bool $justJoined,
        bool $videoJoined,
        array $videoSources,
        array $presentationSources,
        ?string $videoEndpoint,
        ?string $presentationEndpoint,
        int $volume,
        ?string $about,
        ?int $raiseHandRating,
        bool $volumeByAdmin,
        /** Total Telegram Stars donated by this participant, if any. */
        public readonly ?int $paidStarsTotal,
    ) {
        parent::__construct(
            $peerId,
            $source,
            $date,
            $activeDate,
            $muted,
            $canSelfUnmute,
            $mutedByYou,
            $self,
            $justJoined,
            $videoJoined,
            $videoSources,
            $presentationSources,
            $videoEndpoint,
            $presentationEndpoint,
            $volume,
            $about,
            $raiseHandRating,
            $volumeByAdmin,
        );
    }

    /**
     * @internal
     *
     * @param array<string, mixed>              $participant
     * @param AbstractGroupCallParticipant|null $cached      The previously known state of this participant, if any.
     *
     * @psalm-mutation-free
     */
    public static function fromRaw(array $participant, int $peerId, ?AbstractGroupCallParticipant $cached = null): self
    {
        return new self(
            ...self::commonArgs($participant, $peerId, $cached),
            paidStarsTotal: isset($participant['paid_stars_total']) ? (int) $participant['paid_stars_total'] : null,
        );
    }
}
