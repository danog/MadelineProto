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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Parallel\Internal;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Ipc\Sync\ChannelException as IpcChannelException;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Ipc\Sync\SerializationException as IpcSerializationException;
use Amp\Parallel\Context\Internal\ContextMessage;
use Amp\Pipeline\ConcurrentIterator;
use Amp\Pipeline\Queue;
use Amp\Serialization\SerializationException;
use Amp\Sync\Channel;
use Amp\Sync\ChannelException;
use Closure;
use Revolt\EventLoop;
use Throwable;

use function Amp\Parallel\Context\flattenArgument;

/**
 * An amphp channel over a danog/ipc socket, used by both ends of a {@see \danog\MadelineProto\Parallel\WebContext}.
 *
 * Values are wrapped in a ContextMessage: a ChannelledSocket returns null once closed, and a null value must still
 * reach the other end (ContextWorker sends one to stop the task runner).
 * Anything else the peer sends (the exit result of the context) goes to the control message callback.
 *
 * A ChannelledSocket can't be read with a cancellation, so a fiber reads it into a queue that can.
 *
 * @internal
 *
 * @template-covariant TReceive
 * @template TSend
 * @implements Channel<TReceive, TSend>
 */
final class SocketChannel implements Channel
{
    use ForbidCloning;
    use ForbidSerialization;

    /** @var ConcurrentIterator<TReceive> */
    private readonly ConcurrentIterator $iterator;
    private readonly Queue $queue;
    private readonly DeferredFuture $onClose;

    /**
     * @param (Closure(mixed): void)|null $onControlMessage Invoked with what the peer sends besides values.
     */
    public function __construct(
        private readonly ChannelledSocket $socket,
        ?Closure $onControlMessage = null,
    ) {
        $queue = $this->queue = new Queue();
        $this->iterator = $queue->iterate();
        $onClose = $this->onClose = new DeferredFuture();

        // Static, so that dropping the channel (or the context holding it) closes it
        EventLoop::queue(static function () use ($socket, $queue, $onClose, $onControlMessage): void {
            try {
                while (($message = $socket->receive()) !== null) {
                    if ($message instanceof ContextMessage) {
                        // Not push(): an unread value must not stop the exit result from being read
                        $queue->pushAsync($message->getMessage())->ignore();
                    } elseif ($onControlMessage !== null) {
                        $onControlMessage($message);
                    } else {
                        throw new ChannelException('Unexpected message: '.flattenArgument($message));
                    }
                }
            } catch (Throwable $e) {
                if (!$queue->isComplete()) {
                    $queue->error(new ChannelException('Receiving from the channel failed', previous: $e));
                }
                $socket->disconnect();
            } finally {
                if (!$queue->isComplete()) {
                    $queue->complete();
                }
                if (!$onClose->isComplete()) {
                    $onClose->complete();
                }
            }
        });
    }

    public function __destruct()
    {
        $this->close();
    }

    #[\Override]
    public function receive(?Cancellation $cancellation = null): mixed
    {
        if (!$this->iterator->continue($cancellation)) {
            throw new ChannelException('The channel closed while waiting to receive the next value');
        }
        return $this->iterator->getValue();
    }

    #[\Override]
    public function send(mixed $data): void
    {
        $this->sendControl(new ContextMessage($data));
    }

    /**
     * Send a message that is not a value of the channel (an exit result, a key).
     */
    public function sendControl(mixed $message): void
    {
        if ($this->isClosed()) {
            throw new ChannelException('The channel was closed');
        }
        try {
            $this->socket->send($message);
        } catch (IpcSerializationException $e) {
            throw new SerializationException($e->getMessage(), previous: $e);
        } catch (IpcChannelException $e) {
            throw new ChannelException('Sending on the channel failed', previous: $e);
        }
    }

    #[\Override]
    public function close(): void
    {
        $this->socket->disconnect();
        if (!$this->queue->isComplete()) {
            $this->queue->complete();
        }
        if (!$this->onClose->isComplete()) {
            $this->onClose->complete();
        }
    }

    #[\Override]
    public function isClosed(): bool
    {
        return $this->onClose->isComplete();
    }

    #[\Override]
    public function onClose(Closure $onClose): void
    {
        $this->onClose->getFuture()->finally($onClose)->ignore();
    }
}
