<?php

declare(strict_types=1);

/**
 * HTTP proxy stream wrapper.
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

namespace danog\MadelineProto\Stream\Proxy;

use Amp\Cancellation;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;
use Webmozart\Assert\Assert;

/**
 * HTTP proxy stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements RawProxyStreamInterface<array{address: string, port: int, username?: string, password?: string}>
 * @implements BufferedProxyStreamInterface<array{address: string, port: int, username?: string, password?: string}>
 */
final class HttpProxy implements RawProxyStreamInterface, BufferedProxyStreamInterface
{
    /**
     * Stream.
     */
    protected RawStreamInterface&BufferedStreamInterface $stream;
    private $extra;
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $ctx = $ctx->clone();
        $uri = $ctx->getUri();
        $secure = $ctx->isSecure();
        if ($secure) {
            $ctx->setSocketContext($ctx->getSocketContext()->withTlsContext(new ClientTlsContext($uri->getHost())));
        }
        $ctx->setUri('tcp://'.$this->extra['address'].':'.$this->extra['port'])->secure(false);
        $this->stream = $ctx->getStream();
        Assert::isInstanceOf($this->stream, BufferedStreamInterface::class);
        Assert::isInstanceOf($this->stream, RawStreamInterface::class);
        $address = $uri->getHost();
        $port = $uri->getPort();
        try {
            if (\strlen(inet_pton($address) ?: '') === 16) {
                $address = '['.$address.']';
            }
        } catch (Exception) {
        }
        $this->stream->write("CONNECT {$address}:{$port} HTTP/1.1\r\nHost: {$address}:{$port}\r\nAccept: */*\r\n".$this->getProxyAuthHeader()."Connection: keep-Alive\r\n\r\n");
        $buffer = $this->stream->getReadBuffer($l);
        $headers = '';
        $was_crlf = false;
        while (true) {
            $piece = $buffer->bufferRead(2);
            $headers .= $piece;
            if ($piece === "\n\r") {
                // Assume end of headers with \r\n\r\n
                $headers .= $buffer->bufferRead(1);
                break;
            }
            if ($was_crlf && $piece === "\r\n") {
                break;
            }
            $was_crlf = $piece === "\r\n";
        }
        $headers = explode("\r\n", $headers);
        [$protocol, $code, $description] = explode(' ', $headers[0], 3);
        [$protocol, $protocol_version] = explode('/', $protocol);
        if ($protocol !== 'HTTP') {
            throw new Exception('Wrong protocol');
        }
        $code = (int) $code;
        unset($headers[0]);
        if (array_pop($headers).array_pop($headers) !== '') {
            throw new Exception('Wrong last header');
        }
        foreach ($headers as $key => $current_header) {
            unset($headers[$key]);
            $current_header = explode(':', $current_header, 2);
            $headers[strtolower($current_header[0])] = trim($current_header[1]);
        }
        if ($code !== 200) {
            $read = '';
            if (isset($headers['content-length'])) {
                $length = (int) $headers['content-length'];
                if ($length < 0) {
                    Logger::log("Trying to read negative amount {$headers['content-length']}");
                } else {
                    $read = $buffer->bufferRead($length);
                }
            }
            Logger::log(trim($read));
            throw new Exception($description, $code);
        }
        if (isset($headers['content-length'])) {
            $length = (int) $headers['content-length'];
            $read = $buffer->bufferRead($length);
        }
        if ($secure) {
            $this->getSocket()->setupTls();
        }
        Logger::log('Connected to '.$address.':'.$port.' via http');
        if (\strlen($header)) {
            ($this->stream->getWriteBuffer(\strlen($header)))->bufferWrite($header);
        }
    }
    /**
     * Async close.
     */
    public function disconnect(): void
    {
        $this->stream->disconnect();
    }
    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     */
    public function getWriteBuffer(int $length, string $append = ''): \danog\MadelineProto\Stream\WriteBufferInterface
    {
        return $this->stream->getWriteBuffer($length, $append);
    }
    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     */
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        return $this->stream->getReadBuffer($length);
    }
    public function read(?Cancellation $cancellation = null): ?string
    {
        return $this->stream->read($cancellation);
    }
    public function write(string $data): void
    {
        $this->stream->write($data);
    }
    private function getProxyAuthHeader(): string
    {
        if (!isset($this->extra['username']) || !isset($this->extra['password'])) {
            return '';
        }
        return 'Proxy-Authorization: Basic '.base64_encode($this->extra['username'].':'.$this->extra['password'])."\r\n";
    }
    /**
     * Sets proxy data.
     */
    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }
    /**
     * {@inheritdoc}
     */
    public function getSocket(): Socket
    {
        return $this->stream->getSocket();
    }
    /**
     * {@inheritDoc}
     */
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
    }
    public static function getName(): string
    {
        return self::class;
    }
}
