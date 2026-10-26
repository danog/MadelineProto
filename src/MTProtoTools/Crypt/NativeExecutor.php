<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Future;
use danog\MadelineProto\SecurityException;
use FFI;
use FFI\CData;
use Revolt\EventLoop;
use RuntimeException;
use Throwable;

/**
 * One native worker shared by all crypto adapters in this process.
 * @internal
 * @psalm-type CryptoOutput = array{data: string, iv: string, sessionId: string, messageId: string, seqNo: int}
 */
final class NativeExecutor
{
    use ForbidCloning;
    use ForbidSerialization;

    public const ENCRYPT = 1;
    public const DECRYPT = 2;
    public const DECODE_V2 = 4;
    private static self|false|null $instance = null;
    /** @var array<int, DeferredFuture<CryptoOutput>> */
    private array $pending = [];
    private ?DeferredFuture $space = null;
    /** @var resource */
    private $stream;
    private string $watcher;
    private bool $closed = false;
    private bool $failed = false;

    /** Called by the MTProto owner at startup, never by an IPC-only client. */
    public static function initialize(int $capacity = 128, int $byteLimit = 134217728, int $windowUs = 5000, int $fixedWidth = 0): void
    {
        if (self::$instance !== null) {
            return;
        }
        self::$instance = false;
        if (!\extension_loaded('ffi') || PHP_OS_FAMILY === 'Windows') {
            return;
        }
        $name = PHP_OS_FAMILY === 'Darwin' ? 'libmadeline-ige.dylib' : 'libmadeline-ige.so';
        $build = \dirname(__DIR__, 3).'/native/';
        $header = @file_get_contents($build.'crypto.h');
        if ($header === false) {
            return;
        }
        $declarations = str_replace('unsigned char', 'char', preg_replace('/^#.*$/m', '', $header));
        foreach (array_filter([getenv('MADELINE_IGE_LIBRARY'), $build.'build/'.$name, $build.'build/Release/'.$name, $name]) as $library) {
            try {
                $ffi = FFI::cdef($declarations, $library);
                if ($ffi->madeline_crypto_abi() !== 1) {
                    continue;
                }
                $operations = (int) $ffi->madeline_crypto_operations();
                if (($operations & 3) !== 3) {
                    continue;
                }
                /** @var CData $pool */
                $pool = $ffi->madeline_crypto_create($capacity, $byteLimit, $windowUs, $fixedWidth);
                if (FFI::isNull($pool)) {
                    continue;
                }
                try {
                    self::$instance = new self($ffi, $pool, $operations);
                } catch (Throwable $e) {
                    $ffi->madeline_crypto_destroy($pool);
                    throw $e;
                }
                register_shutdown_function(self::$instance->close(...));
                return;
            } catch (Throwable) {
                // Restricted FFI, incompatible helper or unavailable worker: retain synchronous backends.
            }
        }
    }

    public static function get(int $operation): ?self
    {
        $instance = self::$instance;
        return $instance instanceof self && !$instance->closed && !$instance->failed && ($instance->operations & $operation) === $operation
            ? $instance : null;
    }

    private function __construct(private readonly FFI $ffi, private readonly CData $pool, private readonly int $operations)
    {
        $fd = (int) $ffi->madeline_crypto_notification_fd($pool);
        if ($fd < 0) {
            throw new RuntimeException('Could not open crypto notification descriptor');
        }
        $stream = @fopen('php://fd/'.$fd, 'r');
        if (!$stream) {
            throw new RuntimeException('Could not open crypto notification stream');
        }
        $this->stream = $stream;
        stream_set_blocking($stream, false);
        stream_set_read_buffer($stream, 0);
        try {
            $this->watcher = EventLoop::onReadable($stream, $this->collect(...));
            EventLoop::unreference($this->watcher);
        } catch (Throwable $e) {
            fclose($stream);
            throw $e;
        }
    }

    /** Access the loaded ABI for adapter-owned resources. @internal */
    public function bind(\Closure $factory): mixed
    {
        if ($this->closed || $this->failed) {
            throw new RuntimeException('Native executor is closed');
        }
        return $factory($this->ffi, $this->pool);
    }

    /**
     * Retry submission on backpressure. The adapter owns argument validation and marshalling.
     * Accepted work is not cancelled by its waiter; C owns all submitted buffers.
     * @param \Closure(FFI, CData): int $enqueue
     * @return Future<CryptoOutput>
     */
    public function submit(\Closure $enqueue, ?Cancellation $cancellation = null): Future
    {
        while (true) {
            $cancellation?->throwIfRequested();
            if ($this->closed || $this->failed) {
                throw new RuntimeException('Native executor is closed');
            }
            $id = $enqueue($this->ffi, $this->pool);
            if ($id > 0) break;
            if ($id < 0) throw new RuntimeException('Native submission failed: '.$id);
            $this->space ??= new DeferredFuture;
            $this->space->getFuture()->await($cancellation);
        }
        /** @var DeferredFuture<CryptoOutput> $deferred */
        $deferred = new DeferredFuture;
        $this->pending[$id] = $deferred;
        EventLoop::reference($this->watcher);
        return $deferred->getFuture()->ignore();
    }

    private function collect(): void
    {
        while (($bytes = fread($this->stream, 8192)) !== false && $bytes !== '') {
        }
        $buffer = $this->ffi->new('CryptoResult');
        $address = FFI::addr($buffer);
        /** @var object{handle: CData, id: int, data: CData, size: non-negative-int, iv: CData, session_id: CData, message_id: CData, seq_no: int, status: int} $result */
        $result = $buffer;
        while ($this->ffi->madeline_crypto_collect($this->pool, $address)) {
            $deferred = $this->pending[$result->id];
            unset($this->pending[$result->id]);
            try {
                if ($result->status <= -10) {
                    throw new SecurityException('Invalid encrypted transport message: '.$result->status);
                }
                if ($result->status !== 1 || $this->failed) {
                    $this->failed = true;
                    throw new RuntimeException('Native crypto operation failed: '.$result->status);
                }
                $value = [
                    'data' => FFI::string($result->data, $result->size),
                    'iv' => FFI::string($result->iv, 32),
                    'sessionId' => FFI::string($result->session_id, 8),
                    'messageId' => FFI::string($result->message_id, 8),
                    'seqNo' => $result->seq_no,
                ];
            } catch (Throwable $e) {
                $deferred->error($e);
                continue;
            } finally {
                $this->ffi->madeline_crypto_release($this->pool, $result->handle);
            }
            $deferred->complete($value);
        }
        if ($this->space) {
            $space = $this->space;
            $this->space = null;
            $space->complete();
        }
        if (!$this->pending) {
            EventLoop::unreference($this->watcher);
        }
        if ($this->failed && !$this->closed) {
            $this->close();
        }
    }

    /** @return array{width: int, locked: bool}|null Available when the executor is idle. */
    public function getPolicy(int $operation, int $bytes): ?array
    {
        if ($this->closed || $bytes < 0) {
            return null;
        }
        $width = $this->ffi->new('unsigned[1]');
        $locked = $this->ffi->new('int[1]');
        if (!$this->ffi->madeline_crypto_policy($this->pool, $operation, $bytes, $width, $locked)) {
            return null;
        }
        return ['width' => (int) $width[0], 'locked' => (bool) $locked[0]];
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->ffi->madeline_crypto_stop($this->pool);
        $this->collect();
        EventLoop::cancel($this->watcher);
        $stream = $this->stream;
        fclose($stream);
        $this->ffi->madeline_crypto_destroy($this->pool);
    }
}
