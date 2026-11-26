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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream;

use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use Amp\Socket\ConnectContext;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\PremadeStream;
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
     * The connection URI.
     *
     */
    private UriInterface $uri;
    /**
     * String form of the connection URI (avoids PSR-7 UriInterface::__toString taint false positives on DC URIs).
     */
    private string $uriString = '';
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
     * Stream classes in the stream chain.
     *
     * @var list<class-string>
     */
    private array $streamClasses = [];
    /**
     * Extra parameters paired by index with $streamClasses.
     *
     * @var list<mixed>
     */
    private array $streamExtras = [];
    /**
     * MTProto inputClientProxy payload for ObfuscatedStream, if present.
     */
    private ?array $inputClientProxy = null;
    /**
     * Premade stream payload for PremadeStream, kept separate from proxy extras.
     */
    private ?ReadableStream $premadeStream = null;
    /**
     * The current stream key.
     */
    private int $key = 0;
    /**
     * Set the socket context.
     *
     * @psalm-external-mutation-free
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
        // Normalized, like the host of getUri() (the TLS SNI of DefaultStream)
        $this->uriString = self::uriToString($this->uri);
        return $this;
    }

    /**
     * The addresses of the DCs, the proxies and the DoH servers are the settings, not input: a connection must go there.
     *
     * @psalm-taint-escape ssrf
     */
    private static function uriToString(UriInterface $uri): string
    {
        return (string) $uri;
    }
    /**
     * Get the URI as a string.
     *
     * @psalm-mutation-free
     */
    public function getStringUri(): string
    {
        return $this->uriString;
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-mutation-free
     */
    public function clone(): self
    {
        return clone $this;
    }
    /**
     * Whether this is a test connection.
     */
    public function isTest(): bool
    {
        return $this->test;
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
     *
     * @psalm-external-mutation-free
     */
    public function setIsDns(bool $isDns): self
    {
        $this->isDns = $isDns;
        return $this;
    }
    /**
     * Set the secure boolean.
     *
     * @psalm-external-mutation-free
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
     * @psalm-external-mutation-free
     */
    public function setDc(int $dc): self
    {
        $this->dc = $dc;
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
     */
    public function addStream(string $streamName, $extra = null): self
    {
        if ($streamName === PremadeStream::class) {
            \assert($extra instanceof ReadableStream);
            return $this->addPremadeStream($extra);
        }
        $this->streamClasses[] = $streamName;
        $this->streamExtras[] = $extra;
        if ($streamName === ObfuscatedStream::class && \is_array($extra) && isset($extra['address'])) {
            $extra['_'] = 'inputClientProxy';
            $this->inputClientProxy = $extra;
        }
        $this->key = \count($this->streamClasses) - 1;
        return $this;
    }
    /**
     * Add a premade readable stream to the stream chain without mixing it into proxy configuration extras.
     *
     * @psalm-external-mutation-free
     */
    public function addPremadeStream(ReadableStream $stream): self
    {
        $this->streamClasses[] = PremadeStream::class;
        $this->streamExtras[] = null;
        $this->premadeStream = $stream;
        $this->key = \count($this->streamClasses) - 1;
        return $this;
    }

    /**
     * Check if connected via HTTP.
     *
     * @return boolean
     *
     * @psalm-mutation-free
     */
    public function isHttp(): bool
    {
        return \in_array(Tools::end($this->streamClasses), [HttpStream::class, HttpsStream::class], true);
    }
    /**
     * Check if has stream within stream chain.
     *
     * @param string $stream Stream name
     *
     * @psalm-mutation-free
     */
    public function hasStreamName(string $stream): bool
    {
        return \in_array($stream, $this->streamClasses, true);
    }
    /**
     * Get a stream from the stream chain.
     */
    public function getStream(string $buffer = ''): StreamInterface
    {
        $idx = $this->key--;
        $clazz = $this->streamClasses[$idx];
        $extra = $this->streamExtras[$idx];
        if ($clazz === PremadeStream::class) {
            $obj = new PremadeStream();
            $obj->setExtra($this->premadeStream);
            $obj->connect($this, $buffer);
            return $obj;
        }
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
     * @psalm-mutation-free
     */
    public function getInputClientProxy(): array|null
    {
        return $this->inputClientProxy;
    }
    /**
     * Get a description "name" of the context.
     *
     * @psalm-mutation-free
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
        $count = \count($this->streamClasses);
        for ($i = $count - 1; $i >= 0; $i--) {
            if ($i < $count - 1) {
                $string .= ' => ';
            }
            $class = $this->streamClasses[$i];
            $string .= preg_replace('/.*\\\\/', '', $class) ?? $class;
            if (!empty($this->streamExtras[$i]) && $class !== DefaultStream::class) {
                $string .= ' ('.json_encode($this->streamExtras[$i]).')';
            }
        }
        return $string;
    }
    /**
     * Returns a representation of the context.
     *
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
