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
use danog\MadelineProto\Ipc\ClientAbstract;

/**
 * @internal
 */
final class Cancellation implements AmpCancellation
{
    /**
     * @var array<string, true> $handlers
     */
    private array $handlers = [];
    private CancellationInner $inner;
    /**
     * Constructor.
     *
     * @param array<string, int> $methods
     */
    public function __construct(ClientAbstract $wrapper, array $methods)
    {
        $this->inner = new CancellationInner($wrapper, $methods);
    }

    /**
     * Subscribes a new handler to be invoked on a cancellation request.
     *
     * This handler might be invoked immediately in case the cancellation has already been requested. Any unhandled
     * exceptions will be thrown into the event loop.
     *
     * @param \Closure(CancelledException) $callback Callback to be invoked on a cancellation request. Will receive a
     *                                               `CancelledException` as first argument that may be used to fail the operation.
     *
     * @return string Identifier that can be used to cancel the subscription.
     */
    public function subscribe(\Closure $callback): string
    {
        $id = $this->inner->subscribe($callback);
        $this->handlers[$id] = true;
        return $id;
    }

    /**
     * Unsubscribes a previously registered handler.
     *
     * The handler will no longer be called as long as this method isn't invoked from a subscribed callback.
     */
    public function unsubscribe(string $id): void
    {
        unset($this->handlers[$id]);
        $this->inner->unsubscribe($id);
    }

    /**
     * Returns whether cancellation has been requested yet.
     */
    public function isRequested(): bool
    {
        return $this->inner->isRequested();
    }

    /**
     * Throws the `CancelledException` if cancellation has been requested, otherwise does nothing.
     *
     * @throws CancelledException
     */
    public function throwIfRequested(): void
    {
        $this->inner->throwIfRequested();
    }

    public function __destruct()
    {
        foreach ($this->handlers as $handler => $_) {
            $this->inner->unsubscribe($handler);
        }
    }
}
