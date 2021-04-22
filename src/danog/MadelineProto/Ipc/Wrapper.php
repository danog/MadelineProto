<?php

namespace danog\MadelineProto\Ipc;

use Amp\ByteStream\InputStream as ByteStreamInputStream;
use Amp\ByteStream\OutputStream as ByteStreamOutputStream;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Parallel\Sync\ExitFailure;
use Amp\Promise;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\Ipc\Wrapper\FileCallback;
use danog\MadelineProto\Ipc\Wrapper\InputStream;
use danog\MadelineProto\Ipc\Wrapper\Obj;
use danog\MadelineProto\Ipc\Wrapper\OutputStream;
use danog\MadelineProto\Ipc\Wrapper\SeekableInputStream;
use danog\MadelineProto\Ipc\Wrapper\SeekableOutputStream;
use danog\MadelineProto\Logger;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Tools;

use function Amp\Ipc\connect;

/**
 * Callback payload wrapper.
 */
class Wrapper extends ClientAbstract
{
    /**
     * Payload data.
     *
     * @var mixed
     */
    private $data;
    /**
     * Callbacks.
     *
     * @var callable[]
     */
    private array $callbacks = [];
    /**
     * Callbacks IDs.
     *
     * @var (int|array{0: class-string<Obj>, array<string, int>})[]
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
     * @param SessionPaths $ipc  IPC URI
     *
     * @return \Generator
     * @psalm-return \Generator<int, Promise<ChannelledSocket>|Promise<mixed>, mixed, Wrapper>
     */
    public static function create(&$data, SessionPaths $session, Logger $logger): \Generator
    {
        $instance = new self;
        $instance->data = &$data;
        $instance->logger = $logger;
        $instance->run = false;

        $logger->logger("Connecting to callback IPC server...");
        $instance->server = yield connect($session->getIpcCallbackPath());
        $logger->logger("Connected to callback IPC server!");

        $instance->remoteId = yield $instance->server->receive();
        $logger->logger("Got ID {$instance->remoteId} from callback IPC server!");

        Tools::callFork($instance->receiverLoop());
        return $instance;
    }
    /**
     * Serialization function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['data', 'callbackIds', 'remoteId'];
    }
    /**
     * Wrap a certain callback object.
     *
     * @param object|callable $callback    Callback to wrap
     * @param bool            $wrapObjects Whether to wrap object methods, too
     *
     * @param-out int $callback Callback ID
     *
     * @return void
     */
    public function wrap(&$callback, bool $wrapObjects = true): void
    {
        if (\is_object($callback) && $wrapObjects) {
            $ids = [];
            foreach (\get_class_methods($callback) as $method) {
                $id = $this->id++;
                $this->callbacks[$id] = [$callback, $method];
                $ids[$method] = $id;
            }
            $class = Obj::class;
            if ($callback instanceof ByteStreamInputStream) {
                $class = \method_exists($callback, 'seek') ? InputStream::class : SeekableInputStream::class;
            } elseif ($callback instanceof ByteStreamOutputStream) {
                $class = \method_exists($callback, 'seek') ? OutputStream::class : SeekableOutputStream::class;
            } elseif ($callback instanceof FileCallbackInterface) {
                $class = FileCallback::class;
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
     *
     * @param mixed $data
     * @return mixed
     */
    private static function copy($data)
    {
        return $data;
    }
    /**
     * Receiver loop.
     *
     * @return \Generator
     */
    private function receiverLoop(): \Generator
    {
        $id = 0;
        $payload = null;
        try {
            while ($payload = yield $this->server->receive()) {
                Tools::callFork($this->clientRequest($id++, $payload));
            }
        } finally {
            yield $this->server->disconnect();
        }
    }

    /**
     * Handle client request.
     *
     * @param integer          $id      Request ID
     * @param array            $payload Payload
     *
     * @return \Generator
     */
    private function clientRequest(int $id, $payload): \Generator
    {
        try {
            $result = $this->callbacks[$payload[0]](...$payload[1]);
            $result = $result instanceof \Generator ? yield from $result : yield $result;
        } catch (\Throwable $e) {
            $this->logger->logger("Got error while calling reverse IPC method: $e", Logger::ERROR);
            $result = new ExitFailure($e);
        }
        try {
            yield $this->server->send([$id, $result]);
        } catch (\Throwable $e) {
            $this->logger->logger("Got error while trying to send result of reverse method: $e", Logger::ERROR);
            try {
                yield $this->server->send([$id, new ExitFailure($e)]);
            } catch (\Throwable $e) {
                $this->logger->logger("Got error while trying to send error of error of reverse method: $e", Logger::ERROR);
            }
        }
    }
    /**
     * Get remote socket ID.
     *
     * @internal
     *
     * @return int
     */
    public function getRemoteId(): int
    {
        return $this->remoteId;
    }

    /**
     * Set socket and unwrap data.
     *
     * @param ChannelledSocket $server Socket.
     *
     * @internal
     *
     * @return mixed
     */
    public function unwrap(ChannelledSocket $server)
    {
        $this->server = $server;
        Tools::callFork($this->loopInternal());

        foreach ($this->callbackIds as &$id) {
            if (\is_int($id)) {
                $id = fn (...$args): \Generator => $this->__call($id, $args);
            } else {
                [$class, $ids] = $id;
                $id = new $class($this, $ids);
            }
        }
        return $this->data;
    }
}
