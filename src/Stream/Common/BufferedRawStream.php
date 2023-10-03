<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Common;

use Amp\ByteStream\ClosedException;
use Amp\Cancellation;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\NothingInTheSocketException;
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
    private const MAX_SIZE = 10 * 1024 * 1024;
    protected $stream;
    protected $memory_stream;
    private $append = '';
    private $append_after = 0;
    /**
     * Asynchronously connect to a TCP/TLS server.
     *
     * @param ConnectionContext $ctx Connection context
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $this->stream = $ctx->getStream($header);
        $this->memory_stream = fopen('php://memory', 'r+');
    }
    /**
     * Async chunked read.
     */
    public function read(?Cancellation $cancellation = null): ?string
    {
        if (!$this->stream) {
            throw new ClosedException('MadelineProto stream was disconnected');
        }
        return $this->stream->read($cancellation);
    }
    /**
     * Async write.
     *
     * @param string $data Data to write
     */
    public function write(string $data): void
    {
        if (!$this->stream) {
            throw new ClosedException('MadelineProto stream was disconnected');
        }
        $this->stream->write($data);
    }
    /**
     * Async close.
     */
    public function disconnect(): void
    {
        if ($this->memory_stream) {
            fclose($this->memory_stream);
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
     */
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        if (!$this->stream) {
            throw new ClosedException('MadelineProto stream was disconnected');
        }
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
        return $this;
    }
    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Total length of data that is going to be piped in the buffer
     */
    public function getWriteBuffer(int $length, string $append = ''): \danog\MadelineProto\Stream\WriteBufferInterface
    {
        if (\strlen($append)) {
            $this->append = $append;
            $this->append_after = $length - \strlen($append);
        }
        return $this;
    }
    /**
     * Read data asynchronously.
     *
     * @param int $length Amount of data to read
     */
    public function bufferRead(int $length, ?Cancellation $cancellation = null): string
    {
        if (!$this->stream) {
            throw new ClosedException('MadelineProto stream was disconnected');
        }
        $size = fstat($this->memory_stream)['size'];
        $offset = ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length >= $length) {
            return fread($this->memory_stream, $length);
        }
        $size = fstat($this->memory_stream)['size'];
        $offset = ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length < $length && $buffer_length) {
            fseek($this->memory_stream, $offset + $buffer_length);
        }
        while ($buffer_length < $length) {
            $chunk = $this->read($cancellation);
            if ($chunk === null) {
                $this->disconnect();
                throw new NothingInTheSocketException();
            }
            fwrite($this->memory_stream, $chunk);
            $buffer_length += \strlen($chunk);
        }
        fseek($this->memory_stream, $offset);
        return fread($this->memory_stream, $length);
    }
    /**
     * Async write.
     *
     * @param string $data Data to write
     */
    public function bufferWrite(string $data): void
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
        $this->write($data);
    }
    /**
     * Get remaining data from buffer.
     */
    public function bufferClear(): string
    {
        $size = fstat($this->memory_stream)['size'];
        $offset = ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        $data = fread($this->memory_stream, $buffer_length);
        fclose($this->memory_stream);
        $this->memory_stream = null;
        return $data;
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
    /**
     * Get class name.
     */
    public static function getName(): string
    {
        return self::class;
    }
}
