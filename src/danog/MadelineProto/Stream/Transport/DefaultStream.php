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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Transport;

use Amp\Promise;
use Amp\Socket\Socket;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\RawStreamInterface;
use function Amp\Socket\connect;
use function Amp\Socket\cryptoConnect;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use Amp\ByteStream\ClosedException;

/**
 * Default stream wrapper.
 *
 * Manages reading data in chunks
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class DefaultStream extends Socket implements RawStreamInterface, ProxyStreamInterface
{
    use RawStream;
    private $stream;
    private $connector = 'Amp\\Socket\\connect';
    private $cryptoConnector = 'Amp\\Socket\\cryptoConnect';
    
    public function __construct()
    {
    }

    public function enableCrypto(ClientTlsContext $tlsContext = null): \Amp\Promise
    {
        return $this->enableCrypto($tlsContext);
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function connectAsync(\danog\MadelineProto\Stream\ConnectionContext $ctx, string $header = ''): \Generator
    {
        if ($ctx->isSecure()) {
            $this->stream = yield ($this->cryptoConnector)($ctx->getStringUri(), $ctx->getSocketContext(), null, $ctx->getCancellationToken());
        } else {
            $this->stream = yield ($this->connector)($ctx->getStringUri(), $ctx->getSocketContext(), $ctx->getCancellationToken());
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
     * Async close.
     *
     * @return Generator
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
        } catch (\Exception $e) {
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
    public function getSocket(): \Amp\Socket\Socket
    {
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtra($extra)
    {
        list($this->connector, $this->cryptoConnector) = $extra;
    }
    public static function getName(): string
    {
        return __CLASS__;
    }
}
