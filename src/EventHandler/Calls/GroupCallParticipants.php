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

use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\MTProto;

/**
 * Participants of a group call joined, left, or changed state (muted, video, volume, raised hand...),
 * from [updateGroupCallParticipants](https://core.telegram.org/constructor/updateGroupCallParticipants).
 */
final class GroupCallParticipants extends Update
{
    /** ID of the group call. */
    public readonly int $callId;
    /**
     * The participants that changed, by their bot API peer ID.
     *
     * @var array<int, AbstractGroupCallParticipant>
     */
    public readonly array $participants;
    /**
     * Bot API peer IDs of the participants that left the call.
     *
     * @var list<int>
     */
    public readonly array $left;

    /**
     * @internal
     *
     * @param array{call: array{id: int}, participants: list<array<array-key, mixed>>} $rawUpdate
     *
     * @psalm-mutation-free
     */
    public function __construct(MTProto $API, array $rawUpdate)
    {
        parent::__construct($API);
        $this->callId = $rawUpdate['call']['id'];
        $liveStory = $API->isGroupCallLiveStory($this->callId);
        $participants = [];
        $left = [];
        foreach ($rawUpdate['participants'] as $participant) {
            $peerId = $API->getIdInternal($participant['peer']);
            if ($peerId === null) {
                continue;
            }
            if ($participant['left']) {
                $left[] = $peerId;
                continue;
            }
            $participants[$peerId] = $liveStory
                ? LiveStoryParticipant::fromRaw($participant, $peerId)
                : GroupCallParticipant::fromRaw($participant, $peerId);
        }
        $this->participants = $participants;
        $this->left = $left;
    }
}
