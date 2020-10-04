<?php

/**
 * UDP stream wrapper.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Common;

use Amp\ByteStream\ClosedException;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use Amp\Success;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Stream\ReadBufferInterface;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\WriteBufferInterface;

/**
 * UDP stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class UdpBufferedStream extends DefaultStream implements BufferedStreamInterface, MTProtoBufferInterface
{
    use BufferedStream;
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connect(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $this->stream = (yield from $ctx->getStream($header));
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
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, Promise, mixed, Failure<mixed>|Success<object>>
     */
    public function getReadBufferGenerator(&$length): \Generator
    {
        if (!$this->stream) {
            return new Failure(new ClosedException("MadelineProto stream was disconnected"));
        }
        $chunk = yield $this->read();
        if ($chunk === null) {
            $this->disconnect();
            throw new \danog\MadelineProto\NothingInTheSocketException();
        }
        $length = \strlen($chunk);
        return new Success(new class($chunk) implements ReadBufferInterface {
            /**
             * Buffer.
             *
             * @var resource
             */
            private $buffer;
            /**
             * Constructor function.
             *
             * @param string $buf Buffer
             */
            public function __construct(string $buf)
            {
                $this->buffer = \fopen('php://memory', 'r+');
                \fwrite($this->buffer, $buf);
                \fseek($this->buffer, 0);
            }
            /**
             * Read data from buffer.
             *
             * @param integer $length Length
             *
             * @return Promise<string>
             */
            public function bufferRead(int $length): Promise
            {
                return new Success(\fread($this->buffer, $length));
            }
            /**
             * Destructor function.
             */
            public function __destruct()
            {
                \fclose($this->buffer);
            }
        });
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
        return new Success(new class($length, $append, $this) implements WriteBufferInterface {
            private int $length;
            private string $append;
            private int $append_after;
            private RawStreamInterface $stream;
            private string $data = '';
            /**
             * Constructor function.
             *
             * @param integer $length
             * @param string $append
             * @param RawStreamInterface $rawStreamInterface
             */
            public function __construct(int $length, string $append, RawStreamInterface $rawStreamInterface)
            {
                $this->stream = $rawStreamInterface;
                $this->length = $length;
                if (\strlen($append)) {
                    $this->append = $append;
                    $this->append_after = $length - \strlen($append);
                }
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
                $this->data .= $data;
                if ($this->append_after) {
                    $this->append_after -= \strlen($data);
                    if ($this->append_after === 0) {
                        $this->data .= $this->append;
                        $this->append = '';
                        return $this->stream->write($this->data);
                    } elseif ($this->append_after < 0) {
                        $this->append_after = 0;
                        $this->append = '';
                        throw new Exception('Tried to send too much out of frame data, cannot append');
                    }
                }
                return new Success(\strlen($data));
            }
        });
    }
    /**
     * {@inheritdoc}
     *
     * @return EncryptableSocket
     */
    public function getSocket(): EncryptableSocket
    {
        return $this->getSocket();
    }
    /**
     * {@inheritDoc}
     *
     * @return RawStreamInterface
     */
    public function getStream(): RawStreamInterface
    {
        return $this;
    }
    public static function getName(): string
    {
        return __CLASS__;
    }
}
