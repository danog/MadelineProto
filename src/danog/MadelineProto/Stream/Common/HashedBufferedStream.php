<?php
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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Common;

use Amp\Promise;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;

use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * Hash stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HashedBufferedStream implements BufferedProxyStreamInterface, BufferInterface
{
    use BufferedStream;
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
     *
     * @return void
     */
    public function startReadHash()
    {
        $this->read_hash = \hash_init($this->hash_name);
    }

    /**
     * Check the read hash after N bytes are read.
     *
     * @param int $after The number of bytes to read before checking the hash
     *
     * @return void
     */
    public function checkReadHash(int $after)
    {
        $this->read_check_after = $after;
    }

    /**
     * Stop read hashing and get final hash.
     *
     * @return string
     */
    public function getReadHash(): string
    {
        $hash = \hash_final($this->read_hash, true);
        if ($this->rev) {
            $hash = \strrev($hash);
        }
        $this->read_hash = null;
        $this->read_check_after = 0;
        $this->read_check_pos = 0;

        return $hash;
    }

    /**
     * Check if we are read hashing.
     *
     * @return bool
     */
    public function hasReadHash(): bool
    {
        return $this->read_hash !== null;
    }

    /**
     * Enable write hashing.
     *
     * @return void
     */
    public function startWriteHash()
    {
        $this->write_hash = \hash_init($this->hash_name);
    }

    /**
     * Write the write hash after N bytes are read.
     *
     * @param int $after The number of bytes to read before writing the hash
     *
     * @return void
     */
    public function checkWriteHash(int $after)
    {
        $this->write_check_after = $after;
    }

    /**
     * Stop write hashing and get final hash.
     *
     * @return string
     */
    public function getWriteHash(): string
    {
        $hash = \hash_final($this->write_hash, true);
        if ($this->rev) {
            $hash = \strrev($hash);
        }
        $this->write_hash = null;
        $this->write_check_after = 0;
        $this->write_check_pos = 0;

        return $hash;
    }

    /**
     * Check if we are write hashing.
     *
     * @return bool
     */
    public function hasWriteHash(): bool
    {
        return $this->write_hash !== null;
    }

    /**
     * Hashes read data asynchronously.
     *
     * @param int $length Read and hash $length bytes
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     *
     * @return Generator That resolves with a string when the provided promise is resolved and the data is added to the hashing context
     */
    public function bufferReadGenerator(int $length): \Generator
    {
        if ($this->read_check_after && $length + $this->read_check_pos >= $this->read_check_after) {
            if ($length + $this->read_check_pos > $this->read_check_after) {
                throw new \danog\MadelineProto\Exception('Tried to read too much out of frame data');
            }
            $data = yield $this->read_buffer->bufferRead($length);
            \hash_update($this->read_hash, $data);
            $hash = $this->getReadHash();
            if ($hash !== yield $this->read_buffer->bufferRead(\strlen($hash))) {
                throw new \danog\MadelineProto\Exception('Hash mismatch');
            }

            return $data;
        }
        $data = yield $this->read_buffer->bufferRead($length);
        \hash_update($this->read_hash, $data);
        if ($this->read_check_after) {
            $this->read_check_pos += $length;
        }

        return $data;
    }

    /**
     * Set the hash algorithm.
     *
     * @param string $hash Algorithm name
     *
     * @return void
     */
    public function setExtra($hash)
    {
        $rev = \strpos($hash, '_rev');
        $this->rev = false;
        if ($rev !== false) {
            $hash = \substr($hash, 0, $rev);
            $this->rev = true;
        }
        $this->hash_name = $hash;
    }

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectGenerator(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $this->write_hash = null;
        $this->write_check_after = 0;
        $this->write_check_pos = 0;
        $this->read_hash = null;
        $this->read_check_after = 0;
        $this->read_check_pos = 0;

        $this->stream = yield $ctx->getStream($header);
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
     * @return Generator
     */
    public function getReadBufferGenerator(&$length): \Generator
    {
        //if ($this->read_hash) {
        $this->read_buffer = yield $this->stream->getReadBuffer($length);

        return $this;
        //}

        //return yield $this->stream->getReadBuffer($length);
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     *
     * @return Generator
     */
    public function getWriteBufferGenerator(int $length, string $append = ''): \Generator
    {
        //if ($this->write_hash) {
        $this->write_buffer = yield $this->stream->getWriteBuffer($length, $append);

        return $this;
        //}

        //return yield $this->stream->getWriteBuffer($length, $append);
    }

    /**
     * Reads data from the stream.
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     *
     * @return Promise Resolves with a string when new data is available or `null` if the stream has closed.
     */
    public function bufferRead(int $length): Promise
    {
        if ($this->read_hash === null) {
            return $this->read_buffer->bufferRead($length);
        }

        return \danog\MadelineProto\Tools::call($this->bufferReadGenerator($length));
    }

    /**
     * Writes data to the stream.
     *
     * @param string $data Bytes to write.
     *
     * @throws ClosedException If the stream has already been closed.
     *
     * @return Promise Succeeds once the data has been successfully written to the stream.
     */
    public function bufferWrite(string $data): Promise
    {
        if ($this->write_hash === null) {
            return $this->write_buffer->bufferWrite($data);
        }

        $length = \strlen($data);
        if ($this->write_check_after && $length + $this->write_check_pos >= $this->write_check_after) {
            if ($length + $this->write_check_pos > $this->write_check_after) {
                throw new \danog\MadelineProto\Exception('Too much out of frame data was sent, cannot check hash');
            }
            \hash_update($this->write_hash, $data);

            return $this->write_buffer->bufferWrite($data.$this->getWriteHash());
        }
        if ($this->write_check_after) {
            $this->write_check_pos += $length;
        }
        if ($this->write_hash) {
            \hash_update($this->write_hash, $data);
        }

        return $this->write_buffer->bufferWrite($data);
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
    public static function getName(): string
    {
        return __CLASS__;
    }
}
