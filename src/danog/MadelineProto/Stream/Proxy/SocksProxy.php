<?php

/**
 * Socks5 stream wrapper.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Proxy;

use Amp\Promise;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\EncryptableSocket;
use danog\MadelineProto\Stream\Async\RawStream;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

use function danog\MadelineProto\logger;

/**
 * Socks5 stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class SocksProxy implements RawProxyStreamInterface, BufferedProxyStreamInterface
{
    private const REPS = [0 => 'succeeded', 1 => 'general SOCKS server failure', 2 => 'connection not allowed by ruleset', 3 => 'Network unreachable', 4 => 'Host unreachable', 5 => 'Connection refused', 6 => 'TTL expired', 7 => 'Command not supported', 8 => 'Address type not supported'];
    use RawStream;
    /**
     * Stream.
     *
     * @var RawStreamInterface
     */
    protected $stream;
    private $extra;
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     */
    public function connect(ConnectionContext $ctx, string $header = ''): \Generator
    {
        $ctx = $ctx->getCtx();
        $uri = $ctx->getUri();
        $secure = $ctx->isSecure();
        if ($secure) {
            $ctx->setSocketContext($ctx->getSocketContext()->withTlsContext(new ClientTlsContext($uri->getHost())));
        }
        $ctx->setUri('tcp://'.$this->extra['address'].':'.$this->extra['port'])->secure(false);
        $methods = \chr(0);
        if (isset($this->extra['username']) && isset($this->extra['password'])) {
            $methods .= \chr(2);
        }
        $this->stream = (yield from $ctx->getStream(\chr(5).\chr(\strlen($methods)).$methods));
        $l = 2;
        $buffer = yield $this->stream->getReadBuffer($l);
        $version = \ord(yield $buffer->bufferRead(1));
        $method = \ord(yield $buffer->bufferRead(1));
        if ($version !== 5) {
            throw new \danog\MadelineProto\Exception("Wrong SOCKS5 version: {$version}");
        }
        if ($method === 2) {
            $auth = \chr(1).\chr(\strlen($this->extra['username'])).$this->extra['username'].\chr(\strlen($this->extra['password'])).$this->extra['password'];
            yield $this->stream->write($auth);
            $buffer = yield $this->stream->getReadBuffer($l);
            $version = \ord(yield $buffer->bufferRead(1));
            $result = \ord(yield $buffer->bufferRead(1));
            if ($version !== 1) {
                throw new \danog\MadelineProto\Exception("Wrong authorized SOCKS version: {$version}");
            }
            if ($result !== 0) {
                throw new \danog\MadelineProto\Exception("Wrong authorization status: {$version}");
            }
        } elseif ($method !== 0) {
            throw new \danog\MadelineProto\Exception("Wrong method: {$method}");
        }
        $payload = \pack('C3', 0x5, 0x1, 0x0);
        try {
            $ip = \inet_pton($uri->getHost());
            $payload .= $ip ? \pack('C1', \strlen($ip) === 4 ? 0x1 : 0x4).$ip : \pack('C2', 0x3, \strlen($uri->getHost())).$uri->getHost();
        } catch (\danog\MadelineProto\Exception $e) {
            $payload .= \pack('C2', 0x3, \strlen($uri->getHost())).$uri->getHost();
        }
        $payload .= \pack('n', $uri->getPort());
        yield $this->stream->write($payload);
        $l = 4;
        $buffer = yield $this->stream->getReadBuffer($l);
        $version = \ord(yield $buffer->bufferRead(1));
        if ($version !== 5) {
            throw new \danog\MadelineProto\Exception("Wrong SOCKS5 version: {$version}");
        }
        $rep = \ord(yield $buffer->bufferRead(1));
        if ($rep !== 0) {
            $rep = self::REPS[$rep] ?? $rep;
            throw new \danog\MadelineProto\Exception("Wrong SOCKS5 rep: {$rep}");
        }
        $rsv = \ord(yield $buffer->bufferRead(1));
        if ($rsv !== 0) {
            throw new \danog\MadelineProto\Exception("Wrong socks5 final RSV: {$rsv}");
        }
        switch (\ord(yield $buffer->bufferRead(1))) {
            case 1:
                $buffer = yield $this->stream->getReadBuffer($l);
                $ip = \inet_ntop(yield $buffer->bufferRead(4));
                break;
            case 4:
                $l = 16;
                $buffer = yield $this->stream->getReadBuffer($l);
                $ip = \inet_ntop(yield $buffer->bufferRead(16));
                break;
            case 3:
                $l = 1;
                $buffer = yield $this->stream->getReadBuffer($l);
                $length = \ord(yield $buffer->bufferRead(1));
                $buffer = yield $this->stream->getReadBuffer($length);
                $ip = yield $buffer->bufferRead($length);
                break;
        }
        $l = 2;
        $buffer = yield $this->stream->getReadBuffer($l);
        $port = \unpack('n', yield $buffer->bufferRead(2))[1];
        logger('Connected to '.$ip.':'.$port.' via socks5');
        if ($secure) {
            yield $this->getSocket()->setupTls();
        }
        if (\strlen($header)) {
            yield (yield $this->stream->getWriteBuffer(\strlen($header)))->bufferWrite($header);
        }
    }
    /**
     * Async close.
     *
     */
    public function disconnect(): \Amp\Promise
    {
        return $this->stream->disconnect();
    }
    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     *
     */
    public function getWriteBuffer(int $length, string $append = ''): Promise
    {
        return $this->stream->getWriteBuffer($length, $append);
    }
    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     */
    public function getReadBuffer(&$length): Promise
    {
        return $this->stream->getReadBuffer($length);
    }
    public function read(): Promise
    {
        return $this->stream->read();
    }
    public function write(string $data): Promise
    {
        return $this->stream->write($data);
    }
    /**
     * Sets proxy data.
     *
     * @param array $extra Proxy data
     *
     */
    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }
    /**
     * {@inheritDoc}
     *
     */
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
    }
    /**
     * {@inheritdoc}
     *
     */
    public function getSocket(): EncryptableSocket
    {
        return $this->stream->getSocket();
    }
    public static function getName(): string
    {
        return __CLASS__;
    }
}
