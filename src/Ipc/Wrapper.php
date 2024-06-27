<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Ipc;

use Amp\ByteStream\ReadableStream as ByteStreamReadableStream;
use Amp\ByteStream\WritableStream as ByteStreamWritableStream;
use Amp\Cancellation;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\FileCallback as MadelineProtoFileCallback;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\Ipc\Wrapper\Cancellation as WrapperCancellation;
use danog\MadelineProto\Ipc\Wrapper\FileCallback;
use danog\MadelineProto\Ipc\Wrapper\Obj;
use danog\MadelineProto\Ipc\Wrapper\ReadableStream;
use danog\MadelineProto\Ipc\Wrapper\SeekableReadableStream;
use danog\MadelineProto\Ipc\Wrapper\SeekableWritableStream;
use danog\MadelineProto\Ipc\Wrapper\WrappedCancellation;
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
     * @param mixed $data Payload data
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
     * @param mixed $callback    Callback to wrap
     * @param bool  $wrapObjects Whether to wrap object methods, too
     */
    public function wrap(mixed &$callback, bool $wrapObjects = true): void
    {
        if (\is_object($callback) && $wrapObjects) {
            if ($callback instanceof Cancellation) {
                $callback = new WrappedCancellation($callback);
            }
            if ($callback instanceof FileCallbackInterface) {
                $file = $callback->getFile();
                if ($file instanceof ByteStreamReadableStream) {
                    $this->wrap($file, true);
                    $callback = new MadelineProtoFileCallback($file, $callback);
                }
            }
            $ids = [];
            foreach (get_class_methods($callback) as $method) {
                $id = $this->id++;
                $this->callbacks[$id] = [$callback, $method];
                $ids[$method] = $id;
            }
            $class = null;
            if ($callback instanceof ByteStreamReadableStream) {
                $class = method_exists($callback, 'seek') ? SeekableReadableStream::class : ReadableStream::class;
            } elseif ($callback instanceof ByteStreamWritableStream) {
                $class = method_exists($callback, 'seek') ? SeekableWritableStream::class : WritableStream::class;
            } elseif ($callback instanceof FileCallbackInterface) {
                $class = FileCallback::class;
            } elseif ($callback instanceof WrappedCancellation) {
                $class = WrapperCancellation::class;
            }
            if (!$class) {
                return;
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
            EventLoop::queue($this->server->disconnect(...), "exiting receiverLoop");
        }
    }

    /**
     * Handle client request.
     *
     * @param integer $id      Request ID
     * @param array   $payload Payload
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
            $this->logger->logger("Got error while trying to send result of reverse method {$payload[0]}: $e", Logger::ERROR);
            try {
                $this->server->send([$id, new ExitFailure($e)]);
            } catch (Throwable $e) {
                $this->logger->logger("Got error while trying to send error of error of reverse method {$payload[0]}: $e", Logger::ERROR);
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
