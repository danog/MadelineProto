<?php
/**
 * AES CTR stream wrapper.
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
use Amp\Socket\EncryptableSocket;
use danog\MadelineProto\Stream\Async\Buffer;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;

use tgseclib\Crypt\AES;

/**
 * AES CTR stream wrapper.
 *
 * Manages AES CTR encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class CtrStream implements BufferedProxyStreamInterface, BufferInterface
{
    use Buffer;
    use BufferedStream;
    private $encrypt;
    private $decrypt;
    private $stream;
    private $write_buffer;
    private $read_buffer;
    private $extra;
    private $append = '';
    private $append_after = 0;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectGenerator(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $this->encrypt = new \tgseclib\Crypt\AES('ctr');
        $this->encrypt->enableContinuousBuffer();
        $this->encrypt->setKey($this->extra['encrypt']['key']);
        $this->encrypt->setIV($this->extra['encrypt']['iv']);

        $this->decrypt = new \tgseclib\Crypt\AES('ctr');
        $this->decrypt->enableContinuousBuffer();
        $this->decrypt->setKey($this->extra['decrypt']['key']);
        $this->decrypt->setIV($this->extra['decrypt']['iv']);

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
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     *
     * @return Generator
     */
    public function getWriteBufferGenerator(int $length, string $append = ''): \Generator
    {
        $this->write_buffer = yield $this->stream->getWriteBuffer($length);
        if (\strlen($append)) {
            $this->append = $append;
            $this->append_after = $length - \strlen($append);
        }

        return $this;
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
        $this->read_buffer = yield $this->stream->getReadBuffer($length);

        return $this;
    }

    /**
     * Decrypts read data asynchronously.
     *
     * @param Promise $promise Promise that resolves with a string when new data is available or `null` if the stream has closed.
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     *
     * @return Generator That resolves with a string when the provided promise is resolved and the data is decrypted
     */
    public function bufferReadGenerator(int $length): \Generator
    {
        return @$this->decrypt->encrypt(yield $this->read_buffer->bufferRead($length));
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
        if ($this->append_after) {
            $this->append_after -= \strlen($data);
            if ($this->append_after === 0) {
                $data .= $this->append;
                $this->append = '';
            } elseif ($this->append_after < 0) {
                $this->append_after = 0;
                $this->append = '';

                throw new \danog\MadelineProto\Exception('Tried to send too much out of frame data, cannot append');
            }
        }

        return $this->write_buffer->bufferWrite(@$this->encrypt->encrypt($data));
    }

    /**
     * Set obfuscation keys/IVs.
     *
     * @param array $data Keys
     *
     * @return void
     */
    public function setExtra($data)
    {
        $this->extra = $data;
    }

    /**
     * {@inheritdoc}
     *
     * @return EncryptableSocket
     */
    public function getSocket(): EncryptableSocket
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
    public function getEncryptor(): AES
    {
        return $this->encrypt;
    }
    public function getDecryptor(): AES
    {
        return $this->decrypt;
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}
