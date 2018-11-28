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

namespace danog\MadelineProto\Stream\Transport;

use Amp\Coroutine;
use Amp\Promise;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\RawProxyStreamInterface;
use danog\MadelineProto\Tools;

/**
 * Obfuscated2 AMP stream wrapper.
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ObfuscatedTransportStream extends DefaultStream implements RawProxyStreamInterface, MTProtoBufferInterface
{
    use BufferedStream;
    use Tools;
    private $encrypt;
    private $decrypt;
    private $stream;

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
        yield new Coroutine(parent::connectAsync($ctx));

        do {
            $random = $this->random(64);
        } while (in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(chr(238), 4)]) || $random[0] === chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");
        $random[56] = $random[57] = $random[58] = $random[59] = chr(0xef);

        list($a, $b) = str_split(pack('s', $ctx->getIntDc()));
        $random[60] = $a;
        $random[61] = $b;

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

        $buffer = yield parent::getWriteBuffer(64);
        yield $buffer->bufferWrite($random);
    }
    /**
     * Async close.
     *
     * @return Promise
     */
    public function disconnect()
    {
        return parent::disconnect();
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
    public function readAsync(): \Generator
    {
        return @$this->decrypt->encrypt(yield parent::read());
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
    public function write(string $data): Promise
    {
        return parent::write(@$this->encrypt->encrypt($data));
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
        $buffer = yield parent::getReadBuffer($length);
        $length = ord(yield $buffer->bufferRead(1));
        if ($length >= 127) {
            $length = unpack('V', (yield $buffer->bufferRead(3))."\0")[1];
        }

        return $buffer;
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
        if ($length < 127) {
            $message = chr($length);
        } else {
            $message = chr(127).substr(pack('V', $length), 0, 3);
        }
        $buffer = yield parent::getWriteBuffer($length);
        yield $buffer->bufferWrite($message);

        return $buffer;
    }

    public static function getName(): string
    {
        return __CLASS__;
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
}
