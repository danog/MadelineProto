<?php
/**
 * Obfuscated2 stream wrapper.
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
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\MTProtoTransport;

use Amp\Promise;
use danog\MadelineProto\Stream\Async\Buffer;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;

/**
 * Obfuscated2 stream wrapper.
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ObfuscatedStream implements BufferedProxyStreamInterface, MTProtoBufferInterface
{
    use Buffer;
    use BufferedStream;
    private $encrypt;
    private $decrypt;
    private $stream;
    private $write_buffer;
    private $read_buffer;
    private $extra;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        if (isset($this->extra['address'])) {
            $ctx = $ctx->getCtx();
            $ctx->setUri('tcp://'.$this->extra['address'].':'.$this->extra['port']);
        }
        $this->stream = yield $ctx->getStream();

        do {
            $random = $this->random(64);
        } while (in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(chr(238), 4)]) || $random[0] === chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");
        $random[56] = $random[57] = $random[58] = $random[59] = chr(0xef);

        $random = substr_replace(pack('s', $ctx->getDc()), 60, 2);

        $reversed = strrev(substr($random, 8, 48));

        $key = substr($random, 8, 32);
        $keyRev = substr($reversed, 0, 32);
        if (isset($this->extra['secret'])) {
            $key = hash('sha256', $key.$this->extra['secret'], true);
            $keyRev = hash('sha256', $keyRev.$this->extra['secret'], true);
        }

        $this->encrypt = new \phpseclib\Crypt\AES('ctr');
        $this->encrypt->enableContinuousBuffer();
        $this->encrypt->setKey($key);
        $this->encrypt->setIV(substr($random, 40, 16));

        $this->decrypt = new \phpseclib\Crypt\AES('ctr');
        $this->decrypt->enableContinuousBuffer();
        $this->decrypt->setKey($keyRev);
        $this->decrypt->setIV(substr($reversed, 32, 16));

        $random = substr_replace($random, substr(@$this->encrypt->encrypt($random), 56, 8), 56, 8);

        $buffer = yield $this->stream->getWriteBuffer(64);
        yield $buffer->bufferWrite($random);
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     *
     * @return Generator
     */
    public function getWriteBufferAsync(int $length): \Generator
    {
        $length >>= 2;
        if ($length < 127) {
            $message = chr($length);
        } else {
            $message = chr(127).substr(pack('V', $length), 0, 3);
        }
        $buffer = yield $this->stream->getWriteBuffer(strlen($message) + $length);
        yield $buffer->bufferWrite($message);
        $this->write_buffer = $buffer;

        return $this;
    }

    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     * @return Generator
     */
    public function getReadBufferAsync(&$length): \Generator
    {
        $buffer = yield $this->stream->getReadBuffer($l);
        $length = ord(yield $buffer->bufferRead(1));
        if ($length >= 127) {
            $length = unpack('V', (yield $buffer->bufferRead(3))."\0")[1];
        }
        $length <<= 2;

        $this->read_buffer = $buffer;

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
    public function bufferReadAsync(int $length): \Generator
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
        return $this->buffer_write->bufferWrite(@$this->encrypt->encrypt($data));
    }

    /**
     * Writes data to the stream and closes it.
     *
     * @param string $data Bytes to write.
     *
     * @throws ClosedException If the stream has already been closed.
     *
     * @return Promise Succeeds once the data has been successfully written to the stream.
     */
    public function bufferEnd(string $finalData = ''): Promise
    {
        return $this->buffer_write->bufferEnd(@$this->encrypt->encrypt($finalData));
    }

    /**
     * Does nothing.
     *
     * @param void $data Nothing
     *
     * @return void
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}
