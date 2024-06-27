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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
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
    public function __construct(
        private readonly AmpCancellation $cancellation
    ) {
    }

    /**
     * @var array<string, DeferredFuture>
     */
    private array $handlers = [];
    private string $id = 'a';
    public function getId(): string
    {
        return $this->id++;
    }
    public function wait(string $id): void
    {
        $this->handlers[$id] = $deferred = new DeferredFuture;
        $id = $this->cancellation->subscribe(function (CancelledException $e) use ($deferred, &$id): void {
            unset($this->handlers[$id]);
            $deferred->error($e);
        });
        $deferred->getFuture()->await();
    }

    /**
     * Unsubscribes a previously registered handler.
     *
     * The handler will no longer be called as long as this method isn't invoked from a subscribed callback.
     */
    public function unsubscribe(string $id): void
    {
        if (isset($this->handlers[$id])) {
            $this->handlers[$id]->complete();
            unset($this->handlers[$id]);
        }
    }

    /**
     * Returns whether cancellation has been requested yet.
     */
    public function isRequested(): bool
    {
        return $this->cancellation->isRequested();
    }

    /**
     * Throws the `CancelledException` if cancellation has been requested, otherwise does nothing.
     *
     * @throws CancelledException
     */
    public function throwIfRequested(): void
    {
        $this->cancellation->throwIfRequested();
    }
}
