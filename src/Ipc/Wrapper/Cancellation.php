<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\Cancellation as AmpCancellation;
use Amp\CancelledException;

/**
 * @internal
 */
final class Cancellation extends Obj implements AmpCancellation
{
    /**
     * Subscribes a new handler to be invoked on a cancellation request.
     *
     * This handler might be invoked immediately in case the cancellation has already been requested. Any unhandled
     * exceptions will be thrown into the event loop.
     *
     * @param \Closure(CancelledException) $callback Callback to be invoked on a cancellation request. Will receive a
     * `CancelledException` as first argument that may be used to fail the operation.
     *
     * @return string Identifier that can be used to cancel the subscription.
     */
    public function subscribe(\Closure $callback): string
    {
        return $this->__call('unsubscribe', [$callback]);
    }

    /**
     * Unsubscribes a previously registered handler.
     *
     * The handler will no longer be called as long as this method isn't invoked from a subscribed callback.
     */
    public function unsubscribe(string $id): void
    {
        $this->__call('unsubscribe', [$id]);
    }

    /**
     * Returns whether cancellation has been requested yet.
     */
    public function isRequested(): bool
    {
        return $this->__call('isRequested');
    }

    /**
     * Throws the `CancelledException` if cancellation has been requested, otherwise does nothing.
     *
     * @throws CancelledException
     */
    public function throwIfRequested(): void
    {
        $this->__call('throwIfRequested');
    }
}
