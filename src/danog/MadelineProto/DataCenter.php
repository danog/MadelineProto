<?php

/**
 * DataCenter module.
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

namespace danog\MadelineProto;

use Amp\Dns\Resolver;
use Amp\Dns\Rfc1035StubResolver;
use Amp\DoH\DoHConfig;
use Amp\DoH\Nameserver;
use Amp\DoH\Rfc8484StubResolver;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Cookie\CookieInterceptor;
use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\Cookie\InMemoryCookieJar;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Socket\ConnectContext;
use Amp\Socket\DnsConnector;
use Amp\Websocket\Client\Handshake;
use Amp\Websocket\Client\Rfc6455Connector;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\Settings\Connection as ConnectionSettings;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\UdpBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;

/**
 * Manages datacenters.
 */
class DataCenter
{
    use \danog\Serializable;
    /**
     * All socket connections to DCs.
     *
     * @var array<string|int, DataCenterConnection>
     */
    public $sockets = [];
    /**
     * Current DC ID.
     *
     * @var string|int
     */
    public $curdc = 0;
    /**
     * Main instance.
     *
     * @var MTProto
     */
    private $API;
    /**
     * DC list.
     *
     * @var array
     */
    private $dclist = [];
    /**
     * Settings.
     *
     * @var ConnectionSettings
     */
    private $settings;
    /**
     * HTTP client.
     *
     * @var \Amp\Http\Client\HttpClient
     */
    private $HTTPClient;
    /**
     * DNS over HTTPS client.
     *
     * @var Rfc8484StubResolver|Rfc1035StubResolver
     */
    private $DoHClient;
    /**
     * Non-proxied DNS over HTTPS client.
     *
     * @var Rfc8484StubResolver|Rfc1035StubResolver
     */
    private $nonProxiedDoHClient;
    /**
     * Cookie jar.
     *
     * @var \Amp\Http\Client\Cookie\CookieJar
     */
    private $CookieJar;
    /**
     * DNS connector.
     *
     * @var DNSConnector
     */
    private $dnsConnector;
    /**
     * DoH connector.
     */
    private Rfc6455Connector $webSocketConnector;

    public function __sleep()
    {
        return ['sockets', 'curdc', 'dclist', 'settings'];
    }
    public function __wakeup()
    {
        if (\is_array($this->settings)) {
            $settings = new ConnectionSettings;
            $settings->mergeArray(['connection_settings' => $this->settings]);
            $this->settings = $settings;
        }
        $array = [];
        foreach ($this->sockets as $id => $socket) {
            if ($socket instanceof \danog\MadelineProto\Connection) {
                if (isset($socket->temp_auth_key) && $socket->temp_auth_key) {
                    $array[$id]['tempAuthKey'] = $socket->temp_auth_key;
                }
                if (isset($socket->auth_key) && $socket->auth_key) {
                    $array[$id]['permAuthKey'] = $socket->auth_key;
                    /** @psalm-suppress UndefinedPropertyFetch */
                    $array[$id]['permAuthKey']['authorized'] = $socket->authorized;
                }
                $array[$id] = [];
            }
        }
        $this->setDataCenterConnections($array);
    }
    /**
     * Set auth key information from saved auth array.
     *
     * @param array $saved Saved auth array
     *
     * @return void
     */
    public function setDataCenterConnections(array $saved)
    {
        foreach ($saved as $id => $data) {
            $connection = $this->sockets[$id] = new DataCenterConnection();
            if (isset($data['permAuthKey'])) {
                $connection->setPermAuthKey(new PermAuthKey($data['permAuthKey']));
            }
            if (isset($data['linked'])) {
                continue;
            }
            if (isset($data['tempAuthKey'])) {
                $connection->setTempAuthKey(new TempAuthKey($data['tempAuthKey']));
                if (($data['tempAuthKey']['bound'] ?? false) && $connection->hasPermAuthKey()) {
                    $connection->bind();
                }
            }
            unset($saved[$id]);
        }
        foreach ($saved as $id => $data) {
            $connection = $this->sockets[$id];
            $connection->link($data['linked']);
            if (isset($data['tempAuthKey'])) {
                $connection->setTempAuthKey(new TempAuthKey($data['tempAuthKey']));
                if (($data['tempAuthKey']['bound'] ?? false) && $connection->hasPermAuthKey()) {
                    $connection->bind();
                }
            }
        }
    }
    /**
     * Constructor function.
     *
     * @param MTProto     $API          Main MTProto instance
     * @param array       $dclist       DC IP list
     * @param ConnectionSettings $settings     Settings
     * @param boolean     $reconnectAll Whether to reconnect to all DCs or just to changed ones
     * @param CookieJar   $jar          Cookie jar
     *
     * @return void
     */
    public function __magic_construct($API, array $dclist, ConnectionSettings $settings, bool $reconnectAll = true, CookieJar $jar = null)
    {
        $this->API = $API;
        $changed = [];
        $changedSettings = $settings->hasChanged();
        if (!$reconnectAll) {
            $changed = [];
            $test = $API->getCachedConfig()['test_mode'] ?? false ? 'test' : 'main';
            foreach ($dclist[$test] as $ipv6 => $dcs) {
                foreach ($dcs as $id => $dc) {
                    if ($dc !== ($this->dclist[$test][$ipv6][$id] ?? [])) {
                        $changed[$id] = true;
                    }
                }
            }
        }
        $this->dclist = $dclist;
        $this->settings = $settings;
        foreach ($this->sockets as $key => $socket) {
            if ($socket instanceof DataCenterConnection && !\strpos($key, '_bk')) {
                if ($reconnectAll || isset($changed[$id])) {
                    $this->API->logger->logger("Disconnecting all before reconnect!");
                    $socket->needReconnect(true);
                    $socket->setExtra($this->API);
                    $socket->disconnect();
                }
            } else {
                unset($this->sockets[$key]);
            }
        }
        if ($reconnectAll || $changedSettings || !$this->CookieJar) {
            $this->CookieJar = $jar ?? new InMemoryCookieJar();
            $this->HTTPClient = (new HttpClientBuilder())->interceptNetwork(new CookieInterceptor($this->CookieJar))->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($this))))->build();
            $DoHHTTPClient = (new HttpClientBuilder())->interceptNetwork(new CookieInterceptor($this->CookieJar))->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($this, true))))->build();
            $DoHConfig = new DoHConfig([new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'), new Nameserver('https://dns.google/resolve')], $DoHHTTPClient);
            $nonProxiedDoHConfig = new DoHConfig([new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'), new Nameserver('https://dns.google/resolve')]);
            $this->DoHClient = Magic::$altervista || Magic::$zerowebhost || !$settings->getUseDoH()
                ? new Rfc1035StubResolver()
                : new Rfc8484StubResolver($DoHConfig);
            $this->nonProxiedDoHClient = Magic::$altervista || Magic::$zerowebhost || !$settings->getUseDoH()
                ? new Rfc1035StubResolver()
                : new Rfc8484StubResolver($nonProxiedDoHConfig);

            $this->dnsConnector = new DnsConnector(new Rfc1035StubResolver());
            $this->webSocketConnector = new Rfc6455Connector($this->HTTPClient);
        }
        $this->settings->applyChanges();
    }
    /**
     * Set VoIP endpoints.
     *
     * @param array $endpoints Endpoints
     *
     * @return void
     */
    public function setVoIPEndpoints(array $endpoints): void
    {
    }
    /**
     * Connect to specified DC.
     *
     * @param string  $dc_number DC to connect to
     * @param integer $id        Connection ID to re-establish (optional)
     *
     * @return \Generator<bool>
     */
    public function dcConnect(string $dc_number, int $id = -1): \Generator
    {
        $old = isset($this->sockets[$dc_number]) && ($this->sockets[$dc_number]->shouldReconnect() || $id !== -1 && $this->sockets[$dc_number]->hasConnection($id) && $this->sockets[$dc_number]->getConnection($id)->shouldReconnect());
        if (isset($this->sockets[$dc_number]) && !$old) {
            $this->API->logger("Not reconnecting to DC {$dc_number} ({$id})");
            return false;
        }
        $ctxs = $this->generateContexts($dc_number);
        if (empty($ctxs)) {
            return false;
        }
        foreach ($ctxs as $ctx) {
            try {
                if ($old) {
                    $this->API->logger->logger("Reconnecting to DC {$dc_number} ({$id}) from existing", Logger::WARNING);
                    $this->sockets[$dc_number]->setExtra($this->API);
                    yield from $this->sockets[$dc_number]->connect($ctx, $id);
                } else {
                    $this->API->logger->logger("Connecting to DC {$dc_number} from scratch", Logger::WARNING);
                    $this->sockets[$dc_number] = new DataCenterConnection();
                    $this->sockets[$dc_number]->setExtra($this->API);
                    yield from $this->sockets[$dc_number]->connect($ctx);
                }
                if ($ctx->getIpv6()) {
                    Magic::setIpv6(true);
                }
                $this->API->logger->logger('OK!', Logger::WARNING);
                return true;
            } catch (\Throwable $e) {
                if (\defined("MADELINEPROTO_TEST") && \constant("MADELINEPROTO_TEST") === 'pony') {
                    throw $e;
                }
                $this->API->logger->logger("Connection failed ({$dc_number}): ".$e->getMessage(), Logger::ERROR);
            }
        }
        throw new Exception("Could not connect to DC {$dc_number}");
    }
    /**
     * Generate contexts.
     *
     * @param integer        $dc_number DC ID to generate contexts for
     * @param string         $uri       URI
     * @param ConnectContext $context   Connection context
     *
     * @return ConnectionContext[]
     */
    public function generateContexts($dc_number = 0, string $uri = '', ConnectContext $context = null): array
    {
        $ctxs = [];
        $combos = [];
        $test = $this->settings->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->settings->getIpv6() ? 'ipv6' : 'ipv4';
        switch ($this->settings->getProtocol()) {
            case AbridgedStream::class:
                $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [AbridgedStream::class, []]];
                break;
            case IntermediateStream::class:
                $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [IntermediateStream::class, []]];
                break;
            case IntermediatePaddedStream::class:
                $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [IntermediatePaddedStream::class, []]];
                break;
            case FullStream::class:
                $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [FullStream::class, []]];
                break;
            case HttpStream::class:
                $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpStream::class, []]];
                break;
            case HttpsStream::class:
                $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpsStream::class, []]];
                break;
            case UdpBufferedStream::class:
                $default = [[DefaultStream::class, []], [UdpBufferedStream::class, []]];
                break;
            default:
                throw new Exception(Lang::$current_lang['protocol_invalid']);
        }
        if ($this->settings->getObfuscated() && !\in_array($default[2][0], [HttpsStream::class, HttpStream::class])) {
            $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], \end($default)];
        }
        if ($this->settings->getTransport() && !\in_array($default[2][0], [HttpsStream::class, HttpStream::class])) {
            switch ($this->settings->getTransport()) {
                case DefaultStream::class:
                    if ($this->settings->getObfuscated()) {
                        $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], \end($default)];
                    }
                    break;
                case WssStream::class:
                    $default = [[DefaultStream::class, []], [WssStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], \end($default)];
                    break;
                case WsStream::class:
                    $default = [[DefaultStream::class, []], [WsStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], \end($default)];
                    break;
            }
        }
        if (!$dc_number) {
            $default = [[DefaultStream::class, []], [BufferedRawStream::class, []]];
        }
        $combos[] = $default;
        if ($this->settings->getRetry()) {
            if (isset($this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) && $this->dclist[$test][$ipv6][$dc_number]['tcpo_only'] || isset($this->dclist[$test][$ipv6][$dc_number]['secret'])) {
                $extra = isset($this->dclist[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $this->dclist[$test][$ipv6][$dc_number]['secret']] : [];
                $combos[] = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, $extra], [IntermediatePaddedStream::class, []]];
            }
            $proxyCombos = [];
            foreach ($this->settings->getProxies() as $proxy => $extras) {
                if (!$dc_number && $proxy === ObfuscatedStream::class) {
                    continue;
                }
                foreach ($extras as $extra) {
                    if ($proxy === ObfuscatedStream::class && \in_array(\strlen($extra['secret']), [17, 34])) {
                        $combos[] = [[DefaultStream::class, []], [BufferedRawStream::class, []], [$proxy, $extra], [IntermediatePaddedStream::class, []]];
                    }
                    foreach ($combos as $orig) {
                        $combo = [];
                        if ($proxy === ObfuscatedStream::class) {
                            $combo = $orig;
                            if ($combo[\count($combo) - 2][0] === ObfuscatedStream::class) {
                                $combo[\count($combo) - 2][1] = $extra;
                            } else {
                                $mtproto = \end($combo);
                                $combo[\count($combo) - 1] = [$proxy, $extra];
                                $combo[] = $mtproto;
                            }
                        } else {
                            if ($orig[1][0] === BufferedRawStream::class) {
                                [$first, $second] = [\array_slice($orig, 0, 2), \array_slice($orig, 2)];
                                $first[] = [$proxy, $extra];
                                $combo = \array_merge($first, $second);
                            } elseif (\in_array($orig[1][0], [WsStream::class, WssStream::class])) {
                                [$first, $second] = [\array_slice($orig, 0, 1), \array_slice($orig, 1)];
                                $first[] = [BufferedRawStream::class, []];
                                $first[] = [$proxy, $extra];
                                $combo = \array_merge($first, $second);
                            }
                        }
                        $proxyCombos []= $combo;
                    }
                }
            }
            $combos = \array_merge($proxyCombos, $combos);
            if ($dc_number) {
                $combos[] = [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpsStream::class, []]];
            }
            $combos = \array_unique($combos, SORT_REGULAR);
        }
        /* @var $context \Amp\ConnectContext */
        $context = $context ?? (new ConnectContext())->withMaxAttempts(1)->withConnectTimeout(1000 * $this->settings->getTimeout())->withBindTo($this->settings->getBindTo());
        foreach ($combos as $combo) {
            foreach ([true, false] as $useDoH) {
                $ipv6Combos = [
                    $this->settings->getIpv6() ? 'ipv6' : 'ipv4',
                    $this->settings->getIpv6() ? 'ipv4' : 'ipv6'
                ];
                foreach ($ipv6Combos as $ipv6) {
                    // This is only for non-MTProto connections
                    if (!$dc_number) {
                        /* @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                        $ctx = (new ConnectionContext())->setSocketContext($context)->setUri($uri)->setIpv6($ipv6 === 'ipv6');
                        foreach ($combo as $stream) {
                            if ($stream[0] === DefaultStream::class && $stream[1] === []) {
                                $stream[1] = $useDoH ? new DoHConnector($this, $ctx) : $this->dnsConnector;
                            }
                            /** @var array{0: class-string, 1: mixed} $stream */
                            $ctx->addStream(...$stream);
                        }
                        $ctxs[] = $ctx;
                        continue;
                    }



                    // This is only for MTProto connections
                    if (!isset($this->dclist[$test][$ipv6][$dc_number]['ip_address'])) {
                        continue;
                    }
                    $address = $this->dclist[$test][$ipv6][$dc_number]['ip_address'];
                    if ($ipv6 === 'ipv6') {
                        $address = "[$address]";
                    }
                    $port = $this->dclist[$test][$ipv6][$dc_number]['port'];
                    foreach (\array_unique([$port, 443, 80, 88, 5222]) as $port) {
                        $stream = \end($combo)[0];
                        if ($stream === HttpsStream::class) {
                            if (\strpos($dc_number, '_cdn') !== false) {
                                continue;
                            }
                            $subdomain = $this->settings->getSslSubdomains()[\preg_replace('/\\D+/', '', $dc_number)];
                            if (\strpos($dc_number, '_media') !== false) {
                                $subdomain .= '-1';
                            }
                            $path = $this->settings->getTestMode() ? 'apiw_test1' : 'apiw1';
                            $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                        } elseif ($stream === HttpStream::class) {
                            $uri = 'tcp://'.$address.':'.$port.'/api';
                        } else {
                            $uri = 'tcp://'.$address.':'.$port;
                        }
                        if ($combo[1][0] === WssStream::class) {
                            $subdomain = $this->settings->getSslSubdomains()[\preg_replace('/\\D+/', '', $dc_number)];
                            if (\strpos($dc_number, '_media') !== false) {
                                $subdomain .= '-1';
                            }
                            $path = $this->settings->getTestMode() ? 'apiws_test' : 'apiws';
                            $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                        } elseif ($combo[1][0] === WsStream::class) {
                            $subdomain = $this->settings->getSslSubdomains()[\preg_replace('/\\D+/', '', $dc_number)];
                            if (\strpos($dc_number, '_media') !== false) {
                                $subdomain .= '-1';
                            }
                            $path = $this->settings->getTestMode() ? 'apiws_test' : 'apiws';
                            //$uri = 'tcp://' . $subdomain . '.web.telegram.org:' . $port . '/' . $path;
                            $uri = 'tcp://'.$address.':'.$port.'/'.$path;
                        }
                        /* @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                        $ctx = (new ConnectionContext())->setDc($dc_number)->setTest($this->settings->getTestMode())->setSocketContext($context)->setUri($uri)->setIpv6($ipv6 === 'ipv6');
                        foreach ($combo as $stream) {
                            if ($stream[0] === DefaultStream::class && $stream[1] === []) {
                                $stream[1] = $useDoH ? new DoHConnector($this, $ctx) : $this->dnsConnector;
                            }
                            if (\in_array($stream[0], [WsStream::class, WssStream::class]) && $stream[1] === []) {
                                if (!\class_exists(Handshake::class)) {
                                    throw new Exception('Please install amphp/websocket-client by running "composer require amphp/websocket-client:dev-master"');
                                }
                                $stream[1] = $this->webSocketConnector;
                            }
                            /** @var array{0: class-string, 1: mixed} $stream */
                            $ctx->addStream(...$stream);
                        }
                        $ctxs[] = $ctx;
                    }
                }
            }
        }
        if (empty($ctxs)) {
            unset($this->sockets[$dc_number]);
            $this->API->logger->logger("No info for DC {$dc_number}", Logger::ERROR);
        } elseif (\defined('MADELINEPROTO_TEST') && \constant("MADELINEPROTO_TEST") === 'pony') {
            return [$ctxs[0]];
        }
        return $ctxs;
    }
    /**
     * Get main API.
     *
     * @return MTProto
     */
    public function getAPI()
    {
        return $this->API;
    }
    /**
     * Get async HTTP client.
     *
     * @return \Amp\Http\Client\HttpClient
     */
    public function getHTTPClient(): HttpClient
    {
        return $this->HTTPClient;
    }
    /**
     * Get async HTTP client cookies.
     *
     * @return \Amp\Http\Client\Cookie\CookieJar
     */
    public function getCookieJar(): CookieJar
    {
        return $this->CookieJar;
    }
    /**
     * Get DNS over HTTPS async DNS client.
     *
     * @return \Amp\Dns\Resolver
     */
    public function getDNSClient(): Resolver
    {
        return $this->DoHClient;
    }
    /**
     * Get non-proxied DNS over HTTPS async DNS client.
     *
     * @return \Amp\Dns\Resolver
     */
    public function getNonProxiedDNSClient(): Resolver
    {
        return $this->nonProxiedDoHClient;
    }
    /**
     * Get contents of file.
     *
     * @param string $url URL to fetch
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, \Amp\Promise<string>, mixed, string>
     */
    public function fileGetContents(string $url): \Generator
    {
        return yield (yield $this->getHTTPClient()->request(new Request($url)))->getBody()->buffer();
    }
    /**
     * Get Connection instance for authorization.
     *
     * @param string $dc DC ID
     *
     * @return Connection
     */
    public function getAuthConnection(string $dc): Connection
    {
        return $this->sockets[$dc]->getAuthConnection();
    }
    /**
     * Get Connection instance.
     *
     * @param string $dc DC ID
     *
     * @return Connection
     */
    public function getConnection(string $dc): Connection
    {
        return $this->sockets[$dc]->getConnection();
    }
    /**
     * Get Connection instance asynchronously.
     *
     * @param string $dc DC ID
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, \Amp\Promise, mixed, Connection>
     */
    public function waitGetConnection(string $dc): \Generator
    {
        return $this->sockets[$dc]->waitGetConnection();
    }
    /**
     * Get DataCenterConnection instance.
     *
     * @param string $dc DC ID
     *
     * @return DataCenterConnection
     */
    public function getDataCenterConnection(string $dc): DataCenterConnection
    {
        return $this->sockets[$dc];
    }
    /**
     * Get all DataCenterConnection instances.
     *
     * @return array<int|string, DataCenterConnection>
     */
    public function getDataCenterConnections(): array
    {
        return $this->sockets;
    }
    /**
     * Check if a DC is present.
     *
     * @param string $dc DC ID
     *
     * @return boolean
     */
    public function has(string $dc): bool
    {
        return isset($this->sockets[$dc]);
    }
    /**
     * Check if connected to datacenter using HTTP.
     *
     * @param string $datacenter DC ID
     *
     * @return boolean
     */
    public function isHttp(string $datacenter)
    {
        return $this->sockets[$datacenter]->isHttp();
    }
    /**
     * Check if connected to datacenter directly using IP address.
     *
     * @param string $datacenter DC ID
     *
     * @return boolean
     */
    public function byIPAddress(string $datacenter): bool
    {
        return $this->sockets[$datacenter]->byIPAddress();
    }
    /**
     * Get all DC IDs.
     *
     * @param boolean $all Whether to get all possible DC IDs, or only connected ones
     *
     * @return array
     */
    public function getDcs($all = true): array
    {
        $test = $this->settings->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->settings->getIpv6() ? 'ipv6' : 'ipv4';
        return $all ? \array_keys((array) $this->dclist[$test][$ipv6]) : \array_keys((array) $this->sockets);
    }
}
