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

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\Cancellation as AmpCancellation;
use Amp\CancelledException;
use Amp\DeferredFuture;

/**
 * @internal
 */
final class WrappedCancellation
{
    /**
     * @var array<int, DeferredFuture>
     */
    private array $waiting = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly AmpCancellation $cancellation
    ) {
    }

    /**
     * Waits until cancellation is requested, throwing the `CancelledException`.
     *
     * Returns normally if the IPC connection is closed first.
     */
    public function wait(): void
    {
        $deferred = new DeferredFuture;
        $this->waiting[spl_object_id($deferred)] = $deferred;
        $id = $this->cancellation->subscribe(static function (CancelledException $e) use ($deferred): void {
            if (!$deferred->isComplete()) {
                $deferred->error($e);
            }
        });
        try {
            $deferred->getFuture()->await();
        } finally {
            unset($this->waiting[spl_object_id($deferred)]);
            $this->cancellation->unsubscribe($id);
        }
    }

    /**
     * Stops all pending waits, called when the IPC connection is closed.
     *
     * @internal
     */
    public function disconnect(): void
    {
        $waiting = $this->waiting;
        $this->waiting = [];
        foreach ($waiting as $deferred) {
            // Might have been failed by the cancellation, with the waiting fiber not resumed yet.
            if (!$deferred->isComplete()) {
                $deferred->complete();
            }
        }
    }
}
