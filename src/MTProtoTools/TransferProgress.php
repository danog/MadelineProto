<?php

declare(strict_types=1);

/**
 * Transfer progress.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2026 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

/**
 * Reports the progress of an upload or download to a progress callback (percent, speed in mbps, seconds elapsed),
 * each time a part completes.
 *
 * @internal
 */
final class TransferProgress
{
    /** When the transfer started (hrtime, in nanoseconds). */
    private readonly int $start;
    private int $parts = 0;
    private int $bytes = 0;

    /**
     * @param \Closure(float, float, float): void $cb         Progress callback
     * @param int                                 $totalParts Total number of parts
     */
    public function __construct(private readonly \Closure $cb, private readonly int $totalParts)
    {
        $this->start = hrtime(true);
    }

    /**
     * Report the completion of a part.
     *
     * @param int $bytes Bytes transferred for this part (0 for parts that were already transferred before a resume).
     */
    public function partDone(int $bytes): void
    {
        $this->parts++;
        $this->bytes += $bytes;
        ($this->cb)($this->parts * 100 / $this->totalParts, $this->getSpeed(), $this->getTime());
    }

    /** Seconds elapsed since the transfer started. */
    public function getTime(): float
    {
        return (hrtime(true) - $this->start) / 1e9;
    }

    /** Average speed of the bytes transferred so far, in mbps. */
    public function getSpeed(): float
    {
        $time = $this->getTime();
        return $time > 0 ? $this->bytes * 8 / $time / 1_000_000 : 0.0;
    }
}
