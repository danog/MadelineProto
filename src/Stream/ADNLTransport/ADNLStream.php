<?php

declare(strict_types=1);

/**
 * ADNL stream wrapper.
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

namespace danog\MadelineProto\Stream\ADNLTransport;

use Amp\Socket\Socket;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Tools;

/**
 * ADNL stream wrapper.
 *
 * Manages ADNL envelope
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class ADNLStream implements BufferedStreamInterface, MTProtoBufferInterface
{
    private $stream;
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $this->stream = $ctx->getStream($header);
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
        $length += 64;
        $buffer = $this->stream->getWriteBuffer($length + 4, $append);
        $buffer->bufferWrite(pack('V', $length));
        $this->stream->startWriteHash();
        $this->stream->checkWriteHash($length - 32);
        $buffer->bufferWrite(Tools::random(32));
        return $buffer;
    }
    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     */
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        $buffer = $this->stream->getReadBuffer($l);
        $length = unpack('V', $buffer->bufferRead(4))[1] - 32;
        $this->stream->startReadHash();
        $this->stream->checkReadHash($length);
        $buffer->bufferRead(32);
        $length -= 32;
        return $buffer;
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
    public static function getName(): string
    {
        return self::class;
    }
}
