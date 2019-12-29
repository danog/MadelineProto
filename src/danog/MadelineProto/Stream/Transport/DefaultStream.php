<?php
/**
 * Default stream wrapper.
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
use Amp\Socket\ClientTlsContext;
use Amp\Socket\EncryptableSocket;
use Amp\Socket\Socket;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

use function Amp\Socket\connector;

/**
 * Default stream wrapper.
 *
 * Manages reading data in chunks
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class DefaultStream implements
    RawStreamInterface,
    ProxyStreamInterface
{
    use RawStream;
    /**
     * Socket.
     *
     * @var EncryptableSocket
     */
    private $stream;

    /**
     * Connector.
     *
     * @var Connector
     */
    private $connector;

    public function setupTls(?CancellationToken $cancellationToken = null): \Amp\Promise
    {
        return $this->stream->setupTls($cancellationToken);
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function connectGenerator(\danog\MadelineProto\Stream\ConnectionContext $ctx, string $header = ''): \Generator
    {
        $ctx = $ctx->getCtx();
        $uri = $ctx->getUri();
        $secure = $ctx->isSecure();
        if ($secure) {
            $ctx->setSocketContext(
                $ctx->getSocketContext()->withTlsContext(
                    new ClientTlsContext($uri->getHost())
                )
            );
        }

        $this->stream = yield ($this->connector ?? connector())->connect((string) $uri, $ctx->getSocketContext(), $ctx->getCancellationToken());
        if ($secure) {
            yield $this->stream->setupTls();
        }
        yield $this->stream->write($header);
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
     * Close.
     *
     * @return void
     */
    public function disconnect()
    {
        try {
            if ($this->stream) {
                $this->stream->close();
                $this->stream = null;
            }
        } catch (\Throwable $e) {
            \danog\MadelineProto\Logger::log('Got exception while closing stream: '.$e->getMessage());
        }
    }

    /**
     * Close.
     *
     * @return void
     */
    public function close()
    {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     *
     * @return EncryptableSocket
     */
    public function getSocket(): EncryptableSocket
    {
        return $this->stream;
    }

    public function setExtra($extra)
    {
        $this->connector = $extra;
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}
