<?php
/**
 * Premade stream wrapper.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Transport;

use Amp\ByteStream\ClosedException;
use Amp\CancellationToken;
use Amp\Promise;
use Amp\Socket\Socket;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * Premade stream wrapper.
 *
 * Manages reading data in chunks
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class PremadeStream implements RawStreamInterface, ProxyStreamInterface
{
    use RawStream;
    private $stream;

    public function __construct()
    {
    }

    public function setupTls(?CancellationToken $cancellationToken = null): \Amp\Promise
    {
        return $this->stream->setupTls($cancellationToken);
    }


    public function getStream()
    {
        return $this->stream;
    }

    public function connectGenerator(ConnectionContext $ctx, string $header = ''): \Generator
    {
        if ($header !== '') {
            yield $this->stream->write($header);
        }
    }

    /**
     * Async chunked read.
     *
     * @return Promise
     */
    public function read(): Promise
    {
        return $this->stream ? $this->stream->read() : new \Amp\Success(null);
    }

    /**
     * Async write.
     *
     * @param string $data Data to write
     *
     * @return Promise
     */
    public function write(string $data): Promise
    {
        if (!$this->stream) {
            throw new ClosedException("MadelineProto stream was disconnected");
        }
        return $this->stream->write($data);
    }

    /**
     * Async close.
     *
     * @return Generator
     */
    public function disconnect()
    {
        try {
            if ($this->stream) {
                if (\method_exists($this->stream, 'close')) {
                    $this->stream->close();
                }
                $this->stream = null;
            }
        } catch (\Throwable $e) {
            \danog\MadelineProto\Logger::log('Got exception while closing stream: '.$e->getMessage());
        }
    }

    public function close()
    {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Amp\Socket\Socket
     */
    public function getSocket(): Socket
    {
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtra($extra)
    {
        $this->stream = $extra;
    }
    public static function getName(): string
    {
        return __CLASS__;
    }
}
