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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Transport;

use Amp\Promise;
use Amp\Websocket\Handshake;
use Amp\Websocket\Options;
use Amp\Websocket\Rfc6455Connection;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Tools;

/**
 * Websocket stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class WsStream implements RawStreamInterface
{
    use RawStream;
    use Tools;

    private $stream;
    private $message;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectAsync(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $this->stream = yield $ctx->getStream();
        $handshake = new Handshake($ctx->getStringUri());

        yield $this->stream->write($handshake->generateRequest());

        $buffer = '';
        while (($chunk = yield $this->stream->read()) !== null) {
            $buffer .= $chunk;
            if ($position = \strpos($buffer, "\r\n\r\n")) {
                $headerBuffer = \substr($buffer, 0, $position + 4);
                $buffer = \substr($buffer, $position + 4);
                $headers = $handshake->decodeResponse($headerBuffer);
                $this->stream = new Rfc6455Connection($this->stream, $headers, $buffer, new Options());
                break;
            }
        }
        if (!$this->stream) {
            throw new WebSocketException('Failed to read response from server');
        }
        yield $this->write($header);
    }

    /**
     * Async close.
     */
    public function disconnect()
    {
        try {
            $this->stream->close();
        } catch (\Amp\Websocket\ClosedException $e) {
        }
    }

    public function readAsync(): \Generator
    {
        try {
            if (!$this->message || ($data = yield $this->message->read()) === null) {
                $this->message = yield $this->stream->receive();
                $data = yield $this->message->read();
            }
        } catch (\Amp\Websocket\ClosedException $e) {
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

    public static function getName(): string
    {
        return __CLASS__;
    }
}
