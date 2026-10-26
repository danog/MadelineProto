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
use FFI;
use FFI\CData;
use Revolt\EventLoop;
use RuntimeException;
use Throwable;

/** One native worker shared by all crypto adapters in this process. @internal */
final class NativeCrypto
{
    use ForbidCloning;
    use ForbidSerialization;

    public const ENCRYPT = 1;
    public const DECRYPT = 2;
    public const DECODE_V2 = 4;
    private static self|false|null $instance = null;
    /** @var array<int, DeferredFuture<array>> */
    private array $pending = [];
    private ?DeferredFuture $space = null;
    /** @var resource */
    private $stream;
    private string $watcher;
    private bool $closed = false;

    /** Called by the MTProto owner at startup, never by an IPC-only client. */
    public static function initialize(): void
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
        $declarations = str_replace('unsigned char', 'char', preg_replace('/^#.*$/m', '', file_get_contents($build.'crypto.h')));
        foreach (array_filter([getenv('MADELINE_IGE_LIBRARY'), $build.'build/'.$name, $name]) as $library) {
            try {
                $ffi = FFI::cdef($declarations, $library);
                if ($ffi->madeline_crypto_abi() !== 1) {
                    continue;
                }
                $operations = $ffi->madeline_crypto_operations();
                if (($operations & 3) !== 3) {
                    continue;
                }
                $pool = $ffi->madeline_crypto_create(128, 128 * 1024 * 1024, 5000);
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
        return $instance instanceof self && !$instance->closed && ($instance->operations & $operation) === $operation
            ? $instance : null;
    }

    private function __construct(private readonly FFI $ffi, private readonly CData $pool, private readonly int $operations)
    {
        $fd = $ffi->madeline_crypto_notification_fd($pool);
        if ($fd < 0) {
            throw new RuntimeException('Could not open crypto notification descriptor');
        }
        $stream = fopen('php://fd/'.$fd, 'r');
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

    /**
     * Input is copied by C before returning. Accepted work is not cancelled by its waiter.
     * @return Future<array{data: string, iv: string, sessionId: string, messageId: string, seqNo: int}>
     */
    public function submit(int $operation, string $data, string $key, string $iv, ?Cancellation $cancellation = null): Future
    {
        if (\strlen($key) !== 32 || \strlen($iv) !== 32 || \strlen($data) % 16) {
            throw new \ValueError('AES-256-IGE requires a 32-byte key and IV and complete blocks');
        }
        while (true) {
            $cancellation?->throwIfRequested();
            if ($this->closed) {
                throw new RuntimeException('Native crypto executor is closed');
            }
            $id = $this->ffi->madeline_crypto_submit($this->pool, $operation, $data, \strlen($data), $key, $iv);
            if ($id > 0) {
                break;
            }
            if ($id < 0) {
                throw new RuntimeException('Native crypto submission failed: '.$id);
            }
            $this->space ??= new DeferredFuture;
            $this->space->getFuture()->await($cancellation);
        }
        $deferred = new DeferredFuture;
        $this->pending[$id] = $deferred;
        EventLoop::reference($this->watcher);
        return $deferred->getFuture()->ignore();
    }

    private function collect(): void
    {
        while (($bytes = fread($this->stream, 8192)) !== false && $bytes !== '') {
        }
        $result = $this->ffi->new('CryptoResult');
        while ($this->ffi->madeline_crypto_collect($this->pool, FFI::addr($result))) {
            $deferred = $this->pending[$result->id];
            unset($this->pending[$result->id]);
            try {
                if ($result->status !== 1) {
                    throw new RuntimeException('Native crypto operation failed: '.$result->status);
                }
                $value = [
                    'data' => FFI::string($result->data, $result->size),
                    'iv' => FFI::string($result->iv, 32),
                    'sessionId' => FFI::string($result->session_id, 8),
                    'messageId' => FFI::string($result->message_id, 8),
                    'seqNo' => (int) $result->seq_no,
                ];
            } catch (Throwable $e) {
                $error = $e;
            } finally {
                $this->ffi->madeline_crypto_release($this->pool, $result->handle);
            }
            if (isset($error)) {
                $deferred->error($error);
                unset($error);
            } else {
                $deferred->complete($value);
            }
        }
        if ($this->space) {
            $space = $this->space;
            $this->space = null;
            $space->complete();
        }
        if (!$this->pending) {
            EventLoop::unreference($this->watcher);
        }
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
        fclose($this->stream);
        $this->ffi->madeline_crypto_destroy($this->pool);
    }
}
