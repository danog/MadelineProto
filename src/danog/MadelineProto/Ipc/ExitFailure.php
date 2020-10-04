<?php

namespace danog\MadelineProto\Ipc;

use danog\MadelineProto\RPCErrorException;

use function Amp\Parallel\Sync\flattenThrowableBacktrace;

final class ExitFailure
{
    /** @var string */
    private $type;

    /** @var string */
    private $message;

    /** @var int|string */
    private $code;

    /** @var string[] */
    private $trace;

    /** @var string */
    private $tlTrace;

    /** @var self|null */
    private $previous;

    /** @var string|null */
    private $localized;

    public function __construct(\Throwable $exception)
    {
        $this->type = \get_class($exception);
        $this->message = $exception->getMessage();
        $this->code = $exception->getCode();
        $this->trace = flattenThrowableBacktrace($exception);
        if (\method_exists($exception, 'getTLTrace')) {
            $this->tlTrace = $exception->getTLTrace();
        }

        if ($exception instanceof RPCErrorException) {
            $this->localized = $exception->getLocalization();
        }

        if ($previous = $exception->getPrevious()) {
            $this->previous = new self($previous);
        }
    }

    public function getException(): object
    {
        $previous = $this->previous ? $this->previous->getException() : null;

        $exception = new $this->type($this->message, $this->code, $previous);
        if ($this->tlTrace) {
            $exception->setTLTrace($this->tlTrace);
        }
        if ($this->localized) {
            $exception->setLocalization($this->localized);
        }
        return $exception;
    }
}
