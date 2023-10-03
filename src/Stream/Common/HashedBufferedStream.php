<?php

declare(strict_types=1);

/**
 * Hash stream wrapper.
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

use Amp\Cancellation;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * Hash stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements BufferedProxyStreamInterface<string>
 */
final class HashedBufferedStream implements BufferedProxyStreamInterface, BufferInterface
{
    private $hash_name;
    private $read_hash;
    private $write_hash;
    private $write_buffer;
    private $write_check_after = 0;
    private $write_check_pos = 0;
    private $read_buffer;
    private $read_check_after = 0;
    private $read_check_pos = 0;
    private $stream;
    private $rev = false;
    /**
     * Enable read hashing.
     */
    public function startReadHash(): void
    {
        $this->read_hash = hash_init($this->hash_name);
    }
    /**
     * Check the read hash after N bytes are read.
     *
     * @param int $after The number of bytes to read before checking the hash
     */
    public function checkReadHash(int $after): void
    {
        $this->read_check_after = $after;
    }
    /**
     * Stop read hashing and get final hash.
     */
    public function getReadHash(): string
    {
        $hash = hash_final($this->read_hash, true);
        if ($this->rev) {
            $hash = strrev($hash);
        }
        $this->read_hash = null;
        $this->read_check_after = 0;
        $this->read_check_pos = 0;
        return $hash;
    }
    /**
     * Check if we are read hashing.
     */
    public function hasReadHash(): bool
    {
        return $this->read_hash !== null;
    }
    /**
     * Enable write hashing.
     */
    public function startWriteHash(): void
    {
        $this->write_hash = hash_init($this->hash_name);
    }
    /**
     * Write the write hash after N bytes are read.
     *
     * @param int $after The number of bytes to read before writing the hash
     */
    public function checkWriteHash(int $after): void
    {
        $this->write_check_after = $after;
    }
    /**
     * Stop write hashing and get final hash.
     */
    public function getWriteHash(): string
    {
        $hash = hash_final($this->write_hash, true);
        if ($this->rev) {
            $hash = strrev($hash);
        }
        $this->write_hash = null;
        $this->write_check_after = 0;
        $this->write_check_pos = 0;
        return $hash;
    }
    /**
     * Check if we are write hashing.
     */
    public function hasWriteHash(): bool
    {
        return $this->write_hash !== null;
    }
    /**
     * Set the hash algorithm.
     *
     * @param string $hash Algorithm name
     */
    public function setExtra($hash): void
    {
        $rev = strpos($hash, '_rev');
        $this->rev = false;
        if ($rev !== false) {
            $hash = substr($hash, 0, $rev);
            $this->rev = true;
        }
        $this->hash_name = $hash;
    }
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $this->write_hash = null;
        $this->write_check_after = 0;
        $this->write_check_pos = 0;
        $this->read_hash = null;
        $this->read_check_after = 0;
        $this->read_check_pos = 0;
        $this->stream = ($ctx->getStream($header));
    }
    /**
     * Async close.
     */
    public function disconnect(): void
    {
        $this->stream->disconnect();
    }
    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     */
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        //if ($this->read_hash) {
        $this->read_buffer = $this->stream->getReadBuffer($length);
        return $this;
        //}
        //return $this->stream->getReadBuffer($length);
    }
    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     */
    public function getWriteBuffer(int $length, string $append = ''): \danog\MadelineProto\Stream\WriteBufferInterface
    {
        //if ($this->write_hash) {
        $this->write_buffer = $this->stream->getWriteBuffer($length, $append);
        return $this;
        //}
        //return $this->stream->getWriteBuffer($length, $append);
    }
    /**
     * Reads data from the stream.
     */
    public function bufferRead(int $length, ?Cancellation $cancellation = null): ?string
    {
        if ($this->read_hash === null) {
            return $this->read_buffer->bufferRead($length, $cancellation);
        }
        if ($this->read_check_after && $length + $this->read_check_pos >= $this->read_check_after) {
            if ($length + $this->read_check_pos > $this->read_check_after) {
                throw new Exception('Tried to read too much out of frame data');
            }
            $data = $this->read_buffer->bufferRead($length, $cancellation);
            hash_update($this->read_hash, $data);
            $hash = $this->getReadHash();
            if ($hash !== $this->read_buffer->bufferRead(\strlen($hash), $cancellation)) {
                throw new Exception('Hash mismatch');
            }
            return $data;
        }
        $data = $this->read_buffer->bufferRead($length, $cancellation);
        hash_update($this->read_hash, $data);
        if ($this->read_check_after) {
            $this->read_check_pos += $length;
        }
        return $data;
    }
    /**
     * Writes data to the stream.
     *
     * @param string $data Bytes to write.
     */
    public function bufferWrite(string $data): void
    {
        if ($this->write_hash === null) {
            $this->write_buffer->bufferWrite($data);
            return;
        }
        $length = \strlen($data);
        if ($this->write_check_after && $length + $this->write_check_pos >= $this->write_check_after) {
            if ($length + $this->write_check_pos > $this->write_check_after) {
                throw new Exception('Too much out of frame data was sent, cannot check hash');
            }
            hash_update($this->write_hash, $data);
            $this->write_buffer->bufferWrite($data.$this->getWriteHash());
            return;
        }
        if ($this->write_check_after) {
            $this->write_check_pos += $length;
        }
        if ($this->write_hash) {
            hash_update($this->write_hash, $data);
        }
        $this->write_buffer->bufferWrite($data);
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
    public static function getName(): string
    {
        return self::class;
    }
}
