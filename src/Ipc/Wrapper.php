<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc;

use Amp\ByteStream\ReadableStream as ByteStreamReadableStream;
use Amp\ByteStream\WritableStream as ByteStreamWritableStream;
use Amp\Cancellation;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Parallel\Context\Internal\ExitFailure;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\Ipc\Wrapper\Cancellation as WrapperCancellation;
use danog\MadelineProto\Ipc\Wrapper\FileCallback;
use danog\MadelineProto\Ipc\Wrapper\Obj;
use danog\MadelineProto\Ipc\Wrapper\ReadableStream;
use danog\MadelineProto\Ipc\Wrapper\SeekableReadableStream;
use danog\MadelineProto\Ipc\Wrapper\SeekableWritableStream;
use danog\MadelineProto\Ipc\Wrapper\WritableStream;
use danog\MadelineProto\Logger;
use danog\MadelineProto\SessionPaths;
use Revolt\EventLoop;
use Throwable;

use function Amp\Ipc\connect;

/**
 * Callback payload wrapper.
 *
 * @psalm-suppress InternalMethod
 * @psalm-suppress InternalClass
 *
 * @internal
 */
final class Wrapper extends ClientAbstract
{
    /**
     * Payload data.
     */
    private mixed $data;
    /**
     * Callbacks.
     *
     * @var array<callable>
     */
    private array $callbacks = [];
    /**
     * Callbacks IDs.
     *
     * @var list<int|list{class-string<Obj>, array<string, int>}>
     */
    private array $callbackIds = [];
    /**
     * Callback ID.
     */
    private int $id = 0;
    /**
     * Remote socket ID.
     */
    private int $remoteId = 0;
    /**
     * Constructor.
     *
     * @param mixed        $data Payload data
     */
    public static function create(mixed &$data, SessionPaths $session, Logger $logger): self
    {
        $instance = new self;
        $instance->data = &$data;
        $instance->logger = $logger;
        $instance->run = false;

        $logger->logger('Connecting to callback IPC server...');
        $instance->server = connect($session->getIpcCallbackPath());
        $logger->logger('Connected to callback IPC server!');

        $instance->remoteId = $instance->server->receive();
        $logger->logger("Got ID {$instance->remoteId} from callback IPC server!");

        EventLoop::queue($instance->receiverLoop(...));
        return $instance;
    }
    /**
     * Serialization function.
     */
    public function __sleep(): array
    {
        return ['data', 'callbackIds', 'remoteId'];
    }
    /**
     * Wrap a certain callback object.
     *
     * @param mixed           $callback    Callback to wrap
     * @param bool            $wrapObjects Whether to wrap object methods, too
     * @param-out int $callback Callback ID
     */
    public function wrap(mixed &$callback, bool $wrapObjects = true): void
    {
        if (\is_object($callback) && $wrapObjects) {
            $ids = [];
            foreach (\get_class_methods($callback) as $method) {
                $id = $this->id++;
                $this->callbacks[$id] = [$callback, $method];
                $ids[$method] = $id;
            }
            $class = Obj::class;
            if ($callback instanceof ByteStreamWritableStream) {
                $class = \method_exists($callback, 'seek') ? WritableStream::class : SeekableWritableStream::class;
            } elseif ($callback instanceof ByteStreamReadableStream) {
                $class = \method_exists($callback, 'seek') ? ReadableStream::class : SeekableReadableStream::class;
            } elseif ($callback instanceof FileCallbackInterface) {
                $class = FileCallback::class;
            } elseif ($callback instanceof Cancellation) {
                $class = WrapperCancellation::class;
            }
            $callback = [$class, $ids]; // Will be re-filled later
            $this->callbackIds[] = &$callback;
        } elseif (\is_callable($callback)) {
            $id = $this->id++;
            $this->callbacks[$id] = self::copy($callback);
            $callback = $id;
            $this->callbackIds[] = &$callback;
        }
    }
    /**
     * Get copy of data.
     */
    private static function copy($data)
    {
        return $data;
    }
    /**
     * Receiver loop.
     */
    private function receiverLoop(): void
    {
        $id = 0;
        $payload = null;
        try {
            while ($payload = $this->server->receive()) {
                EventLoop::queue($this->clientRequest(...), $id++, $payload);
            }
        } finally {
            $this->server->disconnect();
        }
    }

    /**
     * Handle client request.
     *
     * @param integer          $id      Request ID
     * @param array            $payload Payload
     */
    private function clientRequest(int $id, array $payload): void
    {
        try {
            $result = $this->callbacks[$payload[0]](...$payload[1]);
        } catch (Throwable $e) {
            $this->logger->logger("Got error while calling reverse IPC method: $e", Logger::ERROR);
            $result = new ExitFailure($e);
        }
        try {
            $this->server->send([$id, $result]);
        } catch (Throwable $e) {
            $this->logger->logger("Got error while trying to send result of reverse method: $e", Logger::ERROR);
            try {
                $this->server->send([$id, new ExitFailure($e)]);
            } catch (Throwable $e) {
                $this->logger->logger("Got error while trying to send error of error of reverse method: $e", Logger::ERROR);
            }
        }
    }
    /**
     * Get remote socket ID.
     *
     * @internal
     */
    public function getRemoteId(): int
    {
        return $this->remoteId;
    }

    /**
     * Set socket and unwrap data.
     *
     * @param ChannelledSocket $server Socket.
     * @internal
     */
    public function unwrap(ChannelledSocket $server)
    {
        $this->server = $server;
        EventLoop::queue($this->loopInternal(...));

        foreach ($this->callbackIds as &$id) {
            if (\is_int($id)) {
                $id = fn (...$args) => $this->__call($id, $args);
            } else {
                [$class, $ids] = $id;
                $id = new $class($this, $ids);
            }
        }
        return $this->data;
    }
}
