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
use Amp\File\File;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;
use RuntimeException;

/**
 * Buffered raw stream.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements ProxyStreamInterface<File>
 */
final class FileBufferedStream implements BufferedStreamInterface, BufferInterface, ProxyStreamInterface, RawStreamInterface
{
    private ?File $stream = null;
    private int $append_after;
    private string $append;
    /**
     * Connect.
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        if ($header !== '') {
            $this->stream->write($header);
        }
    }
    /**
     * Async chunked read.
     */
    public function read(?Cancellation $token = null): ?string
    {
        if (!$this->stream) {
            throw new ClosedException('MadelineProto stream was disconnected');
        }
        return $this->stream->read($token);
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
        if ($this->stream) {
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
    public function bufferRead(int $length, ?Cancellation $cancellation = null): ?string
    {
        return $this->stream->read($cancellation, $length);
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
     * Set file handle.
     */
    public function setExtra($extra): void
    {
        $this->stream = $extra;
    }
    /**
     * {@inheritDoc}
     */
    public function getStream(): RawStreamInterface
    {
        throw new RuntimeException("Can't get underlying RawStreamInterface, is a File handle!");
    }
    /**
     * {@inheritDoc}
     */
    public function getSocket(): Socket
    {
        throw new RuntimeException("Can't get underlying socket, is a File handle!");
    }
    /**
     * Get class name.
     */
    public static function getName(): string
    {
        return self::class;
    }
}
