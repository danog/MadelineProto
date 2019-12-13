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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream;

use Amp\CancellationToken;
use Amp\Socket\ConnectContext;
use Amp\Uri\Uri;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;

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
     * Whether to use media servers.
     *
     * @var bool
     */
    private $media = false;
    /**
     * Whether to use CDN servers.
     *
     * @var bool
     */
    private $cdn = false;
    /**
     * The connection URI.
     *
     * @var \Amp\Uri\Uri
     */
    private $uri;
    /**
     * Whether this connection context will be used by the DNS client.
     *
     * @var bool
     */
    private $isDns = false;
    /**
     * Socket context.
     *
     * @var \Amp\Socket\ConnectContext
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
     * Read callback.
     *
     * @var callable
     */
    private $readCallback;

    /**
     * Set the socket context.
     *
     * @param ConnectContext $socketContext
     *
     * @return self
     */
    public function setSocketContext(ConnectContext $socketContext): self
    {
        $this->socketContext = $socketContext;

        return $this;
    }

    /**
     * Get the socket context.
     *
     * @return ConnectContext
     */
    public function getSocketContext(): ConnectContext
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
     * Return a clone of the current connection context.
     *
     * @return self
     */
    public function getCtx(): self
    {
        return clone $this;
    }
    /**
     * Set the test boolean.
     *
     * @param bool $test
     *
     * @return self
     */
    public function setTest(bool $test): self
    {
        $this->test = $test;

        return $this;
    }

    /**
     * Whether this is a test connection.
     *
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->test;
    }
    /**
     * Whether this is a media connection.
     *
     * @return bool
     */
    public function isMedia(): bool
    {
        return $this->media;
    }

    /**
     * Whether this is a CDN connection.
     *
     * @return bool
     */
    public function isCDN(): bool
    {
        return $this->cdn;
    }

    /**
     * Whether this connection context will only be used by the DNS client.
     *
     * @return bool
     */
    public function isDns(): bool
    {
        return $this->isDns;
    }

    /**
     * Whether this connection context will only be used by the DNS client.
     *
     * @param boolean $isDns
     * @return self
     */
    public function setIsDns(bool $isDns): self
    {
        $this->isDns = $isDns;
        return $this;
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
        $int = \intval($dc);
        if (!(1 <= $int && $int <= 1000)) {
            throw new Exception("Invalid DC id provided: $dc");
        }
        $this->dc = $dc;
        $this->media = \strpos($dc, '_media') !== false;
        $this->cdn = \strpos($dc, '_cdn') !== false;

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
        $dc = \intval($this->dc);
        if ($this->test) {
            $dc += 10000;
        }
        if ($this->media) {
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
        $this->key = \count($this->nextStreams) - 1;

        return $this;
    }

    /**
     * Set read callback, called every time the socket reads at least a byte.
     *
     * @param callback $callable Read callback
     *
     * @return void
     */
    public function setReadCallback($callable)
    {
        $this->readCallback = $callable;
    }

    /**
     * Check if a read callback is present.
     *
     * @return boolean
     */
    public function hasReadCallback(): bool
    {
        return $this->readCallback !== null;
    }

    /**
     * Get read callback.
     *
     * @return callable
     */
    public function getReadCallback()
    {
        return $this->readCallback;
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
     * Check if has stream within stream chain.
     *
     * @param string $stream Stream name
     *
     * @return boolean
     */
    public function hasStreamName(string $stream): bool
    {
        foreach ($this->nextStreams as list($name)) {
            if ($name === $stream) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a stream from the stream chain.
     *
     * @internal Generator func
     *
     * @return \Generator
     */
    public function getStream(string $buffer = ''): \Generator
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
     * Get the inputClientProxy proxy MTProto object.
     *
     * @return array
     */
    public function getInputClientProxy(): ?array
    {
        foreach ($this->nextStreams as $couple) {
            list($streamName, $extra) = $couple;
            if ($streamName === ObfuscatedStream::getName() && isset($extra['address'])) {
                $extra['_'] = 'inputClientProxy';
                return $extra;
            }
        }
        return null;
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
        $string .= $this->isTest() ? ' test' : ' main';
        $string .= ' DC ';
        $string .= $this->getDc();
        $string .= ', via ';
        $string .= $this->getIpv6() ? 'ipv6' : 'ipv4';
        $string .= ' using ';
        foreach (\array_reverse($this->nextStreams) as $k => $stream) {
            if ($k) {
                $string .= ' => ';
            }
            $string .= \preg_replace('/.*\\\\/', '', $stream[0]);
            if ($stream[1] && $stream[0] !== DefaultStream::getName()) {
                $string .= ' ('.\json_encode($stream[1]).')';
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
