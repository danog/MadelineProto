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

use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\MTProto;
use JsonSerializable;

/**
 * Broadcast progress.
 */
final class Progress extends Update implements JsonSerializable
{
    /**
     * Completion percentage.
     */
    public readonly int $percent;
    /** @internal */
    public function __construct(
        MTProto $API,
        /** Broadcast ID */
        public readonly int $broadcastId,
        /** Broadcast status */
        public readonly Status $status,
        /** Pending number of peers */
        public readonly int $pendingCount,
        /** Successful number of peers */
        public readonly int $successCount,
        /** Failed number of peers */
        public readonly int $failCount,
    ) {
        parent::__construct($API);
        $this->percent = $status === Status::FINISHED
            ? 100
            : ($pendingCount ? (int) (($successCount+$failCount)*100/($successCount+$failCount+$pendingCount)) : 0);
    }
    public function __toString(): string
    {
        return "Progress for {$this->broadcastId}: {$this->percent}%, status {$this->status->value}, sent to {$this->successCount} peers, failed sending to {$this->failCount} peers, {$this->pendingCount} peers left.";
    }
}
