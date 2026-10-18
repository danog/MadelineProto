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

namespace danog\MadelineProto\Parallel;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Context\Internal\ExitResult;
use Amp\Sync\ChannelException;
use danog\MadelineProto\Parallel\Internal\SocketChannel;

use function Amp\Parallel\Context\flattenArgument;

/**
 * A context running in a request to the webserver, started by {@see WebContextFactory}.
 *
 * The request can't be killed: closing the context disconnects it, which ends the task runner of a worker.
 *
 * @template-covariant TResult
 * @template-covariant TReceive
 * @template TSend
 * @implements Context<TResult, TReceive, TSend>
 */
final class WebContext implements Context
{
    use ForbidCloning;
    use ForbidSerialization;

    /** @var SocketChannel<TReceive, TSend> */
    private readonly SocketChannel $channel;

    /** @var DeferredFuture<ExitResult<TResult>> */
    private readonly DeferredFuture $result;

    /**
     * @internal
     */
    public function __construct(ChannelledSocket $socket)
    {
        $result = $this->result = new DeferredFuture();
        $this->channel = new SocketChannel($socket, static function (mixed $message) use ($result): void {
            if (!$message instanceof ExitResult || $result->isComplete()) {
                throw new ChannelException('Unexpected message from the context: '.flattenArgument($message));
            }
            $result->complete($message);
        });
        // The exit result is read before the socket is closed
        $this->channel->onClose(static function () use ($result): void {
            if (!$result->isComplete()) {
                $result->error(new ContextException(
                    'The context exited without a result, potentially due to a fatal error, calling exit, or the webserver ending the request'
                ));
            }
        });
        $result->getFuture()->ignore();
    }

    public function __destruct()
    {
        $this->close();
    }

    #[\Override]
    public function receive(?Cancellation $cancellation = null): mixed
    {
        try {
            return $this->channel->receive($cancellation);
        } catch (ChannelException $e) {
            throw new ContextException(
                'The context stopped responding during receive, potentially due to a fatal error or calling exit',
                previous: $e,
            );
        }
    }

    #[\Override]
    public function send(mixed $data): void
    {
        try {
            $this->channel->send($data);
        } catch (ChannelException $e) {
            throw new ContextException(
                'The context stopped responding during send, potentially due to a fatal error or calling exit',
                previous: $e,
            );
        }
    }

    /**
     * @return TResult
     * @throws ContextException
     */
    #[\Override]
    public function join(?Cancellation $cancellation = null): mixed
    {
        return $this->result->getFuture()->await($cancellation)->getResult();
    }

    #[\Override]
    public function close(): void
    {
        $this->channel->close();
    }

    #[\Override]
    public function isClosed(): bool
    {
        return $this->channel->isClosed();
    }

    #[\Override]
    public function onClose(\Closure $onClose): void
    {
        $this->channel->onClose($onClose);
    }
}
