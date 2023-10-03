<?php

declare(strict_types=1);

/**
 * TCP full stream wrapper.
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

namespace danog\MadelineProto\Stream\MTProtoTransport;

use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\Common\HashedBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * TCP full stream wrapper.
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class FullStream implements BufferedStreamInterface, MTProtoBufferInterface
{
    private $stream;
    private $in_seq_no = -1;
    private $out_seq_no = -1;
    /**
     * Stream to use as data source.
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $this->in_seq_no = -1;
        $this->out_seq_no = -1;
        $this->stream = new HashedBufferedStream();
        $this->stream->setExtra('crc32b_rev');
        $this->stream->connect($ctx, $header);
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
        $this->stream->startWriteHash();
        $this->stream->checkWriteHash($length + 8);
        $buffer = $this->stream->getWriteBuffer($length + 12, $append);
        $this->out_seq_no++;
        $buffer->bufferWrite(pack('VV', $length + 12, $this->out_seq_no));
        return $buffer;
    }
    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     */
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        $this->stream->startReadHash();
        $buffer = $this->stream->getReadBuffer($l);
        $read_length = unpack('V', $buffer->bufferRead(4))[1];
        $length = $read_length - 12;
        $this->stream->checkReadHash($read_length - 8);
        $this->in_seq_no++;
        $in_seq_no = unpack('V', $buffer->bufferRead(4))[1];
        if ($in_seq_no != $this->in_seq_no) {
            throw new Exception('Incoming seq_no mismatch');
        }
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
