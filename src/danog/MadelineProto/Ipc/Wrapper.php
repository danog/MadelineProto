<?php

namespace danog\MadelineProto\Ipc;

use Amp\ByteStream\InputStream as ByteStreamInputStream;
use Amp\ByteStream\OutputStream as ByteStreamOutputStream;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Parallel\Sync\ExitFailure;
use danog\MadelineProto\Ipc\Wrapper\InputStream;
use danog\MadelineProto\Ipc\Wrapper\Obj;
use danog\MadelineProto\Ipc\Wrapper\OutputStream;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Tools;

use function Amp\Ipc\connect;

/**
 * Callback payload wrapper.
 */
class Wrapper extends Client
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
     * Logger instance.
     */
    private Logger $logger;
    /**
     * Constructor.
     *
     * @param mixed  $data Payload data
     * @param string $ipc  IPC URI
     *
     * @return \Generator
     */
    public static function create(&$data, string $ipc, Logger $logger): \Generator
    {
        $instance = new self;
        $instance->data = &$data;
        $instance->server = yield connect($ipc);
        $instance->remoteId = yield $instance->server->receive();
        $instance->logger = $logger;
        Tools::callFork($instance->receiverLoop());
        return $instance;
    }
    private function __construct()
    {
    }
    /**
     * Serialization function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['data', 'callbackIds'];
    }
    /**
     * Wrap a certain callback object.
     *
     * @param object|callable $callback Object to wrap
     *
     * @param-out int $callback Callback ID
     *
     * @return void
     */
    public function wrap(&$callback): void
    {
        if (\is_object($callback)) {
            $ids = [];
            foreach (\get_class_methods($callback) as $method) {
                $id = $this->id++;
                $this->callbacks[$id] = [$callback, $method];
                $ids[$method] = $id;
            }
            $callback = $ids;
            $this->callbackIds[] = &$callback;
        } else {
            $id = $this->id++;
            $this->callbacks[$id] = self::copy($callback);
            $class = Obj::class;
            if ($callback instanceof ByteStreamInputStream) {
                $class = InputStream::class;
            } else if ($callback instanceof ByteStreamOutputStream) {
                $class = OutputStream::class;
            }
            if ($class !== Obj::class && method_exists($callback, 'seek')) {
                $class = "Seekable$class";
            }
            $callback = [$class, $id]; // Will be re-filled later
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
