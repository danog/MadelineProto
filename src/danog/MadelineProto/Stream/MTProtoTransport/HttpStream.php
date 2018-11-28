<?php
/**
 * HTTP stream wrapper.
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

namespace danog\MadelineProto\Stream\MTProtoTransport;

use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Tools;

/**
 * HTTP stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HttpStream implements BufferedStreamInterface, MTProtoBufferInterface
{
    use BufferedStream;
    use Tools;
    private $stream;
    private $code;
    private $ctx;
    /**
     * URI of the HTTP API.
     *
     * @var \Amp\Uri\Uri
     */
    private $uri;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->ctx = $ctx->getCtx();
        $this->stream = yield $ctx->getStream();
        $this->uri = $ctx->getUri();
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
    public function getWriteBufferAsync(int $length): \Generator
    {
        $headers = 'POST '.$this->uri->getPath()." HTTP/1.1\r\nHost: ".$this->uri->getHost().':'.$this->uri->getPort()."\r\n"."Content-Type: application/x-www-form-urlencoded\r\nConnection: keep-alive\r\nKeep-Alive: timeout=100000, max=10000000\r\nContent-Length: ".$length."\r\n\r\n";
        $buffer = yield $this->stream->getWriteBuffer(strlen($headers) + $length);
        yield $buffer->bufferWrite($headers);

        return $buffer;
    }

    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     * @return Generator
     */
    public function getReadBufferAsync(&$length): \Generator
    {
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
        $headers = explode("\r\n", $headers);

        list($protocol, $code, $description) = explode(' ', $headers[0], 3);
        list($protocol, $protocol_version) = explode('/', $protocol);
        if ($protocol !== 'HTTP') {
            throw new \danog\MadelineProto\Exception('Wrong protocol');
        }
        $code = (int) $code;
        unset($headers[0]);
        if (array_pop($headers).array_pop($headers) !== '') {
            throw new \danog\MadelineProto\Exception('Wrong last header');
        }
        foreach ($headers as $key => $current_header) {
            unset($headers[$key]);
            $current_header = explode(':', $current_header, 2);
            $headers[strtolower($current_header[0])] = trim($current_header[1]);
        }

        $close = $protocol === 'HTTP/1.0';
        if (isset($headers['connection'])) {
            $close = strtolower($headers['connection']) === 'close';
        }

        if ($code !== 200) {
            $read = '';
            if (isset($headers['content-length'])) {
                $read = yield $buffer->bufferRead((int) $headers['content-length']);
            }

            if ($close) {
                $this->disconnect();
                yield $this->connect($this->ctx);
            }

            \danog\MadelineProto\Logger::log($read);

            $this->code = $this->pack_signed_int(-$code);
            $length = 4;

            return $this;
        }

        if ($close) {
            yield $buffer->bufferEnd();
            yield $this->connect($this->ctx);
        }
        if (isset($headers['content-length'])) {
            $length = (int) $headers['content-length'];
        }

        return $buffer;
    }

    public function bufferRead(int $length): Promise
    {
        return new Success($this->code);
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}
