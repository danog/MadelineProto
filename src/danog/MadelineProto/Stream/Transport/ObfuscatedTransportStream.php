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

/**
 * Obfuscated2 AMP stream wrapper
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ObfuscatedStream extends DefaultStream implements RawProxyStreamInterface
{
    private $encrypt;
    private $decrypt;
    private $stream;

    /**
     * Does nothing
     * 
     * @param void $data Nothing
     * 
     * @return void
     */
    public function setExtra($data)
    {
    }

    /**
     * Connect to a server
     *
     * @param string                           $uri           URI
     * @param bool                             $secure        Whether to use TLS while connecting
     * @param \Amp\Socket\ClientConnectContext $socketContext Socket context
     * @param \Amp\CancellationToken           $token         Cancellation token
     * 
     * @return Promise
     */
    public function connect(string $uri, bool $secure, ClientConnectContext $socketContext = null, CancellationToken $token = null): Promise
    {
        return call([$this, 'connectAsync'], $uri, $secure, $socketContext, $token);
    }
    
    /**
     * Connect to a server
     *
     * @param string                           $uri           URI
     * @param bool                             $secure        Whether to use TLS while connecting
     * @param \Amp\Socket\ClientConnectContext $socketContext Socket context
     * @param \Amp\CancellationToken           $token         Cancellation token
     * 
     * @return Promise
     */
    public function connectAsync(string $uri, bool $secure, ClientConnectContext $socketContext = null, CancellationToken $token = null): \Generator
    {
        yield parent::connect($uri, $secure, $socketContext, $token);

        do {
            $random = $this->random(64);
        } while (in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(chr(238), 4)]) || $random[0] === chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");
        $random[56] = $random[57] = $random[58] = $random[59] = chr(0xef);

        $reversed = strrev(substr($random, 8, 48));

        $this->encrypt = new \phpseclib\Crypt\AES('ctr');
        $this->encrypt->enableContinuousBuffer();
        $this->encrypt->setKey(substr($random, 8, 32));
        $this->encrypt->setIV(substr($random, 40, 16));

        $this->decrypt = new \phpseclib\Crypt\AES('ctr');
        $this->decrypt->enableContinuousBuffer();
        $this->decrypt->setKey(substr($reversed, 0, 32));
        $this->decrypt->setIV(substr($reversed, 32, 16));

        $random = substr_replace($random, substr(@$this->encrypt->encrypt($random), 56, 8), 56, 8);

        yield parent::write($random);
    }
    /**
     * Reads data from the stream.
     *
     * @return Promise Resolves with a string when new data is available or `null` if the stream has closed.
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     */
    public function read(): Promise
    {
        return call([$this, 'onRead'], parent::read());
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
    public function onRead(Promise $promise): \Generator
    {
        return @$this->decrypt->encrypt(yield $promise);
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
    public function write(string $data): Promise
    {
        return parent::write(@$this->encrypt->encrypt($data));
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
    public function end(string $finalData = ""): Promise
    {
        return parent::end(@$this->encrypt->encrypt($finalData));
    }


    /**
     * Get read buffer asynchronously
     *
     * @return Promise
     */
    public function getReadBuffer(): Promise
    {
        return call([$this, 'getReadBufferAsync']);
    }
    /**
     * Get read buffer asynchronously
     *
     * @return Generator
     */
    public function getReadBufferAsync(): \Generator
    {
        if (ord(yield $this->bufferRead(1)) >= 127) {
            yield $this->bufferRead(3);
        }

        return $this;
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
        return call([$this, 'getWriteBufferAsync'], $length);
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
        yield $this->bufferWrite($message);
        return $this;
    }

}