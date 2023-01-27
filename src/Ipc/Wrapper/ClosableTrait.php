<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use AssertionError;

trait ClosableTrait
{
    /**
     * Closes the resource, marking it as unusable.
     * Whether pending operations are aborted or not is implementation dependent.
     */
    public function close(): void
    {
        $this->__call('close');
    }

    /**
     * Returns whether this resource has been closed.
     *
     * @return bool {@code true} if closed, otherwise {@code false}
     */
    public function isClosed(): bool
    {
        return $this->__call('isClosed');
    }

    /**
     * Registers a callback that is invoked when this resource is closed.
     *
     * @param \Closure():void $onClose
     */
    public function onClose(\Closure $onClose): void
    {
        throw new AssertionError("Not implemented");
    }
}
