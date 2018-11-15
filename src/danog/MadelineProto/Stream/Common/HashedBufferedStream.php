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

namespace danog\MadelineProto\Stream\Common;

use \Amp\Deferred;
use \Amp\Promise;
use \danog\MadelineProto\Stream\Common\BufferedRawStream;
use \danog\MadelineProto\Stream\BufferInterface;
use \danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use function \Amp\call;
use danog\MadelineProto\Stream\ConnectionContext;

/**
 * Obfuscated2 AMP stream wrapper
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HashedBufferedStream implements BufferedProxyStreamInterface, BufferInterface
{
    use Stream;
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

    /**
     * Enable read hashing
     *
     * @return void
     */
    public function startReadHash()
    {
        $this->read_hash = hash_init($this->hash_name);
    }
    /**
     * Check the read hash after N bytes are read
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
     * Stop read hashing and get final hash
     * 
     * @return string
     */
    public function getReadHash(): string
    {
        $hash = hash_final($this->read_hash, true);
        $this->read_hash = null;
        $this->read_check_after = 0;
        $this->read_check_pos = 0;
        return $hash;
    }

    /**
     * Check if we are read hashing
     * 
     * @return bool
     */
    public function hasReadHash(): bool
    {
        return $this->read_hash !== null;
    }

    /**
     * Enable write hashing
     *
     * @return void
     */
    public function startWriteHash()
    {
        $this->write_hash = hash_init($this->hash_name);
    }

    /**
     * Write the write hash after N bytes are read
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
     * Stop write hashing and get final hash
     * 
     * @return string
     */
    public function getWriteHash(): string
    {
        $hash = hash_final($this->write_hash, true);
        $this->write_hash = null;
        $this->write_check_after = 0;
        $this->write_check_pos = 0;
        return $hash;
    }

    /**
     * Check if we are write hashing
     * 
     * @return bool
     */
    public function hasWriteHash(): bool
    {
        return $this->write_hash !== null;
    }

    /**
     * Hashes read data asynchronously
     *
     * @param int $length Read and hash $length bytes
     * 
     * @return Generator That resolves with a string when the provided promise is resolved and the data is added to the hashing context
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     */
    public function bufferReadAsync(int $length): \Generator
    {
        if ($this->read_check_after && $length + $this->read_check_pos >= $this->read_check_after) {
            if ($length + $this->read_check_pos > $this->read_check_after) {
                throw new \danog\MadelineProto\Exception('Tried to read too much out of frame data');
            }
            $data = yield $this->read_buffer->bufferRead($read_length);
            if ($data === null) {
                return $data;
            }
            hash_update($this->read_hash, $data);
            $hash = $this->getReadHash();
            if ($hash !== yield $this->read_buffer->bufferRead(strlen($hash))) {
                throw new \danog\MadelineProto\Exception('Hash mismatch');
            }
            return $data;
        }
        $data = yield $this->read_buffer->bufferRead($length);
        if ($data === null) {
            return $data;
        }
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
     *
     * @return Generator Succeeds once the data has been successfully written to the stream.
     *
     * @throws ClosedException If the stream has already been closed.
     */
    public function bufferWriteAsync(string $data): \Generator
    {
        $length = strlen($data);
        if ($this->write_check_after && $length + $this->write_check_pos >= $this->write_check_after) {
            if ($length + $this->write_check_pos > $this->write_check_after) {
                throw new \danog\MadelineProto\Exception('Too much out of frame data was sent, cannot check hash');
            }
            if (null === yield $this->write_buffer->bufferWrite($data)) {
                return null;
            }
            hash_update($this->write_hash, $data);
            return yield $this->write_buffer->bufferWrite($this->getWriteHash());
        }
        if ($this->write_check_after) {
            $this->write_check_pos += $length;
        }
        if ($this->write_hash) {
            hash_update($this->write_hash, $data);
        }
        return $this->buffer_write->bufferWrite($data);
    }

    /**
     * Writes data to the stream and closes it.
     *
     * @param string $data Bytes to write.
     *
     * @return Promise Succeeds once the data has been successfully written to the stream.
     *
     * @throws ClosedException If the stream has already been closed.
     */
    public function bufferEnd(string $finalData = ""): Promise
    {
        if ($this->write_hash) {
            hash_update($this->write_hash, $finalData);
        }
        return $this->buffer_write->bufferEnd($finalData);
    }

    /**
     * Set the hash algorithm
     * 
     * @param string $hash Algorithm name
     * 
     * @return void
     */
    public function setExtra($hash)
    {
        $this->hash_name = $hash;
    }

 

    /**
     * Stream to use as data source
     *
     * @param mixed $stream The stream
     * 
     * @return Promise
     */
    private function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->stream = yield $ctx->getStream();
    }

    /**
     * Get read buffer asynchronously
     *
     * @return Promise
     */
    public function getReadBuffer(): Promise
    {
        if ($this->read_hash) {
            $this->read_buffer = $this->stream->getReadBuffer();
            return new Success($this);
        }
        return $this->stream->getReadBuffer();
    }
    /**
     * Get write buffer asynchronously
     *
     * @param int $length Length of data that is going to be written to the write buffer
     * 
     * @return Promise
     */
    public function getWriteBuffer(int $length): Promise
    {
        if ($this->write_hash) {
            $this->write_buffer = $this->stream->getWriteBuffer($length);
            return new Success($this);
        }
        return $this->stream->getWriteBuffer($length);
    }

    /**
     * Reads data from the stream.
     *
     * @return Promise Resolves with a string when new data is available or `null` if the stream has closed.
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     */
    public function bufferRead(int $length): Promise
    {
        if ($this->read_hash === null) {
            return $this->read_buffer->bufferRead($length);
        }
        return call([$this, 'bufferReadAsync'], $length);
    }

    /**
     * Writes data to the stream.
     *
     * @param string $data Bytes to write.
     *
     * @return Promise Succeeds once the data has been successfully written to the stream.
     *
     * @throws ClosedException If the stream has already been closed.
     */
    public function bufferWrite(string $data): Promise
    {
        if ($this->write_hash === null) {
            return $this->write_buffer->bufferRead($length);
        }
        return call([$this, 'bufferWriteAsync'], $data);
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}