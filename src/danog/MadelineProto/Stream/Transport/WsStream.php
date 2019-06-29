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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Transport;

use Amp\Http\Rfc7230;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Socket\ConnectException;
use Amp\Websocket\Client\ConnectionException;
use Amp\Websocket\Client\Handshake;
use Amp\Websocket\Client\Internal\ClientSocket;
use Amp\Websocket\Client\Rfc6455Connection;
use Amp\Websocket\Rfc6455Client;
use Amp\Websocket\Rfc7692CompressionFactory;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;
use function Amp\Websocket\generateKey;
use function Amp\Websocket\validateAcceptForKey;

/**
 * Websocket stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class WsStream implements RawStreamInterface
{
    use RawStream;

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
        $this->dc = $ctx->getIntDc();
        $stream = yield $ctx->getStream();
        $resource = $stream->getStream()->getResource();

        $this->compressionFactory = new Rfc7692CompressionFactory();

        $handshake = new Handshake(str_replace('tcp://', $ctx->isSecure() ? 'ws://' : 'wss://', $ctx->getStringUri()));

        $key = generateKey();
        yield $stream->write($this->generateRequest($handshake, $key));

        $buffer = '';
        while (($chunk = yield $stream->read()) !== null) {
            $buffer .= $chunk;
            if ($position = \strpos($buffer, "\r\n\r\n")) {
                $headerBuffer = \substr($buffer, 0, $position + 4);
                $buffer = \substr($buffer, $position + 4);
                $headers = $this->handleResponse($headerBuffer, $key);

                $client = new Rfc6455Client(
                    new ClientSocket($resource, $buffer),
                    $handshake->getOptions(),
                    true
                );
                $this->stream = new Rfc6455Connection($client, $headers);
                //$this->stream = new Rfc6455Connection($this->stream, $headers, $buffer);
                break;
            }
        }

        if (!$this->stream) {
            throw new ConnectionException('Failed to read response from server');
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
        } catch (Exception $e) {
        }
    }

    public function readAsync(): \Generator
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
        } catch (Exception $e) {
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

    private function generateRequest(Handshake $handshake, string $key): string
    {
        $uri = $handshake->getUri();
        $headers = $handshake->getHeaders();
        $headers['host'] = [$uri->getAuthority()];
        $headers['connection'] = ['Upgrade'];
        $headers['upgrade'] = ['websocket'];
        $headers['sec-websocket-version'] = ['13'];
        $headers['sec-websocket-key'] = [$key];
        if ($handshake->getOptions()->isCompressionEnabled()) {
            $headers['sec-websocket-extensions'] = [$this->compressionFactory->createRequestHeader()];
        }
        if (($path = $uri->getPath()) === '') {
            $path = '/';
        }
        if (($query = $uri->getQuery()) !== '') {
            $path .= '?'.$query;
        }

        return \sprintf("GET %s HTTP/1.1\r\n%s\r\n", $path, Rfc7230::formatHeaders($headers));
    }

    private function handleResponse(string $headerBuffer, string $key): array
    {
        if (\substr($headerBuffer, -4) !== "\r\n\r\n") {
            throw new ConnectException('Invalid header provided');
        }
        $position = \strpos($headerBuffer, "\r\n");
        $startLine = \substr($headerBuffer, 0, $position);
        if (!\preg_match("/^HTTP\/(1\.[01]) (\d{3}) ([^\x01-\x08\x10-\x19]*)$/i", $startLine, $matches)) {
            throw new ConnectException('Invalid response start line: '.$startLine);
        }
        $version = $matches[1];
        $status = (int) $matches[2];
        $reason = $matches[3];

        if ($version !== '1.1' || $status !== Status::SWITCHING_PROTOCOLS) {
            throw new ConnectionException(
                \sprintf('Did not receive switching protocols response: %d %s on DC %d', $status, $reason, $this->dc),
                $status
            );
        }
        $headerBuffer = \substr($headerBuffer, $position + 2, -2);
        $headers = Rfc7230::parseHeaders($headerBuffer);
        $upgrade = $headers['upgrade'][0] ?? '';
        if (\strtolower($upgrade) !== 'websocket') {
            throw new ConnectionException('Missing "Upgrade: websocket" header');
        }
        $connection = $headers['connection'][0] ?? '';
        if (!\in_array('upgrade', \array_map('trim', \array_map('strtolower', \explode(',', $connection))), true)) {
            throw new ConnectionException('Missing "Connection: upgrade" header');
        }
        $secWebsocketAccept = $headers['sec-websocket-accept'][0] ?? '';
        if (!validateAcceptForKey($secWebsocketAccept, $key)) {
            throw new ConnectionException('Invalid "Sec-WebSocket-Accept" header');
        }

        return $headers;
    }

    final protected function createCompressionContext(array $headers): ?Websocket\CompressionContext
    {
        $extensions = $headers['sec-websocket-extensions'][0] ?? '';
        $extensions = \array_map('trim', \explode(',', $extensions));
        foreach ($extensions as $extension) {
            if ($compressionContext = $this->compressionFactory->fromServerHeader($extension)) {
                return $compressionContext;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Amp\Socket\Socket
     */
    public function getSocket(): \Amp\Socket\Socket
    {
        return $this->stream->getSocket();
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}
