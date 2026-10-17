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
 * What a participant of a video chat ({@see GroupCallParticipant}) and of a live story
 * ({@see LiveStoryParticipant}) have in common, mirroring the shared fields of
 * [groupCallParticipant](https://core.telegram.org/constructor/groupCallParticipant).
 */
abstract class AbstractGroupCallParticipant extends MultiCallParticipant
{
    /**
     * @internal
     *
     * @param list<int> $videoSources        Signed SSRCs of this participant's camera video streams, empty if none.
     * @param list<int> $presentationSources Signed SSRCs of this participant's screen-share (presentation) streams, empty if none.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        int $peerId,
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
        /** @var list<int> */
        public readonly array $videoSources,
        /** @var list<int> */
        public readonly array $presentationSources,
        /** The SFU endpoint id of this participant's camera stream, needed to subscribe to it. */
        public readonly ?string $videoEndpoint,
        /** The SFU endpoint id of this participant's screen-share stream, needed to subscribe to it. */
        public readonly ?string $presentationEndpoint,
        /**
         * Playback volume, from 1 to 20000 where 10000 is 100%.
         *
         * @var int<1, 20000>
         */
        public readonly int $volume,
        /** Bio of the participant, if any. */
        public readonly ?string $about,
        /** Raised hand rating, if the participant raised their hand. */
        public readonly ?int $raiseHandRating,
        /** Whether the participant's volume was set by an admin. */
        public readonly bool $volumeByAdmin,
    ) {
        parent::__construct($peerId);
    }

    /**
     * The common {@see groupCallParticipant} fields, as named constructor arguments shared by every
     * subclass, applying the `min` rules (a min update keeps `volume`, `muted_by_you` and the video
     * source groups from the cached state, see https://core.telegram.org/constructor/groupCallParticipant).
     *
     * @param array<string, mixed> $participant
     * @param self|null            $cached      The previously known state of this participant, if any.
     *
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    final protected static function commonArgs(array $participant, int $peerId, ?self $cached): array
    {
        $min = $participant['min'];
        // Video/presentation source groups are only carried in full (non-min) updates; a min update
        // keeps whatever the cached state already had, exactly like volume and muted_by_you.
        $video = self::videoSources($participant['video'] ?? null);
        $presentation = self::videoSources($participant['presentation'] ?? null);
        $videoEndpoint = self::endpoint($participant['video'] ?? null);
        $presentationEndpoint = self::endpoint($participant['presentation'] ?? null);
        if ($min && $cached !== null) {
            if (!\is_array($participant['video'] ?? null)) {
                $video = $cached->videoSources;
                $videoEndpoint = $cached->videoEndpoint;
            }
            if (!\is_array($participant['presentation'] ?? null)) {
                $presentation = $cached->presentationSources;
                $presentationEndpoint = $cached->presentationEndpoint;
            }
        }
        return [
            'peerId' => $peerId,
            'source' => $participant['source'],
            'date' => $participant['date'],
            'activeDate' => $participant['active_date'] ?? null,
            'muted' => $participant['muted'],
            'canSelfUnmute' => $participant['can_self_unmute'],
            'mutedByYou' => $min && $cached !== null ? $cached->mutedByYou : $participant['muted_by_you'],
            'self' => $participant['self'],
            'justJoined' => $participant['just_joined'],
            'videoJoined' => $participant['video_joined'],
            'videoSources' => $video,
            'presentationSources' => $presentation,
            'videoEndpoint' => $videoEndpoint,
            'presentationEndpoint' => $presentationEndpoint,
            'volume' => $min && $cached !== null ? $cached->volume : ($participant['volume'] ?? 10000),
            'about' => $participant['about'] ?? null,
            'raiseHandRating' => $participant['raise_hand_rating'] ?? null,
            'volumeByAdmin' => $participant['volume_by_admin'],
        ];
    }

    /**
     * Flatten the source SSRCs of a
     * [groupCallParticipantVideo](https://core.telegram.org/constructor/groupCallParticipantVideo),
     * taking each source group's primary (first) source and every simulcast layer, deduplicated.
     * Retransmission (FID) pairs contribute only their main source, since we do not record rtx.
     *
     * @param mixed $video The `video` or `presentation` field of a groupCallParticipant, if present.
     *
     * @return list<int> Signed SSRCs.
     *
     * @psalm-pure
     */
    private static function videoSources(mixed $video): array
    {
        if (!\is_array($video) || ($video['paused'] ?? false)) {
            return [];
        }
        $sources = [];
        foreach ($video['source_groups'] ?? [] as $group) {
            $sim = ($group['semantics'] ?? '') === 'SIM';
            foreach ($group['sources'] ?? [] as $index => $source) {
                if ($sim || $index === 0) {
                    $sources[$source] = true;
                }
            }
        }
        return array_values(array_keys($sources));
    }

    /**
     * The `endpoint` of a groupCallParticipantVideo, the key used to subscribe to that stream over
     * the colibri data channel.
     *
     * @psalm-pure
     */
    private static function endpoint(mixed $video): ?string
    {
        if (!\is_array($video)) {
            return null;
        }
        $endpoint = $video['endpoint'] ?? null;
        return \is_string($endpoint) && $endpoint !== '' ? $endpoint : null;
    }
}
