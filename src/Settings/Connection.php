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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
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
     * Initial DC list, see setDcList().
     */
    protected array $dcList = [
        'ipv4' => [
            2 => [
                'ip_address' => '149.154.167.51',
                'port' => 443,
                'tcpo_only' => false,
            ],
        ],
        'ipv6' => [
            2 => [
                'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                'port' => 443,
                'tcpo_only' => false,
            ],
        ],
    ];
    /**
     * Initial test DC list, see setTestDcList().
     */
    protected array $testDcList = [
        'ipv4' => [
            10002 => [
                'ip_address' => '149.154.167.40',
                'port' => 443,
                'tcpo_only' => false,
            ],
        ],
        'ipv6' => [
            10002 => [
                'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                'port' => 443,
                'tcpo_only' => false,
            ],
        ],
    ];

    /**
     * Domain of the web servers for the https protocol and the TLS websocket transport.
     */
    protected string $sslDomain = 'web.telegram.org';

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
     * `\danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream`: Lightest protocol available
     * Overhead: Very small
     * Minimum envelope length: 1 byte (length)
     * Maximum envelope length: 4 bytes (length)
     *
     * `\danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream`: I guess they like having multiple protocols
     * Overhead: small
     * Minimum envelope length: 4 bytes (length)
     * Maximum envelope length: 4 bytes (length)
     *
     * `\danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream`: Padded version of the intermediate protocol, to use with obfuscation enabled to bypass ISP blocks
     * Overhead: small-medium
     * Minimum envelope length: random
     * Maximum envelope length: random
     *
     * `\danog\MadelineProto\Stream\MTProtoTransport\FullStream`: The basic MTProto transport protocol
     * Overhead: medium
     * Minimum envelope length: 12 bytes (length+seqno+crc)
     * Maximum envelope length: 12 bytes (length+seqno+crc)
     * Pros:
     * Initial integrity check with crc32
     * Transport sequence number check
     *
     * Cons:
     * Initial integrity check with crc32 is not that useful since the TCP protocol already uses it internally
     * Transport sequence number check is also not that useful since transport sequence numbers are not encrypted and thus cannot be used to avoid replay attacks, and MadelineProto already uses MTProto sequence numbers and message ids for that.
     *
     * `\danog\MadelineProto\Stream\MTProtoTransport\HttpStream`: MTProto over HTTP for browsers and webhosts
     * Overhead: medium
     * Pros:
     * Can be used on restricted webhosts or browsers
     * Cons:
     * Very big envelope length
     *
     * `\danog\MadelineProto\Stream\MTProtoTransport\HttpsStream`: MTProto over HTTPS for browsers and webhosts, very secure
     * Overhead: high
     * Pros:
     * Can be used on restricted webhosts or browsers
     * Provides an additional layer of security by trasmitting data over TLS
     * Integrity checks with HMAC built into TLS
     * Sequence number checks built into TLS
     * Cons:
     * Very big envelope length
     * Requires an additional round of encryption
     *
     * @param class-string<MTProtoBufferInterface> $protocol Protocol identifier
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
     */
    public function setIpv6(bool $ipv6): self
    {
        $this->ipv6 = $ipv6;

        return $this;
    }

    /**
     * Get the domain of the web servers for the https protocol and the TLS websocket transport.
     */
    public function getSslDomain(): string
    {
        return $this->sslDomain;
    }

    /**
     * Set the domain of the web servers for the https protocol and the TLS websocket transport (web.telegram.org by default): DC N is reached at {subdomain of DC N}.{domain}, see setSslSubdomains().
     *
     * @param string $sslDomain Domain
     *
     * @psalm-external-mutation-free
     */
    public function setSslDomain(string $sslDomain): self
    {
        $this->sslDomain = $sslDomain;

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
     * An empty array disables the https protocol (also tried when the other connections fail, see setRetry()) and the TLS websocket transport: do that when connecting to a private server without web servers, see setDcList().
     *
     * @param array $sslSubdomains Subdomains of web.telegram.org for https protocol.
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     * `\danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream::class`
     * `\danog\MadelineProto\Stream\Proxy\HttpProxy::class`
     * `\danog\MadelineProto\Stream\Proxy\SocksProxy::class`
     *
     * @param class-string<StreamInterface> $proxy Proxy identifier
     * @param array                         $extra Extra
     *
     * @psalm-external-mutation-free
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
     * `\danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream::class`
     * `\danog\MadelineProto\Stream\Proxy\HttpProxy::class`
     * `\danog\MadelineProto\Stream\Proxy\SocksProxy::class`
     *
     * The value must be a list of extra (URI, username, password) for that proxy.
     *
     * @param array<class-string<StreamInterface>, list<array>> $proxies Proxies
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
     */
    public function clearProxies(): self
    {
        $this->proxy = [];

        return $this;
    }

    /**
     * Remove specific proxy pair.
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     * `danog\MadelineProto\Stream\Transport`: Default TCP transport
     * `danog\MadelineProto\Stream\WsTransport`: Plain websocket transport
     * `danog\MadelineProto\Stream\WssTransport`: TLS websocket transport
     *
     * @param class-string<RawStreamInterface> $transport Transport identifier.
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
     */
    public function setBindTo(?string $bindTo): self
    {
        $this->bindTo = $bindTo;

        return $this;
    }

    /**
     * Get the initial DC list.
     *
     * @return array{ipv4: array<int, array{ip_address: string, port: int, tcpo_only: bool}>, ipv6: array<int, array{ip_address: string, port: int, tcpo_only: bool}>}
     */
    public function getDcList(): array
    {
        return $this->dcList;
    }

    /**
     * Set the initial DC list: the addresses MadelineProto connects to before getting the full DC list from the server (help.getConfig), which replaces it.
     *
     * Use it, along with setRsaKeys(), to connect to a private server (a teamgram-server deployment, for example): the DC list returned by its help.getConfig is then used.
     * Also call setSslSubdomains([]) (or point setSslDomain() to its web servers), otherwise the https fallback connects to Telegram's web servers.
     *
     * The DC list is stored in the session: when this setting changes, the stored DC list is reset to it on the next start.
     * The authorization keys of the session are kept, so use a new session to switch to another server.
     *
     * Format, by IP version and DC ID (negative for media DCs):
     *
     * ```php
     * [
     *     'ipv4' => [
     *         2 => ['ip_address' => '192.0.2.1', 'port' => 443],
     *     ],
     *     'ipv6' => [
     *         2 => ['ip_address' => '2001:db8::1', 'port' => 443],
     *     ],
     * ]
     * ```
     *
     * The ipv6 list may be omitted (then don't enable setIpv6()), and each DC may also specify `'tcpo_only' => true` to only connect with the obfuscated protocol.
     * The IPv4 addresses may also be hostnames.
     * Before logging in, MadelineProto uses the first DC of the list (of the IP version chosen with setIpv6()).
     *
     * @param array<'ipv4'|'ipv6', array<int, array{ip_address: string, port: int, tcpo_only?: bool}>> $dcList DC list, the DC IDs must be between 1 and 10000.
     *
     * @psalm-external-mutation-free
     */
    public function setDcList(array $dcList): self
    {
        $this->dcList = self::normalizeDcList($dcList, false);

        return $this;
    }

    /**
     * Get the initial test DC list.
     *
     * @return array{ipv4: array<int, array{ip_address: string, port: int, tcpo_only: bool}>, ipv6: array<int, array{ip_address: string, port: int, tcpo_only: bool}>}
     */
    public function getTestDcList(): array
    {
        return $this->testDcList;
    }

    /**
     * Set the initial test DC list, used in test mode (see setTestMode()), in the format of setDcList().
     *
     * MadelineProto numbers the test DCs from 10001 (DC 2 is 10002): in test mode, it adds 10000 to the IDs of the DCs returned by help.getConfig.
     *
     * @param array<'ipv4'|'ipv6', array<int, array{ip_address: string, port: int, tcpo_only?: bool}>> $testDcList Test DC list, the DC IDs must be above 10000.
     *
     * @psalm-external-mutation-free
     */
    public function setTestDcList(array $testDcList): self
    {
        $this->testDcList = self::normalizeDcList($testDcList, true);

        return $this;
    }

    /**
     * Get the ID of the DC used before logging in: the first one of the initial DC list of the current mode.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getDefaultDcId(): int
    {
        $list = $this->testMode ? $this->testDcList : $this->dcList;
        [$preferred, $other] = $this->ipv6 ? ['ipv6', 'ipv4'] : ['ipv4', 'ipv6'];
        return array_key_first($list[$preferred] ?: $list[$other]);
    }

    /**
     * Validate a DC list, adding the default values.
     *
     * @psalm-pure
     */
    private static function normalizeDcList(array $dcList, bool $test): array
    {
        $kind = $test ? 'test DC list' : 'DC list';
        if ($unknown = array_diff(array_keys($dcList), ['ipv4', 'ipv6'])) {
            throw new Exception("Unknown keys in the $kind: ".implode(', ', $unknown).', only ipv4 and ipv6 are allowed!');
        }
        $result = [];
        foreach (['ipv4', 'ipv6'] as $version) {
            $result[$version] = [];
            if (!\is_array($dcList[$version] ?? [])) {
                throw new Exception("The $version list of the $kind must be an array!");
            }
            foreach ($dcList[$version] ?? [] as $id => $dc) {
                if (!\is_int($id) || $id === 0 || ($test ? abs($id) <= 10000 : abs($id) > 10000)) {
                    throw new Exception("Invalid DC ID $id in the $version list of the $kind: ".($test ? 'test DC IDs must be above 10000 (below -10000 for media DCs)' : 'DC IDs must be between 1 and 10000 (-1 and -10000 for media DCs)').'!');
                }
                if (!\is_array($dc)) {
                    throw new Exception("DC $id of the $version list of the $kind must be an array!");
                }
                if ($unknown = array_diff(array_keys($dc), ['ip_address', 'port', 'tcpo_only'])) {
                    throw new Exception("Unknown keys for DC $id of the $version list of the $kind: ".implode(', ', $unknown).', only ip_address, port and tcpo_only are allowed!');
                }
                $address = $dc['ip_address'] ?? null;
                if (!\is_string($address) || !($version === 'ipv6'
                    ? filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                    : filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($address, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
                ) {
                    throw new Exception("Invalid ip_address for DC $id of the $version list of the $kind: expected ".($version === 'ipv6' ? 'an IPv6 address' : 'an IPv4 address or a hostname').'!');
                }
                $port = $dc['port'] ?? null;
                if (!\is_int($port) || $port < 1 || $port > 65535) {
                    throw new Exception("Invalid port for DC $id of the $version list of the $kind!");
                }
                $tcpoOnly = $dc['tcpo_only'] ?? false;
                if (!\is_bool($tcpoOnly)) {
                    throw new Exception("Invalid tcpo_only for DC $id of the $version list of the $kind: expected a boolean!");
                }
                $result[$version][$id] = [
                    'ip_address' => $address,
                    'port' => $port,
                    'tcpo_only' => $tcpoOnly,
                ];
            }
        }
        if (!$result['ipv4'] && !$result['ipv6']) {
            throw new Exception("The $kind must contain at least one DC!");
        }
        return $result;
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
     * @psalm-external-mutation-free
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
     * @psalm-external-mutation-free
     */
    public function setTestRsaKeys(array $testRsaKeys): self
    {
        $this->testRsaKeys = $testRsaKeys;

        return $this;
    }
}
