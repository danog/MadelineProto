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
use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Future;
use Amp\Ipc\Sync\ChannelException as IpcChannelException;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Ipc\Sync\SerializationException as IpcSerializationException;
use Amp\Serialization\SerializationException;
use Amp\Sync\Channel;
use Amp\Sync\ChannelException;
use Amp\Sync\LocalMutex;
use Closure;
use Throwable;

use function Amp\async;
use function Amp\Parallel\Context\flattenArgument;

/**
 * An amphp channel over a danog/ipc socket, used for both channels of a {@see \danog\MadelineProto\Parallel\WebContext}
 * (its IPC channel and its result channel), at both ends.
 *
 * Values are wrapped in a ChannelValue: a ChannelledSocket returns null once closed, and a null value must still
 * reach the other end (ContextWorker sends one to stop the task runner).
 *
 * The socket is only read by receive(), like StreamChannel: an idle channel has no read pending, which would keep the
 * event loop running (an idle worker would keep its script from ending). A ChannelledSocket can't be read with a
 * cancellation: a receive() that is cancelled leaves its read to the next one, unreferenced meanwhile.
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

    /** @var Future<mixed>|null The read of a receive() that was cancelled. */
    private ?Future $reading = null;
    private readonly LocalMutex $readMutex;
    private readonly DeferredFuture $onClose;

    public function __construct(
        private readonly ChannelledSocket $socket,
    ) {
        $this->readMutex = new LocalMutex();
        $this->onClose = new DeferredFuture();
    }

    public function __destruct()
    {
        $this->close();
    }

    #[\Override]
    public function receive(?Cancellation $cancellation = null): mixed
    {
        $lock = $this->readMutex->acquire();
        try {
            if ($this->isClosed()) {
                throw new ChannelException('The channel was closed');
            }
            $socket = $this->socket;
            $this->reading ??= async(static fn (): mixed => $socket->receive());
            // Errors are thrown to the receive() awaiting it, if any
            $this->reading->ignore();
            // Awaited: the read keeps the event loop running, like any other
            $socket->reference();
            try {
                $message = $this->reading->await($cancellation);
            } catch (CancelledException $e) {
                // Nobody awaits the read anymore: it mustn't keep the event loop running until the other end writes
                $socket->unreference();
                throw $e;
            } catch (Throwable $e) {
                $this->reading = null;
                $this->close();
                throw new ChannelException('Receiving from the channel failed', previous: $e);
            }
            $this->reading = null;
        } finally {
            $lock->release();
        }
        if ($message === null) {
            $this->close();
            throw new ChannelException('The channel closed while waiting to receive the next value');
        }
        if (!$message instanceof ChannelValue) {
            $this->close();
            throw new ChannelException('Unexpected message: '.flattenArgument($message));
        }
        return $message->value;
    }

    #[\Override]
    public function send(mixed $data): void
    {
        if ($this->isClosed()) {
            throw new ChannelException('The channel was closed');
        }
        try {
            $this->socket->send(new ChannelValue($data));
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
