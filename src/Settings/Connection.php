<?php declare(strict_types=1);

/**
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

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SettingsAbstract;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\Proxy\HttpProxy;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Stream\StreamInterface;
use danog\MadelineProto\Stream\Transport\DefaultStream;

/**
 * Connection settings.
 */
final class Connection extends SettingsAbstract
{
    /**
     * RSA keys.
     */
    protected array $rsaKeys = [
        "-----BEGIN RSA PUBLIC KEY-----\n".
        "MIIBCgKCAQEA6LszBcC1LGzyr992NzE0ieY+BSaOW622Aa9Bd4ZHLl+TuFQ4lo4g\n".
        "5nKaMBwK/BIb9xUfg0Q29/2mgIR6Zr9krM7HjuIcCzFvDtr+L0GQjae9H0pRB2OO\n".
        "62cECs5HKhT5DZ98K33vmWiLowc621dQuwKWSQKjWf50XYFw42h21P2KXUGyp2y/\n".
        "+aEyZ+uVgLLQbRA1dEjSDZ2iGRy12Mk5gpYc397aYp438fsJoHIgJ2lgMv5h7WY9\n".
        "t6N/byY9Nw9p21Og3AoXSL2q/2IJ1WRUhebgAdGVMlV1fkuOQoEzR7EdpqtQD9Cs\n".
        "5+bfo3Nhmcyvk5ftB0WkJ9z6bNZ7yxrP8wIDAQAB\n".
        '-----END RSA PUBLIC KEY-----',
    ];
    /**
     * Test RSA keys.
     */
    protected array $testRsaKeys =  [
        "-----BEGIN RSA PUBLIC KEY-----\n".
        "MIIBCgKCAQEAyMEdY1aR+sCR3ZSJrtztKTKqigvO/vBfqACJLZtS7QMgCGXJ6XIR\n".
        "yy7mx66W0/sOFa7/1mAZtEoIokDP3ShoqF4fVNb6XeqgQfaUHd8wJpDWHcR2OFwv\n".
        "plUUI1PLTktZ9uW2WE23b+ixNwJjJGwBDJPQEQFBE+vfmH0JP503wr5INS1poWg/\n".
        "j25sIWeYPHYeOrFp/eXaqhISP6G+q2IeTaWTXpwZj4LzXq5YOpk4bYEQ6mvRq7D1\n".
        "aHWfYmlEGepfaYR8Q0YqvvhYtMte3ITnuSJs171+GDqpdKcSwHnd6FudwGO4pcCO\n".
        "j4WcDuXc2CTHgH8gFTNhp/Y8/SpDOhvn9QIDAQAB\n".
        '-----END RSA PUBLIC KEY-----',
    ];
    /**
     * Maximum media socket count.
     */
    protected int $maxMediaSocketCount = 10;
    /**
     * Robin period (seconds).
     */
    protected int $robinPeriod = 10;
    /**
     * Protocol identifier.
     *
     * @var class-string<MTProtoBufferInterface>
     */
    protected string $protocol = AbridgedStream::class;
    /**
     * Transport identifier.
     *
     * @var class-string<RawStreamInterface>
     */
    protected string $transport = DefaultStream::class;
    /**
     * Proxy identifiers.
     *
     * @var array<class-string<StreamInterface>, array>
     */
    protected array $proxy = [];
    /**
     * Whether to use the obfuscated protocol.
     */
    protected bool $obfuscated = false;

    /**
     * Whether we're in test mode.
     */
    protected bool $testMode = false;

    /**
     * Whether to use ipv6.
     */
    protected bool $ipv6 = false;

    /**
     * Connection timeout.
     */
    protected float $timeout = 5.0;
    /**
     * Ping interval.
     */
    protected int $pingInterval = 60;

    /**
     * Whether to retry connection.
     */
    protected bool $retry = true;

    /**
     * Whether to use DNS over HTTPS.
     */
    protected bool $useDoH = true;

    /**
     * Bind on specific address and port.
     */
    protected ?string $bindTo = null;

    /**
     * Subdomains of web.telegram.org for https protocol.
     */
    protected array $sslSubdomains = [
        1 => 'pluto',
        2 => 'venus',
        3 => 'aurora',
        4 => 'vesta',
        5 => 'flora',
    ];

    public function __construct()
    {
        $this->init();
    }
    public function __wakeup(): void
    {
        $this->init();
    }
    public function init(): void
    {
        Magic::start(light: true);

        if (Magic::$altervista) {
            $this->addProxy(HttpProxy::class, ['address' => 'localhost', 'port' => 80]);
            $this->setProtocol(HttpStream::class);
        } else {
            $this->removeProxy(HttpProxy::class, ['address' => 'localhost', 'port' => 80]);
        }
    }
    /**
     * Get protocol identifier.
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * Set protocol identifier.
     *
     * Available MTProto transport protocols (smaller overhead is better):
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream`: Lightest protocol available
     *   * Overhead: Very small
     *   * Minimum envelope length: 1 byte (length)
     *   * Maximum envelope length: 4 bytes (length)
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream`: I guess they like having multiple protocols
     *   * Overhead: small
     *   * Minimum envelope length: 4 bytes (length)
     *   * Maximum envelope length: 4 bytes (length)
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream`: Padded version of the intermediate protocol, to use with obfuscation enabled to bypass ISP blocks
     *   * Overhead: small-medium
     *   * Minimum envelope length: random
     *   * Maximum envelope length: random
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\FullStream`: The basic MTProto transport protocol
     *   * Overhead: medium
     *   * Minimum envelope length: 12 bytes (length+seqno+crc)
     *   * Maximum envelope length: 12 bytes (length+seqno+crc)
     *   * Pros:
     *     * Initial integrity check with crc32
     *     * Transport sequence number check
     *
     *   * Cons:
     *     * Initial integrity check with crc32 is not that useful since the TCP protocol already uses it internally
     *     * Transport sequence number check is also not that useful since transport sequence numbers are not encrypted and thus cannot be used to avoid replay attacks, and MadelineProto already uses MTProto sequence numbers and message ids for that.
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\HttpStream`: MTProto over HTTP for browsers and webhosts
     *   * Overhead: medium
     *   * Pros:
     *     * Can be used on restricted webhosts or browsers
     *   * Cons:
     *     * Very big envelope length
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\HttpsStream`: MTProto over HTTPS for browsers and webhosts, very secure
     *   * Overhead: high
     *   * Pros:
     *     * Can be used on restricted webhosts or browsers
     *     * Provides an additional layer of security by trasmitting data over TLS
     *     * Integrity checks with HMAC built into TLS
     *     * Sequence number checks built into TLS
     *   * Cons:
     *     * Very big envelope length
     *     * Requires an additional round of encryption
     *
     * @param class-string<MTProtoBufferInterface> $protocol Protocol identifier
     */
    public function setProtocol(string $protocol): self
    {
        if (!isset(class_implements($protocol)[MTProtoBufferInterface::class])) {
            throw new Exception('An invalid protocol was specified!');
        }
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get whether to use ipv6.
     */
    public function getIpv6(): bool
    {
        return $this->ipv6;
    }

    /**
     * Set whether to use ipv6.
     *
     * @param bool $ipv6 Whether to use ipv6
     */
    public function setIpv6(bool $ipv6): self
    {
        $this->ipv6 = $ipv6;

        return $this;
    }

    /**
     * Get subdomains of web.telegram.org for https protocol.
     */
    public function getSslSubdomains(): array
    {
        return $this->sslSubdomains;
    }

    /**
     * Set subdomains of web.telegram.org for https protocol.
     *
     * @param array $sslSubdomains Subdomains of web.telegram.org for https protocol.
     */
    public function setSslSubdomains(array $sslSubdomains): self
    {
        $this->sslSubdomains = $sslSubdomains;

        return $this;
    }

    /**
     * Get maximum media socket count.
     */
    public function getMaxMediaSocketCount(): int
    {
        return $this->maxMediaSocketCount;
    }

    /**
     * Set maximum media socket count.
     *
     * @param int $maxMediaSocketCount Maximum media socket count.
     */
    public function setMaxMediaSocketCount(int $maxMediaSocketCount): self
    {
        $this->maxMediaSocketCount = $maxMediaSocketCount;

        return $this;
    }

    /**
     * Get robin period (seconds).
     */
    public function getRobinPeriod(): int
    {
        return $this->robinPeriod;
    }

    /**
     * Set robin period (seconds).
     *
     * @param int $robinPeriod Robin period (seconds).
     */
    public function setRobinPeriod(int $robinPeriod): self
    {
        $this->robinPeriod = $robinPeriod;

        return $this;
    }

    /**
     * Get proxy identifiers.
     *
     * @return array<class-string<StreamInterface>, array>
     */
    public function getProxies(): array
    {
        return $this->proxy;
    }

    /**
     * Add proxy identifier to list, one of:.
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream::class`
     * * `\danog\MadelineProto\Stream\Proxy\HttpProxy::class`
     * * `\danog\MadelineProto\Stream\Proxy\SocksProxy::class`
     *
     * @param class-string<StreamInterface> $proxy Proxy identifier
     * @param array                         $extra Extra
     */
    public function addProxy(string $proxy, array $extra = []): self
    {
        if (!isset(class_implements($proxy)[StreamInterface::class])) {
            throw new Exception('An invalid proxy class was specified!');
        }
        if (!isset($this->proxy[$proxy])) {
            $this->proxy[$proxy] = [];
        }
        $this->proxy[$proxy][] = $extra;

        return $this;
    }

    /**
     * Set proxies.
     *
     * The key must be one of:
     *
     * * `\danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream::class`
     * * `\danog\MadelineProto\Stream\Proxy\HttpProxy::class`
     * * `\danog\MadelineProto\Stream\Proxy\SocksProxy::class`
     *
     * The value must be a list of extra (URI, username, password) for that proxy.
     *
     * @param array<class-string<StreamInterface>, list<array>> $proxies Proxies
     */
    public function setProxies(array $proxies): self
    {
        foreach ($proxies as $proxy => $_) {
            if (!isset(class_implements($proxy)[StreamInterface::class])) {
                throw new Exception('An invalid proxy class was specified!');
            }
        }
        $this->proxy = $proxies;
        return $this;
    }
    /**
     * Clear proxies.
     */
    public function clearProxies(): self
    {
        $this->proxy = [];

        return $this;
    }

    /**
     * Remove specific proxy pair.
     */
    public function removeProxy(string $proxy, array $extra): self
    {
        if (!isset($this->proxy[$proxy])) {
            return $this;
        }
        if (false === $index = array_search($extra, $this->proxy[$proxy], true)) {
            return $this;
        }
        unset($this->proxy[$proxy][$index]);
        if (empty($this->proxy[$proxy])) {
            unset($this->proxy[$proxy]);
        }
        return $this;
    }
    /**
     * Get whether to use the obfuscated protocol: useful to bypass ISP blocks.
     */
    public function getObfuscated(): bool
    {
        return $this->obfuscated;
    }

    /**
     * Set whether to use the obfuscated protocol: useful to bypass ISP blocks.
     *
     * @param bool $obfuscated Whether to use the obfuscated protocol.
     */
    public function setObfuscated(bool $obfuscated): self
    {
        $this->obfuscated = $obfuscated;

        return $this;
    }

    /**
     * Get whether we're in test mode.
     */
    public function getTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Set whether we're in test mode.
     *
     * @param bool $testMode Whether we're in test mode.
     */
    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;

        return $this;
    }

    /**
     * Get transport identifier.
     *
     * @return class-string<RawStreamInterface>
     */
    public function getTransport(): string
    {
        return $this->transport;
    }

    /**
     * Sets the transport protocol to use when connecting to telegram.
     * Not supported by HTTP and HTTPS protocols, obfuscation must be enabled.
     *
     * * `danog\MadelineProto\Stream\Transport`: Default TCP transport
     * * `danog\MadelineProto\Stream\WsTransport`: Plain websocket transport
     * * `danog\MadelineProto\Stream\WssTransport`: TLS websocket transport
     *
     * @param class-string<RawStreamInterface> $transport Transport identifier.
     */
    public function setTransport(string $transport): self
    {
        if (!isset(class_implements($transport)[RawStreamInterface::class])) {
            throw new Exception('An invalid transport was specified!');
        }
        $this->transport = $transport;

        return $this;
    }

    /**
     * Get whether to retry connection.
     */
    public function getRetry(): bool
    {
        return $this->retry;
    }

    /**
     * Set whether to retry connection.
     *
     * @param bool $retry Whether to retry connection.
     */
    public function setRetry(bool $retry): self
    {
        $this->retry = $retry;

        return $this;
    }

    /**
     * Get connection timeout.
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * Set connection timeout.
     *
     * @param float $timeout Connection timeout.
     */
    public function setTimeout(float $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Get ping interval.
     */
    public function getPingInterval(): int
    {
        return $this->pingInterval;
    }

    /**
     * Set ping interval.
     *
     * @param int $pingInterval Ping interval
     */
    public function setPingInterval(int $pingInterval): self
    {
        $this->pingInterval = $pingInterval;

        return $this;
    }

    /**
     * Get whether to use DNS over HTTPS.
     */
    public function getUseDoH(): bool
    {
        return $this->useDoH;
    }

    /**
     * Set whether to use DNS over HTTPS.
     *
     * @param bool $useDoH Whether to use DNS over HTTPS
     */
    public function setUseDoH(bool $useDoH): self
    {
        $this->useDoH = $useDoH;

        return $this;
    }

    /**
     * Get bind on specific address and port.
     */
    public function getBindTo(): ?string
    {
        return $this->bindTo;
    }

    /**
     * Set bind on specific address and port.
     *
     * @param null|string $bindTo Bind on specific address and port.
     */
    public function setBindTo(?string $bindTo): self
    {
        $this->bindTo = $bindTo;

        return $this;
    }

    /**
     * Get RSA keys.
     *
     */
    public function getRsaKeys(): array
    {
        return $this->rsaKeys;
    }

    /**
     * Set RSA keys.
     *
     * @param array $rsaKeys RSA keys
     *
     */
    public function setRsaKeys(array $rsaKeys): self
    {
        $this->rsaKeys = $rsaKeys;

        return $this;
    }

    /**
     * Get test RSA keys.
     *
     */
    public function getTestRsaKeys(): array
    {
        return $this->testRsaKeys;
    }

    /**
     * Set test RSA keys.
     *
     * @param array $testRsaKeys Test RSA keys
     *
     */
    public function setTestRsaKeys(array $testRsaKeys): self
    {
        $this->testRsaKeys = $testRsaKeys;

        return $this;
    }
}
