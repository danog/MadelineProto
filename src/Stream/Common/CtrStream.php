<?php

declare(strict_types=1);

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
use phpseclib3\Crypt\AES;

/**
 * AES CTR stream wrapper.
 *
 * Manages AES CTR encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements BufferedProxyStreamInterface<array{
 *      encrypt: array{key: string, iv: string},
 *      decrypt: array{key: string, iv: string},
 * }>
 */
class CtrStream implements BufferedProxyStreamInterface, BufferInterface
{
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
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $this->encrypt = new AES('ctr');
        $this->encrypt->enableContinuousBuffer();
        $this->encrypt->setKey($this->extra['encrypt']['key']);
        $this->encrypt->setIV($this->extra['encrypt']['iv']);
        $this->decrypt = new AES('ctr');
        $this->decrypt->enableContinuousBuffer();
        $this->decrypt->setKey($this->extra['decrypt']['key']);
        $this->decrypt->setIV($this->extra['decrypt']['iv']);
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
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     */
    public function getWriteBuffer(int $length, string $append = ''): \danog\MadelineProto\Stream\WriteBufferInterface
    {
        $this->write_buffer = $this->stream->getWriteBuffer($length);
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
     */
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        $this->read_buffer = $this->stream->getReadBuffer($length);
        return $this;
    }
    /**
     * Decrypts read data asynchronously.
     */
    public function bufferRead(int $length, ?Cancellation $cancellation = null): string
    {
        return @$this->decrypt->encrypt($this->read_buffer->bufferRead($length, $cancellation));
    }
    /**
     * Writes data to the stream.
     *
     * @param string $data Bytes to write.
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
        $this->write_buffer->bufferWrite(@$this->encrypt->encrypt($data));
    }
    /**
     * Set obfuscation keys/IVs.
     *
     * @param array $data Keys
     */
    public function setExtra($data): void
    {
        $this->extra = $data;
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
        return self::class;
    }
}
