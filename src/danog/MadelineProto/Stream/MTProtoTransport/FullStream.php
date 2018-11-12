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
class FullStream implements BufferedProxyStreamInterface, BufferInterface
{
    private $stream;
    private $in_seq_no = -1;
    private $out_seq_no = -1;
    private $read_length = 0;

    /**
     * Stream to use as data source
     *
     * @param mixed $stream The stream
     * 
     * @return Promise
     */
    public function pipe(mixed $stream): Promise
    {
        $this->in_seq_no = -1;
        $this->out_seq_no = -1;
        $this->read_length = 0;
        $this->write_length = 0;
        return new Success(0);
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
        $buffer = yield $this->stream->getWriteBuffer($length+12);
        $this->out_seq_no++;
        $buffer->bufferWrite(pack('VV', $length + 12, $this->out_seq_no));
        return $buffer;
    }

    /**
     * Get read buffer asynchronously
     *
     * @return Generator
     */
    public function getReadBufferAsync(): \Generator
    {
        $buffer = yield $this->stream->getReadBuffer();
        $packet_length_data = yield $this->read(4);
        $packet_length = unpack('V', $packet_length_data)[1];
        $packet = yield $this->read($packet_length - 4);
        if (strrev(hash('crc32b', $packet_length_data . substr($packet, 0, -4), true)) !== substr($packet, -4)) {
            throw new Exception('CRC32 was not correct!');
        }
        $this->in_seq_no++;
        $in_seq_no = unpack('V', substr($packet, 0, 4))[1];
        if ($in_seq_no != $this->in_seq_no) {
            throw new Exception('Incoming seq_no mismatch');
        }

        return substr($packet, 4, $packet_length - 12);
        yield $buffer->bufferRead(4);

        return $buffer;
    }
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
     * Get read buffer asynchronously
     *
     * @return Promise
     */
    public function getReadBuffer(): Promise
    {
        return call([$this, 'getReadBufferAsync']);
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
}