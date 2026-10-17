<?php

declare(strict_types=1);

/**
 * Referenced timeout cancellation.
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

namespace danog\MadelineProto;

use Amp\Cancellation;
use Amp\DeferredCancellation;
use Amp\TimeoutException;
use Revolt\EventLoop;

/**
 * Timeout cancellation whose timer keeps the event loop alive.
 *
 * Unlike Amp\TimeoutCancellation, the timer is *referenced*: some callers
 * (i.e. the IPC connection polling loop) await futures that can only complete
 * through this timeout, so an unreferenced timer would let the event loop
 * terminate underneath them.
 *
 * The timer is cancelled when the cancellation is destroyed, so it does not
 * keep the event loop alive after the awaited operation has completed.
 *
 * @internal
 */
final class ReferencedTimeoutCancellation implements Cancellation
{
    private readonly DeferredCancellation $deferred;
    private readonly string $watcher;

    public function __construct(float $timeout, string $message = "Operation timed out")
    {
        $deferred = $this->deferred = new DeferredCancellation;
        $this->watcher = EventLoop::delay(
            $timeout,
            static fn () => $deferred->cancel(new TimeoutException($message)),
        );
    }

    public function __destruct()
    {
        EventLoop::cancel($this->watcher);
    }

    #[\Override]
    public function subscribe(\Closure $callback): string
    {
        return $this->deferred->getCancellation()->subscribe($callback);
    }

    #[\Override]
    public function unsubscribe(string $id): void
    {
        $this->deferred->getCancellation()->unsubscribe($id);
    }

    #[\Override]
    public function isRequested(): bool
    {
        return $this->deferred->isCancelled();
    }

    #[\Override]
    public function throwIfRequested(): void
    {
        $this->deferred->getCancellation()->throwIfRequested();
    }
}
