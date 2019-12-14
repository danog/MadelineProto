<?php
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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Proxy;

use Amp\Promise;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\EncryptableSocket;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawProxyStreamInterface;

use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * HTTP proxy stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HttpProxy implements RawProxyStreamInterface, BufferedProxyStreamInterface
{
    use RawStream;
    private $extra;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectGenerator(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $ctx = $ctx->getCtx();
        $uri = $ctx->getUri();
        $secure = $ctx->isSecure();

        if ($secure) {
            $ctx->setSocketContext($ctx->getSocketContext()->withTlsContext(new ClientTlsContext($uri->getHost())));
        }

        $ctx->setUri('tcp://'.$this->extra['address'].':'.$this->extra['port'])->secure(false);

        $this->stream = yield $ctx->getStream();
        $address = $uri->getHost();
        $port = $uri->getPort();

        try {
            if (\strlen(\inet_pton($address) === 16)) {
                $address = '['.$address.']';
            }
        } catch (\danog\MadelineProto\Exception $e) {
        }

        yield $this->stream->write("CONNECT $address:$port HTTP/1.1\r\nHost: $address:$port\r\nAccept: */*\r\n".$this->getProxyAuthHeader()."Connection: keep-Alive\r\n\r\n");

        $buffer = yield $this->stream->getReadBuffer($l);
        $headers = '';
        $was_crlf = false;
        while (true) {
            $piece = yield $buffer->bufferRead(2);
            $headers .= $piece;
            if ($piece === "\n\r") { // Assume end of headers with \r\n\r\n
                $headers .= yield $buffer->bufferRead(1);
                break;
            }
            if ($was_crlf && $piece === "\r\n") {
                break;
            }
            $was_crlf = $piece === "\r\n";
        }
        $headers = \explode("\r\n", $headers);

        list($protocol, $code, $description) = \explode(' ', $headers[0], 3);
        list($protocol, $protocol_version) = \explode('/', $protocol);
        if ($protocol !== 'HTTP') {
            throw new \danog\MadelineProto\Exception('Wrong protocol');
        }
        $code = (int) $code;
        unset($headers[0]);
        if (\array_pop($headers).\array_pop($headers) !== '') {
            throw new \danog\MadelineProto\Exception('Wrong last header');
        }
        foreach ($headers as $key => $current_header) {
            unset($headers[$key]);
            $current_header = \explode(':', $current_header, 2);
            $headers[\strtolower($current_header[0])] = \trim($current_header[1]);
        }

        $close = $protocol === 'HTTP/1.0';
        if (isset($headers['connection'])) {
            $close = \strtolower($headers['connection']) === 'close';
        }

        if ($code !== 200) {
            $read = '';
            if (isset($headers['content-length'])) {
                $read = yield $buffer->bufferRead((int) $headers['content-length']);
            }

            if ($close) {
                $this->disconnect();
                yield $this->connect($ctx);
            }

            \danog\MadelineProto\Logger::log(\trim($read));

            throw new \danog\MadelineProto\Exception($description, $code);
        }

        if ($close) {
            yield $this->stream->disconnect();
            yield $this->stream->connect($ctx);
        }
        if (isset($headers['content-length'])) {
            $length = (int) $headers['content-length'];
            $read = yield $buffer->bufferRead($length);
        }

        if ($secure) {
            yield $this->getSocket()->setupTls();
        }
        \danog\MadelineProto\Logger::log('Connected to '.$address.':'.$port.' via http');

        if (\strlen($header)) {
            yield (yield $this->stream->getWriteBuffer(\strlen($header)))->bufferWrite($header);
        }
    }

    /**
     * Async close.
     *
     * @return Promise
     */
    public function disconnect()
    {
        return $this->stream->disconnect();
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     *
     * @return Generator
     */
    public function getWriteBuffer(int $length, string $append = ''): Promise
    {
        return $this->stream->getWriteBuffer($length, $append);
    }

    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     * @return Generator
     */
    public function getReadBuffer(&$length): Promise
    {
        return $this->stream->getReadBuffer($length);
    }

    public function read(): Promise
    {
        return $this->stream->read();
    }

    public function write(string $data): Promise
    {
        return $this->stream->write($data);
    }

    private function getProxyAuthHeader()
    {
        if (!isset($this->extra['username']) || !isset($this->extra['password'])) {
            return '';
        }

        return 'Proxy-Authorization: Basic '.\base64_encode($this->extra['username'].':'.$this->extra['password'])."\r\n";
    }

    /**
     * Sets proxy data.
     *
     * @param array $extra Proxy data
     *
     * @return void
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    /**
     * {@inheritdoc}
     *
     * @return EncryptableSocket
     */
    public function getSocket(): EncryptableSocket
    {
        return $this->stream->getSocket();
    }

    /**
     * {@inheritDoc}
     *
     * @return RawStreamInterface
     */
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
    }
    public static function getName(): string
    {
        return __CLASS__;
    }
}
