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
use Amp\Success;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Tools;
use function Amp\Websocket\connect;

/**
 * Websocket stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class WsStream implements BufferedStreamInterface, RawStreamInterface, BufferInterface
{
    use BufferedStream;
    use RawStream;
    use Tools;
    private $sock;
    private $memory_stream;
    private $buffer;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->sock = yield connect($ctx->getStringUri(), $ctx->getSocketContext());
        $this->memory_stream = fopen('php://memory', 'r+');
        return true;
    }
    /**
     * Async close.
     */
    public function disconnect()
    {
        try {
            if ($this->sock) {
                $this->sock->close();
                $this->sock = null;
            }
            if ($this->memory_stream) {
                fclose($this->memory_stream);
                $this->memory_stream = null;
            }
        } catch (\Throwable $e) {
            \danog\MadelineProto\Logger::log("Got exception while closing stream: $e");
        }
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     *
     * @return Generator
     */
    public function getWriteBuffer(int $length): Promise
    {
        return new Success($this);
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
        fwrite($this->memory_stream, yield (yield $this->sock->receive())->buffer());

        return $this;
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

        throw new \danog\MadelineProto\Exception('Not enough data');
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
        if ($buffer_length < $length && $buffer_length) {
            fseek($this->memory_stream, $offset + $buffer_length);
        }

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

    public function bufferWrite(string $data): Promise
    {
        return $this->sock->sendBinary($data);
    }
    public static function getName(): string
    {
        return __CLASS__;
    }
}

