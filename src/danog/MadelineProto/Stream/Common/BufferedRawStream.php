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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Common;

use Amp\ByteStream\ClosedException;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * Buffered raw stream.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class BufferedRawStream implements BufferedStreamInterface, BufferInterface, RawStreamInterface
{
    use RawStream;

    const MAX_SIZE = 10 * 1024 * 1024;

    protected $stream;
    protected $memory_stream;
    private $append = '';
    private $append_after = 0;

    /**
     * Asynchronously connect to a TCP/TLS server.
     *
     * @param ConnectionContext $ctx Connection context
     *
     * @return \Generator
     */
    public function connectGenerator(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $this->stream = yield $ctx->getStream($header);
        $this->memory_stream = \fopen('php://memory', 'r+');

        return true;
    }

    /**
     * Async chunked read.
     *
     * @return Promise
     */
    public function read(): Promise
    {
        if (!$this->stream) {
            throw new ClosedException("MadelineProto stream was disconnected");
        }
        return $this->stream->read();
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
        if (!$this->stream) {
            throw new ClosedException("MadelineProto stream was disconnected");
        }
        return $this->stream->write($data);
    }

    /**
     * Async close.
     *
     * @return Generator
     */
    public function disconnect()
    {
        if ($this->memory_stream) {
            \fclose($this->memory_stream);
            $this->memory_stream = null;
        }
        if ($this->stream) {
            $this->stream->disconnect();
            $this->stream = null;
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
        if (!$this->stream) {
            throw new ClosedException("MadelineProto stream was disconnected");
        }
        $size = \fstat($this->memory_stream)['size'];
        $offset = \ftell($this->memory_stream);
        $length = $size - $offset;
        if ($length === 0 || $size > self::MAX_SIZE) {
            $new_memory_stream = \fopen('php://memory', 'r+');
            if ($length) {
                \fwrite($new_memory_stream, \fread($this->memory_stream, $length));
                \fseek($new_memory_stream, 0);
            }
            \fclose($this->memory_stream);
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
    public function getWriteBuffer(int $length, string $append = ''): Promise
    {
        if (\strlen($append)) {
            $this->append = $append;
            $this->append_after = $length - \strlen($append);
        }

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
        if (!$this->stream) {
            throw new ClosedException("MadelineProto stream was disconnected");
        }
        $size = \fstat($this->memory_stream)['size'];
        $offset = \ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length >= $length) {
            return new Success(\fread($this->memory_stream, $length));
        }
        return \danog\MadelineProto\Tools::call($this->bufferReadGenerator($length));
    }

    /**
     * Read data asynchronously.
     *
     * @param int $length Amount of data to read
     *
     * @return \Generator
     */
    public function bufferReadGenerator(int $length): \Generator
    {
        $size = \fstat($this->memory_stream)['size'];
        $offset = \ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length < $length && $buffer_length) {
            \fseek($this->memory_stream, $offset + $buffer_length);
        }

        while ($buffer_length < $length) {
            $chunk = yield $this->read();
            if ($chunk === null) {
                $this->disconnect();

                throw new \danog\MadelineProto\NothingInTheSocketException();
            }
            \fwrite($this->memory_stream, $chunk);
            $buffer_length += \strlen($chunk);
        }
        \fseek($this->memory_stream, $offset);

        return \fread($this->memory_stream, $length);
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
        if ($this->append_after) {
            $this->append_after -= \strlen($data);
            if ($this->append_after === 0) {
                $data .= $this->append;
                $this->append = '';
            } elseif ($this->append_after < 0) {
                $this->append_after = 0;
                $this->append = '';

                throw new Exception('Tried to send too much out of frame data, cannot append');
            }
        }

        return $this->write($data);
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
    /**
     * {@inheritDoc}
     *
     * @return RawStreamInterface
     */
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
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
