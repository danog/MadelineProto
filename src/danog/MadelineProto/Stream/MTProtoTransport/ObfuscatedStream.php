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

namespace danog\MadelineProto\Streams\Transport;

use \Amp\Deferred;
use \Amp\Promise;
use \danog\MadelineProto\Stream\Common\BufferedRawStream;
use \danog\MadelineProto\Stream\BufferInterface;
use \danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use \danog\MadelineProto\Stream\RawProxyStreamInterface;
use function \Amp\call;
use danog\MadelineProto\Stream\Async\Buffer;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;

/**
 * Obfuscated2 AMP stream wrapper
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ObfuscatedStream implements BufferedProxyStreamInterface, BufferInterface
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
     * Stream to use as data source
     *
     * @param BufferedStreamInterface $stream The stream
     * 
     * @return Promise
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
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
     * Get write buffer asynchronously
     * 
     * @param integer $length Length of data that is going to be written to the write buffer
     *
     * @return Generator
     */
    public function getWriteBufferAsync($length): \Generator
    {
        if ($length < 127) {
            $message = chr($length);
        } else {
            $message = chr(127) . substr(pack('V', $length), 0, 3);
        }
        $buffer = yield $this->stream->getWriteBuffer(strlen($message)+$length);
        yield $buffer->bufferWrite($message);
        $this->write_buffer = $buffer;
        return $this;
    }

    /**
     * Get read buffer asynchronously
     *
     * @return Generator
     */
    public function getReadBufferAsync(): \Generator
    {
        $buffer = yield $this->stream->getReadBuffer();
        if (ord(yield $buffer->bufferRead(1)) >= 127) {
            yield $buffer->bufferRead(3);
        }

        $this->read_buffer = $buffer;

        return $this;
    }

    /**
     * Decrypts read data asynchronously
     *
     * @param Promise $promise Promise that resolves with a string when new data is available or `null` if the stream has closed.
     * 
     * @return Generator That resolves with a string when the provided promise is resolved and the data is decrypted
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
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
     * @return Promise Succeeds once the data has been successfully written to the stream.
     *
     * @throws ClosedException If the stream has already been closed.
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
     * @return Promise Succeeds once the data has been successfully written to the stream.
     *
     * @throws ClosedException If the stream has already been closed.
     */
    public function bufferEnd(string $finalData = ""): Promise
    {
        return $this->buffer_write->bufferEnd(@$this->encrypt->encrypt($finalData));
    }

    /**
     * Does nothing
     * 
     * @param void $data Nothing
     * 
     * @return void
     */
    public function setExtra(mixed $extra)
    {
        $this->extra = $extra;
    }
}