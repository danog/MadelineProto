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
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\Async\Buffer;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Tools;

/**
 * Obfuscated2 stream wrapper.
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ObfuscatedStream implements BufferedProxyStreamInterface
{
    use Tools;
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
    public function connectAsync(ConnectionContext $ctx, string $header = ''): \Generator
    {
        if (isset($this->extra['address'])) {
            $ctx = $ctx->getCtx();
            $ctx->setUri('tcp://'.$this->extra['address'].':'.$this->extra['port']);
        }

        do {
            $random = $this->random(64);
        } while (in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(chr(238), 4), str_repeat(chr(221), 4)]) || $random[0] === chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");

        if (strlen($header) === 1) {
            $header = str_repeat($header, 4);
        }
        $random = substr_replace($random, $header.substr($random, 56 + strlen($header)), 56);
        $random = substr_replace($random, pack('s', $ctx->getIntDc()).substr($random, 60 + 2), 60);

        $reversed = strrev($random);

        $key = substr($random, 8, 32);
        $keyRev = substr($reversed, 8, 32);

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
        $this->decrypt->setIV(substr($reversed, 40, 16));

        $random = substr_replace($random, substr(@$this->encrypt->encrypt($random), 56, 8), 56, 8);

        $this->stream = yield $ctx->getStream($random);
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
    public function getWriteBufferAsync(int $length, string $append = ''): \Generator
    {
        $this->write_buffer = yield $this->stream->getWriteBuffer($length);
        if (strlen($append)) {
            $this->append = $append;
            $this->append_after = $length - strlen($append);
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
    public function getReadBufferAsync(&$length): \Generator
    {
        $this->read_buffer = yield $this->stream->getReadBuffer($l);

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
        if ($this->append_after) {
            $this->append_after -= strlen($data);
            if ($this->append_after === 0) {
                $data .= $this->append;
                $this->append = '';
            } elseif ($this->append_after < 0) {
                $this->append_after = 0;
                $this->append = '';

                throw new Exception('Tried to send too much out of frame data, cannot append');
            }
        }

        return $this->write_buffer->bufferWrite(@$this->encrypt->encrypt($data));
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
        if (isset($extra['secret']) && strlen($extra['secret']) > 17) {
            $extra['secret'] = hex2bin($extra['secret']);
        }
        if (isset($extra['secret']) && strlen($extra['secret']) == 17) {
            $extra['secret'] = substr($extra['secret'], 0, 16);
        }
        $this->extra = $extra;
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}
