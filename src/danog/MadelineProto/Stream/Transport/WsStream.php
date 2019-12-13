<?php
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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Transport;

use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use Amp\Websocket\Client\Connector;
use Amp\Websocket\Client\Handshake;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

use function Amp\Websocket\Client\connector;

/**
 * Websocket stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class WsStream implements RawStreamInterface, ProxyStreamInterface
{
    use RawStream;

    /**
     * Websocket stream.
     *
     * @var Connection
     */
    private $stream;
    /**
     * Websocket message.
     *
     * @var Message
     */
    private $message;
    /**
     * Websocket Connector.
     *
     * @var Connector
     */
    private $connector;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectGenerator(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $this->dc = $ctx->getIntDc();

        $handshake = new Handshake(\str_replace('tcp://', $ctx->isSecure() ? 'wss://' : 'ws://', $ctx->getStringUri()));

        $this->stream = yield ($this->connector ?? connector())->connect($handshake, $ctx->getCancellationToken());

        if (\strlen($header)) {
            yield $this->write($header);
        }
    }

    /**
     * Async close.
     */
    public function disconnect()
    {
        try {
            $this->stream->close();
        } catch (\Throwable $e) {
        }
    }

    public function readGenerator(): \Generator
    {
        try {
            if (!$this->message || ($data = yield $this->message->buffer()) === null) {
                $this->message = yield $this->stream->receive();
                if (!$this->message) {
                    return null;
                }
                $data = yield $this->message->buffer();
                $this->message = null;
            }
        } catch (\Throwable $e) {
            if ($e->getReason() !== 'Client closed the underlying TCP connection') {
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
     *
     * @return Promise
     */
    public function write(string $data): \Amp\Promise
    {
        return $this->stream->sendBinary($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Amp\Socket\Socket
     */
    public function getSocket(): EncryptableSocket
    {
        return $this->stream->getSocket();
    }
    public function setExtra($extra)
    {
        if ($extra instanceof Connector) {
            $this->connector = $extra;
        }
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}
