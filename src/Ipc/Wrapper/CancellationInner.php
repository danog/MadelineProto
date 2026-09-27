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
use Closure;
use danog\MadelineProto\Ipc\ClientAbstract;
use Revolt\EventLoop;
use Throwable;

/**
 * @internal
 */
final class CancellationInner extends Obj implements AmpCancellation
{
    private ?CancelledException $exception = null;
    /**
     * @var array<string, Closure(CancelledException)>
     */
    private array $callbacks = [];
    private string $nextId = 'a';

    /**
     * Constructor.
     *
     * @param array<string, int> $methods
     */
    public function __construct(ClientAbstract $wrapper, array $methods)
    {
        parent::__construct($wrapper, $methods);
        // Cancellation methods must never suspend: amp calls them between fetching a fiber's suspension
        // and suspending it (Future::await(), FutureIterator::consume()).
        // So instead of querying the remote cancellation, we get notified by a single long-running wait call.
        EventLoop::queue(function (): void {
            try {
                $this->__call('wait');
            } catch (CancelledException $e) {
                $this->exception = $e;
                $callbacks = $this->callbacks;
                $this->callbacks = [];
                foreach ($callbacks as $callback) {
                    EventLoop::queue(static function () use ($callback, $e): void {
                        $callback($e);
                    });
                }
            } catch (Throwable) {
            }
        });
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
    #[\Override]
    public function subscribe(\Closure $callback): string
    {
        $id = $this->nextId;
        $this->nextId = str_increment($id);
        $exception = $this->exception;
        if ($exception !== null) {
            EventLoop::queue(static function () use ($callback, $exception): void {
                $callback($exception);
            });
        } else {
            $this->callbacks[$id] = $callback;
        }
        return $id;
    }

    /**
     * Unsubscribes a previously registered handler.
     *
     * The handler will no longer be called as long as this method isn't invoked from a subscribed callback.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function unsubscribe(string $id): void
    {
        unset($this->callbacks[$id]);
    }

    /**
     * Returns whether cancellation has been requested yet.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isRequested(): bool
    {
        return $this->exception !== null;
    }

    /**
     * Throws the `CancelledException` if cancellation has been requested, otherwise does nothing.
     *
     * @throws CancelledException
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function throwIfRequested(): void
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }
    }
}
