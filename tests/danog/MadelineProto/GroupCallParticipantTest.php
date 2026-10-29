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

namespace danog\MadelineProto\Test;

use danog\MadelineProto\EventHandler\Calls\GroupCallParticipant;
use PHPUnit\Framework\TestCase;

/**
 * The video sources of a participant, from the source groups of its groupCallParticipantVideo.
 */
final class GroupCallParticipantTest extends TestCase
{
    private static function raw(bool $min, ?array $video): array
    {
        return [
            'min' => $min,
            'source' => 1,
            'date' => 0,
            'muted' => false,
            'can_self_unmute' => true,
            'muted_by_you' => false,
            'self' => false,
            'just_joined' => false,
            'video_joined' => $video !== null,
            'volume_by_admin' => false,
        ] + ($video === null ? [] : ['video' => $video]);
    }

    public function testTheRetransmissionSourceOfEachVideoSourceIsKept(): void
    {
        $participant = GroupCallParticipant::fromRaw(self::raw(false, [
            'endpoint' => 'e',
            'source_groups' => [
                ['semantics' => 'SIM', 'sources' => [10, 20]],
                ['semantics' => 'FID', 'sources' => [10, 11]],
                ['semantics' => 'FID', 'sources' => [20, -21]],
            ],
        ]), 1);
        $this->assertSame([10, 20], $participant->videoSources);
        $this->assertSame([10 => 11, 20 => -21], $participant->retransmissionSources);

        // A min update without the video keeps the known one, with its retransmission sources.
        $min = GroupCallParticipant::fromRaw(self::raw(true, null), 1, $participant);
        $this->assertSame([10, 20], $min->videoSources);
        $this->assertSame([10 => 11, 20 => -21], $min->retransmissionSources);

        // The video stopping takes them away.
        $stopped = GroupCallParticipant::fromRaw(self::raw(false, ['endpoint' => 'e', 'paused' => true, 'source_groups' => [['semantics' => 'FID', 'sources' => [10, 11]]]]), 1, $participant);
        $this->assertSame([], $stopped->videoSources);
        $this->assertSame([], $stopped->retransmissionSources);
    }
}
