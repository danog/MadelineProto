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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Common;

use Amp\ByteStream\ClosedException;
use Amp\File\File;
use Amp\Promise;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * Buffered raw stream.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class FileBufferedStream implements BufferedStreamInterface, BufferInterface, ProxyStreamInterface, RawStreamInterface
{
    private File $stream;
    private int $append_after;
    private string $append;
    /**
     * Connect.
     *
     * @param ConnectionContext $ctx
     * @param string $header
     * @return \Generator
     */
    public function connect(ConnectionContext $ctx, string $header = ''): \Generator
    {
        if ($header !== '') {
            yield $this->stream->write($header);
        }
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
     * Async write.
     *
     * @param string $data Data to write
     *
     * @return Promise
     */
    public function end(string $finalData = ''): Promise
    {
        if (!$this->stream) {
            throw new ClosedException("MadelineProto stream was disconnected");
        }
        return $this->stream->end($finalData);
    }
    /**
     * Async close.
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->stream) {
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
        return $this->stream->read($length);
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
     * Set file handle.
     *
     * @param File $extra
     * @return void
     */
    public function setExtra($extra)
    {
        $this->stream = $extra;
    }
    /**
     * {@inheritDoc}
     *
     * @return RawStreamInterface
     */
    public function getStream(): RawStreamInterface
    {
        throw new \RuntimeException("Can't get underlying RawStreamInterface, is a File handle!");
    }
    /**
     * {@inheritDoc}
     */
    public function getSocket(): Socket
    {
        throw new \RuntimeException("Can't get underlying socket, is a File handle!");
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
