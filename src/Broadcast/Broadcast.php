<?php

declare(strict_types=1);

/**
 * Broadcast module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Broadcast;

use danog\MadelineProto\Broadcast\Action\ActionForward;
use danog\MadelineProto\Broadcast\Action\ActionSend;

/**
 * Manages broadcasts.
 *
 * @internal
 */
trait Broadcast
{
    /** @var array<int, InternalState> */
    private array $broadcasts = [];
    public function broadcastMessages(array $messages): int
    {
        return $this->broadcastCustom(new ActionSend($this, $messages));
    }
    /**
     * @param list<int> $ids
     */
    public function broadcastForwardMessages(mixed $from_peer, array $ids, bool $drop_author = false): int
    {
        return $this->broadcastCustom(new ActionForward($this, $this->getID($from_peer), $ids, $drop_author));
    }

    public function broadcastCustom(Action $action): int
    {
        $id = \count($this->broadcasts);
        $this->broadcasts[$id] = new InternalState($id, $this, $action);
        return $id;
    }
    /**
     * Get the progress of a currently running broadcast.
     *
     * Will return null if the broadcast doesn't exist, has already completed or was cancelled.
     *
     * Use updateBroadcastProgress updates to get real-time progress status without polling.
     *
     * @param integer $id Broadcast ID
     */
    public function getBroadcastProgress(int $id): ?Progress
    {
        return $this->broadcasts[$id]?->getProgress();
    }
    /**
     * Cancel a running broadcast.
     *
     * @param integer $id Broadcast ID
     */
    public function cancelBroadcast(int $id): void
    {
        $this->broadcasts[$id]?->cancel();
    }

    /** @internal */
    public function cleanupBroadcast(int $id): void
    {
        unset($this->broadcasts[$id]);
    }
}
