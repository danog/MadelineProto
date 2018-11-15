<?php
/**
 * Obfuscated2 stream wrapper
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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\MTProtoTransport;

use \Amp\Deferred;
use \Amp\Promise;
use \danog\MadelineProto\Stream\Common\BufferedRawStream;
use \danog\MadelineProto\Stream\BufferInterface;
use \danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use \danog\MadelineProto\Stream\RawProxyStreamInterface;
use function \Amp\call;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\BufferedStreamInterface;

/**
 * Obfuscated2 AMP stream wrapper
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HttpStream implements BufferedStreamInterface
{
    use BufferedStream;
    private $stream;
    private $code;
    private $ctx;
    /**
     * URI of the HTTP API
     *
     * @var \Amp\Uri\Uri
     */
    private $uri;

    /**
     * Stream to use as data source
     *
     * @param BufferedStreamInterface $stream The stream
     * 
     * @return Promise
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->ctx = $ctx->getCtx();
        $this->stream = yield $ctx->getStream();
        $this->uri = $ctx->getUri();
    }

    /**
     * Get write buffer asynchronously
     * 
     * @param integer $length Length of data that is going to be written to the write buffer
     *
     * @return Generator
     */
    public function getWriteBufferAsync($length): \Generator
    {
        $header_data = 'POST ' . $this->uri->getAbsoluteUri() . " HTTP/1.1\r\nHost: " . $this->uri->getHost() . ':' . $this->uri->getPort() . "\r\n" . "Content-Type: application/x-www-form-urlencoded\r\nConnection: keep-alive\r\nKeep-Alive: timeout=100000, max=10000000\r\nContent-Length: " . $length . "\r\n\r\n";
        $buffer = yield $this->stream->getWriteBuffer(strlen($header_data)+$length);
        yield $buffer->bufferWrite($header_data);
        return $buffer;
    }

    /**
     * Get read buffer asynchronously
     *
     * @return Generator
     */
    public function getReadBufferAsync(): \Generator
    {
        $buffer = yield $this->stream->getReadBuffer();
        $header_data = '';
        $was_crlf = false;
        while (true) {
            $piece = yield $buffer->bufferRead(2);
            $header_data .= $piece;
            if ($piece === "\n\r") { // Assume end of headers with \r\n\r\n
                $header_data .= yield $buffer->bufferRead(1);
                break;
            }
            if ($was_crlf && $piece === "\r\n") {
                break;
            }
            $was_crlf = $piece === "\r\n";
        }
        $header_data = explode("\r\n", $header_data);

        list($protocol, $code, $description) = explode(' ', $header_data[0], 3);
        list($protocol, $protocol_version) = explode('/', $protocol);
        if ($protocol !== 'HTTP') {
            throw new \danog\MadelineProto\Exception('Wrong protocol');
        }
        $code = (int) $code;
        unset($header_data[0]);
        $headers = [];
        foreach ($header_data as $current_header) {
            $current_header = explode(':', $current_header, 2);
            $headers[strtolower($current_header[0])] = trim($current_header[1]);
        }

        $close = $protocol === 'HTTP/1.0' || $code !== 200;
        if (isset($headers['connection'])) {
            $close = strtolower($headers['connection']) === 'close';
        }
        if ($close) {
            yield $this->stream->bufferEnd();
            yield $this->connect($this->ctx);
        }

        if ($response['code'] !== 200) {
            $read = '';
            if (isset($headers['content-length'])) {
                $read = yield $buffer->bufferRead((int) $headers['content-length']);
            }

            \danog\MadelineProto\Logger::log($read);

            $this->code = $this->pack_signed_int(-$code);

            return $this;
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