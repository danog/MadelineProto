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
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;

/**
 * Obfuscated2 AMP stream wrapper
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class IntermediateStream implements BufferedStreamInterface
{
    use BufferedStream;
    private $stream;

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
        $buffer = yield $this->stream->getWriteBuffer(4);
        yield $buffer->bufferWrite(str_repeat(chr(238), 4));
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
        $buffer = yield $this->stream->getWriteBuffer($length+4);
        yield $buffer->bufferWrite(pack('V', $length));
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
        yield $buffer->bufferRead(4);

        return $buffer;
    }
}