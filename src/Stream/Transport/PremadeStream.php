<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Transport;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\Socket\Socket;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * Premade stream wrapper.
 *
 * Manages reading data in chunks
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements ProxyStreamInterface<Socket>
 */
final class PremadeStream implements RawStreamInterface, ProxyStreamInterface
{
    private Socket|ReadableStream|null $stream = null;
    public function __construct()
    {
    }
    public function setupTls(?Cancellation $cancellationToken = null): void
    {
        \assert($this->stream instanceof Socket);
        $this->stream->setupTls($cancellationToken);
    }
    public function getStream()
    {
        return $this->stream;
    }
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        if ($header !== '') {
            $this->stream->write($header);
        }
    }
    /**
     * Async chunked read.
     */
    public function read(?Cancellation $cancellation = null): ?string
    {
        return $this->stream ? $this->stream->read($cancellation) : null;
    }
    /**
     * Async write.
     *
     * @param string $data Data to write
     */
    public function write(string $data): void
    {
        if (!$this->stream) {
            throw new ClosedException('MadelineProto stream was disconnected');
        }
        \assert($this->stream instanceof WritableStream);
        /** @psalm-suppress UndefinedInterfaceMethod */
        $this->stream->write($data);
    }
    /**
     * Async close.
     */
    public function disconnect(): void
    {
        try {
            if ($this->stream) {
                if (method_exists($this->stream, 'close')) {
                    $this->stream->close();
                }
                $this->stream = null;
            }
        } catch (Throwable $e) {
            Logger::log('Got exception while closing stream: '.$e->getMessage());
        }
    }
    public function close(): void
    {
        $this->disconnect();
    }
    public function getSocket(): Socket
    {
        Assert::true($this->stream instanceof Socket);
        return $this->stream;
    }
    public function setExtra(mixed $extra): void
    {
        $this->stream = $extra;
    }
    public static function getName(): string
    {
        return self::class;
    }
}
