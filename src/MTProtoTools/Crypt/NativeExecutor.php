<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use danog\MadelineProto\Native\Crypto;
use Revolt\EventLoop;
use RuntimeException;
use Throwable;

/** Lifecycle of the Rust/php-tokio bridge, initialized only by the MTProto owner. @internal */
final class NativeExecutor
{
    use ForbidCloning;
    use ForbidSerialization;

    /** Exception code of a native operation that was not performed: another backend can perform it. */
    public const UNAVAILABLE = 1;
    /** Exception code of a message that failed MTProto validation. */
    public const INVALID = 2;

    private static self|false|null $instance = null;
    /** @var resource */
    private $stream;
    private string $watcher;
    private bool $closed = false;
    private int $active = 0;
    private readonly int $pid;

    public static function initialize(int $capacity = 128, int $byteLimit = 134217728): void
    {
        if (self::$instance !== null) {
            return;
        }
        self::$instance = false;
        if (!\extension_loaded('madeline_crypto') || !class_exists(Crypto::class, false)
            || Crypto::version() !== '0.3.0') {
            return;
        }
        try {
            $fd = Crypto::init($capacity, $byteLimit);
            self::$instance = new self($fd);
            register_shutdown_function(self::$instance->close(...));
        } catch (Throwable) {
            Crypto::shutdown();
        }
    }

    public static function get(): ?self
    {
        $instance = self::$instance;
        return $instance instanceof self && !$instance->closed
            && $instance->pid === getmypid() && Crypto::available()
            ? $instance : null;
    }

    private function __construct(int $fd)
    {
        $this->pid = getmypid();
        // A failing fopen throws (warnings throw): initialize() falls back to PHP.
        $stream = fopen('php://fd/'.$fd, 'r');
        if (!$stream) {
            throw new RuntimeException('Could not open crypto notification stream');
        }
        $this->stream = $stream;
        stream_set_blocking($stream, false);
        stream_set_read_buffer($stream, 0);
        $this->watcher = EventLoop::onReadable($stream, static fn () => Crypto::wakeup());
        EventLoop::unreference($this->watcher);
    }

    /**
     * Keep Revolt alive while php-tokio suspends the caller. No PHP job/result queue.
     * @template T of array
     * @param \Closure(): T $operation
     * @return T
     */
    public function run(\Closure $operation): array
    {
        if ($this->closed || $this->pid !== getmypid()) {
            throw new RuntimeException('Native executor is closed', self::UNAVAILABLE);
        }
        $this->active++;
        EventLoop::reference($this->watcher);
        try {
            return $operation();
        } finally {
            if (!--$this->active) {
                EventLoop::unreference($this->watcher);
            }
        }
    }

    /**
     * Whether a native operation failed with the given error code.
     *
     * Any other error (such as an exception thrown into the suspended caller by the event loop) must propagate.
     */
    public static function failedWith(\Throwable $e, int $code): bool
    {
        return ($e::class === \Exception::class || $e::class === RuntimeException::class) && $e->getCode() === $code;
    }

    public function close(): void
    {
        if ($this->closed || $this->pid !== getmypid()) {
            return;
        }
        $this->closed = true;
        // Finishes the accepted work, rejects the admission waiters and resumes the callers
        // without running the event loop (this is a shutdown function): if it runs again,
        // they continue, and later operations use the PHP backends.
        try {
            Crypto::shutdown();
        } finally {
            EventLoop::cancel($this->watcher);
            fclose($this->stream);
        }
    }
}
