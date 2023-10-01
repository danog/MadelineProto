<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Proxy;

use Amp\Cancellation;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawProxyStreamInterface;
use danog\MadelineProto\Stream\RawStreamInterface;
use Webmozart\Assert\Assert;

/**
 * Socks5 stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements RawProxyStreamInterface<array{address: string, port: int, username?: string, password?: string}>
 * @implements BufferedProxyStreamInterface<array{address: string, port: int, username?: string, password?: string}>
 */
final class SocksProxy implements RawProxyStreamInterface, BufferedProxyStreamInterface
{
    private const REPS = [0 => 'succeeded', 1 => 'general SOCKS server failure', 2 => 'connection not allowed by ruleset', 3 => 'Network unreachable', 4 => 'Host unreachable', 5 => 'Connection refused', 6 => 'TTL expired', 7 => 'Command not supported', 8 => 'Address type not supported'];
    /**
     * Stream.
     *
     */
    protected RawStreamInterface&BufferedStreamInterface $stream;
    private $extra;
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     */
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $ctx = $ctx->clone();
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
        $this->stream = $ctx->getStream(\chr(5).\chr(\strlen($methods)).$methods);
        Assert::true($this->stream instanceof BufferedStreamInterface);
        Assert::true($this->stream instanceof RawStreamInterface);
        $l = 2;
        $buffer = $this->stream->getReadBuffer($l);
        $version = \ord($buffer->bufferRead(1));
        $method = \ord($buffer->bufferRead(1));
        if ($version !== 5) {
            throw new Exception("Wrong SOCKS5 version: {$version}");
        }
        if ($method === 2) {
            $auth = \chr(1).\chr(\strlen($this->extra['username'])).$this->extra['username'].\chr(\strlen($this->extra['password'])).$this->extra['password'];
            $this->stream->write($auth);
            $buffer = $this->stream->getReadBuffer($l);
            $version = \ord($buffer->bufferRead(1));
            $result = \ord($buffer->bufferRead(1));
            if ($version !== 1) {
                throw new Exception("Wrong authorized SOCKS version: {$version}");
            }
            if ($result !== 0) {
                throw new Exception("Wrong authorization status: {$version}");
            }
        } elseif ($method !== 0) {
            throw new Exception("Wrong method: {$method}");
        }
        $payload = pack('C3', 0x5, 0x1, 0x0);
        try {
            $ip = inet_pton($uri->getHost());
            $payload .= $ip ? pack('C1', \strlen($ip) === 4 ? 0x1 : 0x4).$ip : pack('C2', 0x3, \strlen($uri->getHost())).$uri->getHost();
        } catch (Exception $e) {
            $payload .= pack('C2', 0x3, \strlen($uri->getHost())).$uri->getHost();
        }
        $payload .= pack('n', $uri->getPort());
        $this->stream->write($payload);
        $l = 4;
        $buffer = $this->stream->getReadBuffer($l);
        $version = \ord($buffer->bufferRead(1));
        if ($version !== 5) {
            throw new Exception("Wrong SOCKS5 version: {$version}");
        }
        $rep = \ord($buffer->bufferRead(1));
        if ($rep !== 0) {
            $rep = self::REPS[$rep] ?? $rep;
            throw new Exception("Wrong SOCKS5 rep: {$rep}");
        }
        $rsv = \ord($buffer->bufferRead(1));
        if ($rsv !== 0) {
            throw new Exception("Wrong socks5 final RSV: {$rsv}");
        }
        switch (\ord($buffer->bufferRead(1))) {
            case 1:
                $buffer = $this->stream->getReadBuffer($l);
                $ip = inet_ntop($buffer->bufferRead(4));
                break;
            case 4:
                $l = 16;
                $buffer = $this->stream->getReadBuffer($l);
                $ip = inet_ntop($buffer->bufferRead(16));
                break;
            case 3:
                $l = 1;
                $buffer = $this->stream->getReadBuffer($l);
                $length = \ord($buffer->bufferRead(1));
                $buffer = $this->stream->getReadBuffer($length);
                $ip = $buffer->bufferRead($length);
                break;
        }
        $l = 2;
        $buffer = $this->stream->getReadBuffer($l);
        $port = unpack('n', $buffer->bufferRead(2))[1];
        Logger::log(['Connected to '.$ip.':'.$port.' via socks5']);
        if ($secure) {
            $this->getSocket()->setupTls();
        }
        if (\strlen($header)) {
            ($this->stream->getWriteBuffer(\strlen($header)))->bufferWrite($header);
        }
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
        return $this->stream->getWriteBuffer($length, $append);
    }
    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     */
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        return $this->stream->getReadBuffer($length);
    }
    public function read(?Cancellation $token = null): ?string
    {
        return $this->stream->read($token);
    }
    public function write(string $data): void
    {
        $this->stream->write($data);
    }
    /**
     * Sets proxy data.
     *
     * @param array $extra Proxy data
     */
    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }
    /**
     * {@inheritDoc}
     */
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
    }
    /**
     * {@inheritdoc}
     */
    public function getSocket(): Socket
    {
        return $this->stream->getSocket();
    }
    public static function getName(): string
    {
        return self::class;
    }
}
