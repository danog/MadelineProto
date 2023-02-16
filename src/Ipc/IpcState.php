<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc;

use Throwable;

/**
 * IPC state class.
 *
 * @internal
 */
final class IpcState
{
    /**
     * Startup time.
     */
    private float $startupTime;
    /**
     * Startup ID.
     */
    private int $startupId;
    /**
     * Exception.
     */
    private ?ExitFailure $exception;
    /**
     * Construct.
     */
    public function __construct(int $startupId, ?Throwable $exception = null)
    {
        $this->startupTime = \microtime(true);
        $this->startupId = $startupId;
        $this->exception = $exception ? new ExitFailure($exception) : null;
    }

    /**
     * Get startup time.
     */
    public function getStartupTime(): float
    {
        return $this->startupTime;
    }

    /**
     * Get startup ID.
     */
    public function getStartupId(): int
    {
        return $this->startupId;
    }

    /**
     * Get exception.
     */
    public function getException(): ?Throwable
    {
        return $this->exception ? $this->exception->getException() : null;
    }
}
