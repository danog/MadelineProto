<?php
/**
 * Buffered raw stream.
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

namespace danog\MadelineProto\Stream\Common;

use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use function Amp\call;
use function Amp\Socket\connect;
use function Amp\Socket\cryptoConnect;

/**
 * Buffered raw stream.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class BufferedRawStream implements \danog\MadelineProto\Stream\BufferedStreamInterface, \danog\MadelineProto\Stream\BufferInterface, \danog\MadelineProto\Stream\RawStreamInterface
{
    use RawStream;

    const MAX_SIZE = 10 * 1024 * 1024;

    private $sock;
    private $memory_stream;

    /**
     * Asynchronously connect to a TCP/TLS server.
     *
     * @param ConnectionContext $ctx Connection context
     *
     * @return \Generator
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        if ($ctx->isSecure()) {
            $this->sock = yield cryptoConnect($ctx->getStringUri(), $ctx->getSocketContext(), $ctx->getCancellationToken());
        } else {
            $this->sock = yield connect($ctx->getStringUri(), $ctx->getSocketContext());
        }
        $this->memory_stream = fopen('php://memory', 'r+');

        return true;
    }

    /**
     * Async chunked read.
     *
     * @return Promise
     */
    public function read(): Promise
    {
        return $this->sock->read();
    }

    /**
     * Async write.
     *
     * @param string $data Data to write
     *
     * @return Promise
     */
    public function write(string $data): Promise
    {
        return $this->sock->write($data);
    }

    /**
     * Async close.
     *
     * @return Generator
     */
    public function disconnectAsync(string $finalData = ''): \Generator
    {
        try {
            yield $this->sock->end($finalData);
            $this->sock = null;
            fclose($this->memory_stream);
            $this->memory_stream = null;
        } catch (\Throwable $e) {
            \danog\MadelineProto\Logger::log("Got exception while closing stream: $e");
        }
    }

    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     * @return Promise
     */
    public function getReadBuffer(&$length): Promise
    {
        $size = fstat($this->memory_stream)['size'];
        $offset = ftell($this->memory_stream);
        $length = $size - $offset;
        if ($length === 0 || $size > self::MAX_SIZE) {
            $new_memory_stream = fopen('php://memory', 'r+');
            if ($length) {
                fwrite($new_memory_stream, fread($this->memory_stream, $length));
                fseek($new_memory_stream, 0);
            }
            fclose($this->memory_stream);
            $this->memory_stream = $new_memory_stream;
        }

        return new \Amp\Success($this);
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Total length of data that is going to be piped in the buffer
     *
     * @return Promise
     */
    public function getWriteBuffer(int $length): Promise
    {
        return new \Amp\Success($this);
    }

    /**
     * Read data asynchronously.
     *
     * @param int $length Amount of data to read
     *
     * @return Promise
     */
    public function bufferRead(int $length): Promise
    {
        $size = fstat($this->memory_stream)['size'];
        $offset = ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length >= $length) {
            return new Success(fread($this->memory_stream, $length));
        }

        return call([$this, 'bufferReadAsync'], $length);
    }

    /**
     * Read data asynchronously.
     *
     * @param int $length Amount of data to read
     *
     * @return \Generator
     */
    public function bufferReadAsync(int $length): \Generator
    {
        $size = fstat($this->memory_stream)['size'];
        $offset = ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length < $length && $buffer_length) fseek($this->memory_stream, $offset+$buffer_length);
        while ($buffer_length < $length) {
            $chunk = yield $this->read();
            if ($chunk === null) {
                yield $this->disconnect();

                throw new \danog\MadelineProto\NothingInTheSocketException();
            }
            fwrite($this->memory_stream, $chunk);
            $buffer_length += strlen($chunk);
        }
        fseek($this->memory_stream, $offset);

        return fread($this->memory_stream, $length);
    }

    /**
     * Async write.
     *
     * @param string $data Data to write
     *
     * @return Promise
     */
    public function bufferWrite(string $data): Promise
    {
        return $this->write($data);
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public static function getName(): string
    {
        return __CLASS__;
    }
}
