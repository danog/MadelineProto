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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
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

/**
 * Default stream wrapper.
 *
 * Manages reading data in chunks
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class DefaultStream extends Socket implements RawStreamInterface
{
    use RawStream;
    private $stream;

    public function enableCrypto(): Promise
    {
        return $this->stream->enableCrypto();
    }

    public function __construct()
    {
    }

    public function connectAsync(\danog\MadelineProto\Stream\ConnectionContext $ctx, string $header = ''): \Generator
    {
        if ($ctx->isSecure()) {
            $this->stream = yield cryptoConnect($ctx->getStringUri(), $ctx->getSocketContext(), $ctx->getCancellationToken());
        } else {
            $this->stream = yield connect($ctx->getStringUri(), $ctx->getSocketContext());
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

    public static function getName(): string
    {
        return __CLASS__;
    }
}
