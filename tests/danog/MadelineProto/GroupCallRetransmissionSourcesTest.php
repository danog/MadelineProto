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
use danog\MadelineProto\GroupCall\GroupCallController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The retransmission (RTX) SSRCs of a participant's videos, kept by the controller (not by the participant objects
 * the event handlers get) from the FID groups of its groupCallParticipantVideo.
 */
final class GroupCallRetransmissionSourcesTest extends TestCase
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

    /**
     * @param array<int, int> $previous
     * @return array<int, int>
     */
    private static function sources(array $raw, GroupCallParticipant $parsed, array $previous = []): array
    {
        return (new ReflectionMethod(GroupCallController::class, 'retransmissionSources'))->invoke(null, $raw, $parsed, $previous);
    }

    public function testTheRetransmissionSourceOfEachVideoSourceIsKept(): void
    {
        $raw = self::raw(false, [
            'endpoint' => 'e',
            'source_groups' => [
                ['semantics' => 'SIM', 'sources' => [10, 20]],
                ['semantics' => 'FID', 'sources' => [10, 11]],
                ['semantics' => 'FID', 'sources' => [20, -21]],
            ],
        ]);
        $participant = GroupCallParticipant::fromRaw($raw, 1);
        $this->assertSame([10, 20], $participant->videoSources);
        $rtx = self::sources($raw, $participant);
        $this->assertSame([10 => 11, 20 => -21], $rtx);

        // A min update without the video keeps the known one, with its retransmission sources.
        $raw = self::raw(true, null);
        $min = GroupCallParticipant::fromRaw($raw, 1, $participant);
        $this->assertSame([10, 20], $min->videoSources);
        $this->assertSame([10 => 11, 20 => -21], self::sources($raw, $min, $rtx));

        // The video pausing takes them away.
        $raw = self::raw(false, ['endpoint' => 'e', 'paused' => true, 'source_groups' => [['semantics' => 'FID', 'sources' => [10, 11]]]]);
        $stopped = GroupCallParticipant::fromRaw($raw, 1, $participant);
        $this->assertSame([], $stopped->videoSources);
        $this->assertSame([], self::sources($raw, $stopped, $rtx));
    }
}
