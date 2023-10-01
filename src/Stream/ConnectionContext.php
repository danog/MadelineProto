<?php

declare(strict_types=1);

/**
 * Connection context.
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

namespace danog\MadelineProto\Stream;

use Amp\Cancellation;
use Amp\Socket\ConnectContext;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Tools;
use League\Uri\Http;
use Psr\Http\Message\UriInterface;

/**
 * Connection context class.
 *
 * Is responsible for maintaining state about a certain connection to a DC.
 * That includes the Stream chain that is required to use the connection, the connection URI, and other connection-related data.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class ConnectionContext
{
    /**
     * Whether to use a secure socket.
     *
     */
    private bool $secure = false;
    /**
     * Whether to use test servers.
     *
     */
    private bool $test = false;
    /**
     * Whether to use media servers.
     *
     */
    private bool $media = false;
    /**
     * Whether to use CDN servers.
     *
     */
    private bool $cdn = false;
    /**
     * The connection URI.
     *
     */
    private UriInterface $uri;
    /**
     * Whether this connection context will be used by the DNS client.
     *
     */
    private bool $isDns = false;
    /**
     * Socket context.
     *
     */
    private ConnectContext $socketContext;
    /**
     * Cancellation token.
     *
     */
    private ?Cancellation $cancellationToken = null;
    /**
     * The telegram DC ID.
     *
     */
    private int $dc = 0;
    /**
     * Whether to use IPv6.
     *
     */
    private bool $ipv6 = false;
    /**
     * An array of arrays containing an array with the stream name and the extra parameter to pass to it.
     *
     * @var list<array{0: class-string, 1: mixed}>
     */
    private array $nextStreams = [];
    /**
     * The current stream key.
     */
    private int $key = 0;
    /**
     * Set the socket context.
     */
    public function setSocketContext(ConnectContext $socketContext): self
    {
        $this->socketContext = $socketContext;
        return $this;
    }
    /**
     * Get the socket context.
     */
    public function getSocketContext(): ConnectContext
    {
        return $this->socketContext;
    }
    /**
     * Set the connection URI.
     *
     */
    public function setUri(string|UriInterface $uri): self
    {
        $this->uri = $uri instanceof UriInterface ? $uri : Http::new($uri);
        return $this;
    }
    /**
     * Get the URI as a string.
     */
    public function getStringUri(): string
    {
        return (string) $this->uri;
    }
    /**
     * Get the URI.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }
    /**
     * Set the cancellation token.
     */
    public function setCancellation(Cancellation $cancellationToken): self
    {
        $this->cancellationToken = $cancellationToken;
        return $this;
    }
    /**
     * Get the cancellation token.
     */
    public function getCancellation(): ?Cancellation
    {
        return $this->cancellationToken;
    }
    /**
     * Return a clone of the current connection context.
     */
    public function clone(): self
    {
        return clone $this;
    }
    /**
     * Set the CDN boolean.
     */
    public function setCDN(bool $cdn): self
    {
        $this->cdn = $cdn;
        return $this;
    }
    /**
     * Whether this is a test connection.
     */
    public function isTest(): bool
    {
        return $this->test;
    }
    /**
     * Whether this is a media connection.
     */
    public function isMedia(): bool
    {
        return $this->media;
    }
    /**
     * Whether this is a CDN connection.
     */
    public function isCDN(): bool
    {
        return $this->cdn;
    }
    /**
     * Whether this connection context will only be used by the DNS client.
     */
    public function isDns(): bool
    {
        return $this->isDns;
    }
    /**
     * Whether this connection context will only be used by the DNS client.
     */
    public function setIsDns(bool $isDns): self
    {
        $this->isDns = $isDns;
        return $this;
    }
    /**
     * Set the secure boolean.
     */
    public function secure(bool $secure): self
    {
        $this->secure = $secure;
        return $this;
    }
    /**
     * Whether to use TLS with socket connections.
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }
    /**
     * Set the DC ID.
     *
     */
    public function setDc(int $dc): self
    {
        $this->dc = $dc;
        $this->media = DataCenter::isMedia($dc);
        $this->test = DataCenter::isTest($dc);
        return $this;
    }
    /**
     * Get the int DC ID.
     */
    public function getDc(): int
    {
        return $this->dc;
    }
    /**
     * Whether to use ipv6.
     */
    public function setIpv6(bool $ipv6): self
    {
        $this->ipv6 = $ipv6;
        return $this;
    }
    /**
     * Whether to use ipv6.
     */
    public function getIpv6(): bool
    {
        return $this->ipv6;
    }
    /**
     * Add a stream to the stream chain.
     *
     * @param class-string $streamName
     */
    public function addStream(string $streamName, $extra = null): self
    {
        $this->nextStreams[] = [$streamName, $extra];
        $this->key = \count($this->nextStreams) - 1;
        return $this;
    }
    /**
     * Check if connected via HTTP.
     *
     * @return boolean
     */
    public function isHttp(): bool
    {
        return \in_array(Tools::end($this->nextStreams)[0], [HttpStream::class, HttpsStream::class], true);
    }
    /**
     * Check if has stream within stream chain.
     *
     * @param string $stream Stream name
     */
    public function hasStreamName(string $stream): bool
    {
        foreach ($this->nextStreams as [$name]) {
            if ($name === $stream) {
                return true;
            }
        }
        return false;
    }
    /**
     * Get a stream from the stream chain.
     */
    public function getStream(string $buffer = ''): StreamInterface
    {
        [$clazz, $extra] = $this->nextStreams[$this->key--];
        $obj = new $clazz();
        if ($obj instanceof ProxyStreamInterface) {
            $obj->setExtra($extra);
        }
        $obj->connect($this, $buffer);
        return $obj;
    }
    /**
     * Get the inputClientProxy proxy MTProto object.
     *
     */
    public function getInputClientProxy(): array|null
    {
        foreach ($this->nextStreams as $couple) {
            [$streamName, $extra] = $couple;
            if ($streamName === ObfuscatedStream::class && isset($extra['address'])) {
                $extra['_'] = 'inputClientProxy';
                return $extra;
            }
        }
        return null;
    }
    /**
     * Get a description "name" of the context.
     */
    public function getName(): string
    {
        $string = $this->getStringUri();
        if ($this->isSecure()) {
            $string .= ' (TLS)';
        }
        $string .= $this->isTest() ? ' test' : ' main';
        $string .= ' DC ';
        $string .= $this->getDc();
        $string .= ', via ';
        $string .= $this->getIpv6() ? 'ipv6' : 'ipv4';
        $string .= ' using ';
        foreach (array_reverse($this->nextStreams) as $k => $stream) {
            if ($k) {
                $string .= ' => ';
            }
            $string .= preg_replace('/.*\\\\/', '', $stream[0]);
            if ($stream[1] && $stream[0] !== DefaultStream::class) {
                $string .= ' ('.json_encode($stream[1]).')';
            }
        }
        return $string;
    }
    /**
     * Returns a representation of the context.
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
