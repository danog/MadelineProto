<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SettingsAbstract;
use danog\MadelineProto\Stream\Common\UdpBufferedStream;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Proxy\HttpProxy;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Stream\StreamInterface;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;

/**
 * Connection settings.
 */
class Connection extends SettingsAbstract
{
    /**
     * Minimum media socket count.
     */
    protected int $minMediaSocketCount = 5;
    /**
     * Maximum media socket count.
     */
    protected int $maxMediaSocketCount = 10;
    /**
     * Robin period (seconds).
     */
    protected int $robinPeriod = 10;
    /**
     * Default DC ID.
     */
    protected int $defaultDc = 2;
    /**
     * Default DC params.
     */
    private array $defaultDcParams = ['datacenter' => 2];
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
    protected int $timeout = 2;
    /**
     * Ping interval.
     */
    protected int $pingInterval = 10;

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
    private ?string $bindTo = null;

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

    public function mergeArray(array $settings): void
    {
        if (isset($settings['connection']['ssl_subdomains'])) {
            $this->setSslSubdomains($settings['connection']['ssl_subdomains']);
        }
        $settings = $settings['connection_settings'] ?? [];
        if (isset($settings['media_socket_count']['min'])) {
            $this->setMinMediaSocketCount($settings['media_socket_count']['min']);
        }
        if (isset($settings['media_socket_count']['max'])) {
            $this->setMaxMediaSocketCount($settings['media_socket_count']['max']);
        }
        foreach (self::toCamel([
            'robin_period',
            'default_dc',
            'pfs'
        ]) as $object => $array) {
            if (isset($settings[$array])) {
                $this->{$object}($settings[$array]);
            }
        }

        $settings = $settings['all'] ?? [];
        foreach (self::toCamel([
            'test_mode',
            'ipv6',
            'timeout',
            'obfuscated',
        ]) as $object => $array) {
            if (isset($settings[$array])) {
                $this->{$object}($settings[$array]);
            }
        }

        if (isset($settings['do_not_retry'])) {
            $this->setRetry(false);
        }
        if (isset($settings['proxy'])) {
            $isProxyArray = \is_iterable($settings['proxy']);
            foreach ($isProxyArray ? $settings['proxy'] : [$settings['proxy']] as $key => $proxy) {
                if ($proxy === '\\Socket') {
                    $proxy = DefaultStream::class;
                } elseif ($proxy === '\\SocksProxy') {
                    $proxy = SocksProxy::class;
                } elseif ($proxy === '\\HttpProxy') {
                    $proxy = HttpProxy::class;
                } elseif ($proxy === '\\MTProxySocket') {
                    $proxy = ObfuscatedStream::class;
                }
                if ($proxy !== DefaultStream::class) {
                    $this->addProxy($proxy, $isProxyArray ? $settings['proxy_extra'][$key] : $settings['proxy_extra']);
                }
            }
        }
        if (isset($settings['transport'])) {
            $transport = $settings['transport'];
            if ($transport === 'tcp') {
                $transport = DefaultStream::class;
            } elseif ($transport === 'ws') {
                $transport = WsStream::class;
            } elseif ($transport === 'wss') {
                $transport = WssStream::class;
            }
            $this->setTransport($transport);
        }
        if (isset($settings['protocol'])) {
            $protocol = $settings['protocol'];
            switch ($protocol) {
                case 'abridged':
                case 'tcp_abridged':
                    $protocol = AbridgedStream::class;
                    break;
                case 'intermediate':
                case 'tcp_intermediate':
                    $protocol = AbridgedStream::class;
                    break;
                case 'obfuscated2':
                    $this->setObfuscated(true);
                // no break
                case 'intermediate_padded':
                case 'tcp_intermediate_padded':
                    $protocol = IntermediatePaddedStream::class;
                    break;
                case 'full':
                case 'tcp_full':
                    $protocol = FullStream::class;
                    break;
                case 'http':
                    $protocol = HttpStream::class;
                    break;
                case 'https':
                    $protocol = HttpsStream::class;
                    break;
                case 'udp':
                    $protocol = UdpBufferedStream::class;
                    break;
            }
            $this->setProtocol($protocol);
        }
    }

    public function __construct()
    {
        $this->init();
    }
    public function __wakeup()
    {
        $this->init();
    }
    public function init(): void
    {
        Magic::start(true);

        if (Magic::$altervista) {
            $this->addProxy(HttpProxy::class, ['address' => 'localhost', 'port' => 80]);
            $this->setProtocol(HttpStream::class);
        } else {
            $this->removeProxy(HttpProxy::class, ['address' => 'localhost', 'port' => 80]);
        }
    }
    /**
     * Get protocol identifier.
     *
     * @return string
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
     *
     * @return self
     */
    public function setProtocol(string $protocol): self
    {
        if (!isset(\class_implements($protocol)[MTProtoBufferInterface::class])) {
            throw new Exception("An invalid protocol was specified!");
        }
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get whether to use ipv6.
     *
     * @return bool
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
     * @return self
     */
    public function setIpv6(bool $ipv6): self
    {
        $this->ipv6 = $ipv6;

        return $this;
    }

    /**
     * Get subdomains of web.telegram.org for https protocol.
     *
     * @return array
     */
    public function getSslSubdomains(): array
    {
        return $this->sslSubdomains;
    }

    /**
     * Set subdomains of web.telegram.org for https protocol.
     *
     * @param array $sslSubdomains Subdomains of web.telegram.org for https protocol.
     *
     * @return self
     */
    public function setSslSubdomains(array $sslSubdomains): self
    {
        $this->sslSubdomains = $sslSubdomains;

        return $this;
    }

    /**
     * Get minimum media socket count.
     *
     * @return int
     */
    public function getMinMediaSocketCount(): int
    {
        return $this->minMediaSocketCount;
    }

    /**
     * Set minimum media socket count.
     *
     * @param int $minMediaSocketCount Minimum media socket count.
     *
     * @return self
     */
    public function setMinMediaSocketCount(int $minMediaSocketCount): self
    {
        $this->minMediaSocketCount = $minMediaSocketCount;

        return $this;
    }

    /**
     * Get maximum media socket count.
     *
     * @return int
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
     * @return self
     */
    public function setMaxMediaSocketCount(int $maxMediaSocketCount): self
    {
        $this->maxMediaSocketCount = $maxMediaSocketCount;

        return $this;
    }

    /**
     * Get robin period (seconds).
     *
     * @return int
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
     * @return self
     */
    public function setRobinPeriod(int $robinPeriod): self
    {
        $this->robinPeriod = $robinPeriod;

        return $this;
    }

    /**
     * Get default DC ID.
     *
     * @return int
     */
    public function getDefaultDc(): int
    {
        return $this->defaultDc;
    }
    /**
     * Get default DC params.
     *
     * @return array
     */
    public function getDefaultDcParams(): array
    {
        return $this->defaultDcParams;
    }

    /**
     * Set default DC ID.
     *
     * @param int $defaultDc Default DC ID.
     *
     * @return self
     */
    public function setDefaultDc(int $defaultDc): self
    {
        $this->defaultDc = $defaultDc;
        $this->defaultDcParams = ['datacenter' => $defaultDc];

        return $this;
    }

    /**
     * Get proxy identifiers.
     *
     * @return array
     * @psalm-return array<class-string<StreamInterface>, array>
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
     *
     * @return self
     */
    public function addProxy(string $proxy, array $extra = []): self
    {
        if (!isset(\class_implements($proxy)[StreamInterface::class])) {
            throw new Exception("An invalid proxy class was specified!");
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
     * @param array $proxies Proxies
     *
     * @return self
     */
    public function setProxy(array $proxies): self
    {
        $this->proxy = $proxies;
        return $this;
    }
    /**
     * Clear proxies.
     *
     * @return self
     */
    public function clearProxies(): self
    {
        $this->proxy = [];

        return $this;
    }

    /**
     * Remove specific proxy pair.
     *
     * @param string $proxy
     * @param array $extra
     *
     * @return self
     */
    public function removeProxy(string $proxy, array $extra): self
    {
        if (!isset($this->proxy[$proxy])) {
            return $this;
        }
        if (false === $index = \array_search($extra, $this->proxy[$proxy])) {
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
     *
     * @return bool
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
     * @return self
     */
    public function setObfuscated(bool $obfuscated): self
    {
        $this->obfuscated = $obfuscated;

        return $this;
    }

    /**
     * Get whether we're in test mode.
     *
     * @return bool
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
     * @return self
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
     *
     * @return self
     */
    public function setTransport(string $transport): self
    {
        if (!isset(\class_implements($transport)[RawStreamInterface::class])) {
            throw new Exception("An invalid transport was specified!");
        }
        $this->transport = $transport;

        return $this;
    }

    /**
     * Get whether to retry connection.
     *
     * @return bool
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
     * @return self
     */
    public function setRetry(bool $retry): self
    {
        $this->retry = $retry;

        return $this;
    }

    /**
     * Get connection timeout.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set connection timeout.
     *
     * @param int $timeout Connection timeout.
     *
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Get ping interval.
     *
     * @return int
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
     * @return self
     */
    public function setPingInterval(int $pingInterval): self
    {
        $this->pingInterval = $pingInterval;

        return $this;
    }

    /**
     * Get whether to use DNS over HTTPS.
     *
     * @return bool
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
     * @return self
     */
    public function setUseDoH(bool $useDoH): self
    {
        $this->useDoH = $useDoH;

        return $this;
    }

    /**
     * Get bind on specific address and port.
     *
     * @return ?string
     */
    public function getBindTo(): ?string
    {
        return $this->bindTo;
    }

    /**
     * Set bind on specific address and port.
     *
     * @param ?string $bindTo Bind on specific address and port.
     *
     * @return self
     */
    public function setBindTo(?string $bindTo): self
    {
        $this->bindTo = $bindTo;

        return $this;
    }
}
