<?php

declare(strict_types=1);

/**
 * Websocket stream wrapper.
 *
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

namespace danog\MadelineProto\Stream\Transport;

use Amp\Cancellation;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Socket;
use Amp\Websocket\Client\Connector;
use Amp\Websocket\Client\Rfc6455ConnectionFactory;
use Amp\Websocket\Client\Rfc6455Connector;
use Amp\Websocket\Client\WebsocketConnection;
use Amp\Websocket\Client\WebsocketConnector;
use Amp\Websocket\Client\WebsocketHandshake;
use Amp\Websocket\ClosedException;
use Amp\Websocket\WebsocketMessage;
use AssertionError;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;
use Throwable;

/**
 * Websocket stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements ProxyStreamInterface<?WebsocketConnector>
 */
class WsStream implements RawStreamInterface, ProxyStreamInterface
{
    /**
     * Websocket stream.
     */
    private WebsocketConnection $stream;
    /**
     * Websocket message.
     *
     */
    private ?WebsocketMessage $message = null;
    /**
     * Websocket Connector.
     *
     */
    private WebsocketConnector $connector;
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $uri = $ctx->getStringUri();
        $uri = str_replace('tcp://', $ctx->isSecure() ? 'wss://' : 'ws://', $uri);
        $handshake = new WebsocketHandshake($uri, ['Sec-WebSocket-Protocol' => 'binary']);
        $this->stream = ($this->connector ?? new Rfc6455Connector(new Rfc6455ConnectionFactory(), HttpClientBuilder::buildDefault()))->connect($handshake, $ctx->getCancellation());
        if (\strlen($header)) {
            $this->write($header);
        }
    }
    /**
     * Async close.
     */
    public function disconnect(): void
    {
        try {
            $this->stream->close();
        } catch (Throwable $e) {
        }
    }
    public function read(?Cancellation $token = null): ?string
    {
        try {
            if (!$this->message) {
                $this->message = $this->stream->receive($token);
                if (!$this->message) {
                    return null;
                }
                $data = $this->message->buffer($token);
                $this->message = null;
            }
        } catch (Throwable $e) {
            if ($e instanceof ClosedException && $e->getReason() !== 'Client closed the underlying TCP connection') {
                throw $e;
            }
            return null;
        }
        return $data;
    }
    /**
     * Async write.
     *
     * @param string $data Data to write
     */
    public function write(string $data): void
    {
        $this->stream->sendBinary($data);
    }
    /**
     * {@inheritdoc}
     */
    public function getSocket(): Socket
    {
        throw new AssertionError("Unreachable!");
    }
    public function setExtra($extra): void
    {
        if ($extra instanceof WebsocketConnector) {
            $this->connector = $extra;
        }
    }
    public static function getName(): string
    {
        return self::class;
    }
}
