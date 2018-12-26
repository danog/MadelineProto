<?php
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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream;

use Amp\CancellationToken;
use Amp\Promise;
use Amp\Socket\ClientConnectContext;
use Amp\Uri\Uri;
use function Amp\call;

/**
 * Connection context class.
 *
 * Is responsible for maintaining state about a certain connection to a DC.
 * That includes the Stream chain that is required to use the connection, the connection URI, and other connection-related data.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ConnectionContext
{
    /**
     * Whether to use a secure socket.
     *
     * @var bool
     */
    private $secure = false;
    /**
     * Whether to use test servers.
     *
     * @var bool
     */
    private $test = false;
    /**
     * The connection URI.
     *
     * @var \Amp\Uri\Uri
     */
    private $uri;
    /**
     * Socket context.
     *
     * @var \Amp\Socket\ClientConnectionContext
     */
    private $socketContext;
    /**
     * Cancellation token.
     *
     * @var \Amp\CancellationToken
     */
    private $cancellationToken;
    /**
     * The telegram DC ID.
     *
     * @var int
     */
    private $dc = 0;
    /**
     * Whether to use IPv6.
     *
     * @var bool
     */
    private $ipv6 = false;
    /**
     * An array of arrays containing an array with the stream name and the extra parameter to pass to it.
     *
     * @var array<array<string, any>>
     */
    private $nextStreams = [];
    /**
     * The current stream key.
     *
     * @var int
     */
    private $key = 0;

    /**
     * Set the socket context.
     *
     * @param ClientConnectContext $socketContext
     *
     * @return self
     */
    public function setSocketContext(ClientConnectContext $socketContext): self
    {
        $this->socketContext = $socketContext;

        return $this;
    }

    /**
     * Get the socket context.
     *
     * @return ClientConnectContext
     */
    public function getSocketContext(): ClientConnectContext
    {
        return $this->socketContext;
    }

    /**
     * Set the connection URI.
     *
     * @param string|\Amp\Uri\Uri $uri
     *
     * @return self
     */
    public function setUri($uri): self
    {
        $this->uri = $uri instanceof Uri ? $uri : new Uri($uri);

        return $this;
    }

    /**
     * Get the URI as a string.
     *
     * @return string
     */
    public function getStringUri(): string
    {
        return (string) $this->uri;
    }

    /**
     * Get the URI.
     *
     * @return \Amp\Uri\Uri
     */
    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * Set the cancellation token.
     *
     * @param CancellationToken $cancellationToken
     *
     * @return self
     */
    public function setCancellationToken($cancellationToken): self
    {
        $this->cancellationToken = $cancellationToken;

        return $this;
    }

    /**
     * Get the cancellation token.
     *
     * @return CancellationToken
     */
    public function getCancellationToken()
    {
        return $this->cancellationToken;
    }

    /**
     * Set the secure boolean.
     *
     * @param bool $secure
     *
     * @return self
     */
    public function setTest(bool $test): self
    {
        $this->test = $test;

        return $this;
    }

    /**
     * Whether to use TLS with socket connections.
     *
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->test;
    }

    /**
     * Set the secure boolean.
     *
     * @param bool $secure
     *
     * @return self
     */
    public function secure(bool $secure): self
    {
        $this->secure = $secure;

        return $this;
    }

    /**
     * Whether to use TLS with socket connections.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Set the DC ID.
     *
     * @param string|int $dc
     *
     * @return self
     */
    public function setDc($dc): self
    {
        $this->dc = $dc;

        return $this;
    }

    /**
     * Get the DC ID.
     *
     * @return string|int
     */
    public function getDc()
    {
        return $this->dc;
    }

    /**
     * Get the int DC ID.
     *
     * @return string|int
     */
    public function getIntDc()
    {
        $dc = intval($this->dc);
        if ($this->test) {
            $dc += 10000;
        }
        if (strpos($this->dc, '_media')) {
            $dc = -$dc;
        }

        return $dc;
    }

    /**
     * Whether to use ipv6.
     *
     * @param bool $ipv6
     *
     * @return self
     */
    public function setIpv6(bool $ipv6): self
    {
        $this->ipv6 = $ipv6;

        return $this;
    }

    /**
     * Whether to use ipv6.
     *
     * @return bool
     */
    public function getIpv6(): bool
    {
        return $this->ipv6;
    }

    /**
     * Set the ipv6 boolean.
     *
     * @return self
     */
    public function getCtx(): self
    {
        return clone $this;
    }

    /**
     * Add a stream to the stream chain.
     *
     * @param string $streamName
     * @param any    $extra
     *
     * @return self
     */
    public function addStream(string $streamName, $extra = null): self
    {
        $this->nextStreams[] = [$streamName, $extra];
        $this->key = count($this->nextStreams) - 1;

        return $this;
    }

    /**
     * Get the current stream name from the stream chain.
     *
     * @return string
     */
    public function getStreamName(): string
    {
        return $this->nextStreams[$this->key][0];
    }

    /**
     * Get a stream from the stream chain.
     *
     * @return Promise
     */
    public function getStream(string $buffer = ''): Promise
    {
        return call([$this, 'getStreamAsync'], $buffer);
    }

    /**
     * Get a stream from the stream chain.
     *
     * @internal Generator func
     *
     * @return \Generator
     */
    public function getStreamAsync(string $buffer = ''): \Generator
    {
        list($clazz, $extra) = $this->nextStreams[$this->key--];
        $obj = new $clazz();
        if ($obj instanceof ProxyStreamInterface) {
            $obj->setExtra($extra);
        }
        yield $obj->connect($this, $buffer);

        return $obj;
    }

    /**
     * Get a description "name" of the context.
     *
     * @return string
     */
    public function getName(): string
    {
        $string = $this->getStringUri();
        if ($this->isSecure()) {
            $string .= ' (TLS)';
        }
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
            if ($stream[1]) {
                $string .= ' ('.json_encode($stream[1]).')';
            }
        }

        return $string;
    }

    /**
     * Returns a representation of the context.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
