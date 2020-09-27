<?php

namespace danog\MadelineProto\Ipc;

/**
 * IPC state class.
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
     *
     * @param integer    $startupId
     * @param \Throwable $exception
     */
    public function __construct(int $startupId, \Throwable $exception = null)
    {
        $this->startupTime = \microtime(true);
        $this->startupId = $startupId;
        $this->exception = $exception ? new ExitFailure($exception) : null;
    }

    /**
     * Get startup time.
     *
     * @return float
     */
    public function getStartupTime(): float
    {
        return $this->startupTime;
    }

    /**
     * Get startup ID.
     *
     * @return int
     */
    public function getStartupId(): int
    {
        return $this->startupId;
    }

    /**
     * Get exception.
     *
     * @return ?\Throwable
     */
    public function getException(): ?\Throwable
    {
        return $this->exception ? $this->exception->getException() : null;
    }
}
