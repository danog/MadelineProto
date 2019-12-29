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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
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
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Promise;
use Amp\Socket\ConnectContext;
use Amp\Websocket\Client\Rfc6455Connector;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Proxy\HttpProxy;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use danog\MadelineProto\Stream\StreamInterface;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;

/**
 * Manages datacenters.
 */
class DataCenter
{
    use \danog\MadelineProto\Tools;
    use \danog\Serializable;
    /**
     * All socket connections to DCs.
     *
     * @var array<string, DataCenterConnection>
     */
    public $sockets = [];
    /**
     * Current DC ID.
     *
     * @var string
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
     * @var array
     */
    private $settings = [];
    /**
     * HTTP client.
     *
     * @var \Amp\Http\Client\DelegateHttpClient
     */
    private $HTTPClient;
    /**
     * DNS over HTTPS client.
     *
     * @var \Amp\DoH\Rfc8484StubResolver
     */
    private $DoHClient;
    /**
     * Non-proxied DNS over HTTPS client.
     *
     * @var \Amp\DoH\Rfc8484StubResolver
     */
    private $nonProxiedDoHClient;
    /**
     * Cookie jar.
     *
     * @var \Amp\Http\Client\Cookie\CookieJar
     */
    private $CookieJar;

    public function __sleep()
    {
        return ['sockets', 'curdc', 'dclist', 'settings'];
    }

    public function __wakeup()
    {
        $array = [];
        foreach ($this->sockets as $id => $socket) {
            if ($socket instanceof Connection) {
                if ($socket->temp_auth_key) {
                    $array[$id]['tempAuthKey'] = $socket->temp_auth_key;
                }
                if ($socket->auth_key) {
                    $array[$id]['permAuthKey'] = $socket->auth_key;
                    $array[$id]['permAuthKey']['authorized'] = $socket->authorized;
                }
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
            $connection = $this->sockets[$id] = new DataCenterConnection;
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
     * @param MTProto   $API          Main MTProto instance
     * @param array     $dclist       DC IP list
     * @param array     $settings     Settings
     * @param boolean   $reconnectAll Whether to reconnect to all DCs or just to changed ones
     * @param CookieJar $jar          Cookie jar
     *
     * @return void
     */
    public function __magic_construct($API, array $dclist, array $settings, bool $reconnectAll = true, CookieJar $jar = null)
    {
        $this->API = $API;

        $changed = [];
        $changedSettings = $this->settings !== $settings;
        if (!$reconnectAll) {
            $changed = [];
            $test = ($API->getCachedConfig()['test_mode'] ?? false) ? 'test' : 'main';
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
                //$this->API->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['dc_con_stop'], $key), \danog\MadelineProto\Logger::VERBOSE);
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
            $this->CookieJar = $jar ?? new InMemoryCookieJar;
            $this->HTTPClient = (new HttpClientBuilder)
                ->interceptNetwork(new CookieInterceptor($this->CookieJar))
                ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($this))))
                ->build();

            $DoHHTTPClient = (new HttpClientBuilder)
                ->interceptNetwork(new CookieInterceptor($this->CookieJar))
                ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($this, true))))
                ->build();

            $DoHConfig = new DoHConfig(
                [
                    new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'),
                    new Nameserver('https://dns.google/resolve'),
                ],
                $DoHHTTPClient
            );
            $nonProxiedDoHConfig = new DoHConfig(
                [
                    new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'),
                    new Nameserver('https://dns.google/resolve'),
                ]
            );
            $this->DoHClient = Magic::$altervista || Magic::$zerowebhost ?
                new Rfc1035StubResolver() :
                new Rfc8484StubResolver($DoHConfig);

            $this->nonProxiedDoHClient = Magic::$altervista || Magic::$zerowebhost ?
                new Rfc1035StubResolver() :
                new Rfc8484StubResolver($nonProxiedDoHConfig);
        }
    }

    public function dcConnect(string $dc_number, int $id = -1): \Generator
    {
        $old = isset($this->sockets[$dc_number]) && (
            $this->sockets[$dc_number]->shouldReconnect() ||
            (
                $id !== -1
                && $this->sockets[$dc_number]->hasConnection($id)
                && $this->sockets[$dc_number]->getConnection($id)->shouldReconnect()
            )
        );
        if (isset($this->sockets[$dc_number]) && !$old) {
            $this->API->logger("Not reconnecting to DC $dc_number ($id)");
            return false;
        }
        $ctxs = $this->generateContexts($dc_number);

        if (empty($ctxs)) {
            return false;
        }
        foreach ($ctxs as $ctx) {
            try {
                if ($old) {
                    $this->API->logger->logger("Reconnecting to DC $dc_number ($id) from existing", \danog\MadelineProto\Logger::WARNING);
                    $this->sockets[$dc_number]->setExtra($this->API);
                    yield $this->sockets[$dc_number]->connect($ctx, $id);
                } else {
                    $this->API->logger->logger("Connecting to DC $dc_number from scratch", \danog\MadelineProto\Logger::WARNING);
                    $this->sockets[$dc_number] = new DataCenterConnection();
                    $this->sockets[$dc_number]->setExtra($this->API);
                    yield $this->sockets[$dc_number]->connect($ctx);
                }
                $this->API->logger->logger('OK!', \danog\MadelineProto\Logger::WARNING);

                return true;
            } catch (\Throwable $e) {
                if (\MADELINEPROTO_TEST === 'pony') {
                    throw $e;
                }
                $this->API->logger->logger('Connection failed: '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            }
        }

        throw new \danog\MadelineProto\Exception("Could not connect to DC $dc_number");
    }

    public function generateContexts($dc_number = 0, string $uri = '', ConnectContext $context = null)
    {
        $ctxs = [];
        $combos = [];

        $dc_config_number = isset($this->settings[$dc_number]) ? $dc_number : 'all';
        $test = $this->settings[$dc_config_number]['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4';

        switch ($this->settings[$dc_config_number]['protocol']) {
            case 'abridged':
            case 'tcp_abridged':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [AbridgedStream::getName(), []]];
                break;
            case 'intermediate':
            case 'tcp_intermediate':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [IntermediateStream::getName(), []]];
                break;
            case 'obfuscated2':
                $this->settings[$dc_config_number]['protocol'] = 'tcp_intermediate_padded';
                $this->settings[$dc_config_number]['obfuscated'] = true;
                // no break
            case 'intermediate_padded':
            case 'tcp_intermediate_padded':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [IntermediatePaddedStream::getName(), []]];
                break;
            case 'full':
            case 'tcp_full':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [FullStream::getName(), []]];
                break;
            case 'http':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [HttpStream::getName(), []]];
                break;
            case 'https':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [HttpsStream::getName(), []]];
                break;
            default:
                throw new Exception(Lang::$current_lang['protocol_invalid']);
        }
        if ($this->settings[$dc_config_number]['obfuscated'] && !\in_array($default[2][0], [HttpsStream::getName(), HttpStream::getName()])) {
            $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], \end($default)];
        }
        if ($this->settings[$dc_config_number]['transport'] && !\in_array($default[2][0], [HttpsStream::getName(), HttpStream::getName()])) {
            switch ($this->settings[$dc_config_number]['transport']) {
                case 'tcp':
                    if ($this->settings[$dc_config_number]['obfuscated']) {
                        $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], \end($default)];
                    }
                    break;
                case 'wss':
                    $default = [[DefaultStream::getName(), []], [WssStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], \end($default)];
                    break;
                case 'ws':
                    $default = [[DefaultStream::getName(), []], [WsStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], \end($default)];
                    break;
            }
        }
        if (!$dc_number) {
            $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []]];
        }
        $combos[] = $default;

        if (!isset($this->settings[$dc_config_number]['do_not_retry'])) {
            if ((isset($this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) && $this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) || isset($this->dclist[$test][$ipv6][$dc_number]['secret'])) {
                $extra = isset($this->dclist[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $this->dclist[$test][$ipv6][$dc_number]['secret']] : [];
                $combos[] = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), $extra], [IntermediatePaddedStream::getName(), []]];
            }

            if (\is_iterable($this->settings[$dc_config_number]['proxy'])) {
                $proxies = $this->settings[$dc_config_number]['proxy'];
                $proxy_extras = $this->settings[$dc_config_number]['proxy_extra'];
            } else {
                $proxies = [$this->settings[$dc_config_number]['proxy']];
                $proxy_extras = [$this->settings[$dc_config_number]['proxy_extra']];
            }
            foreach ($proxies as $key => $proxy) {
                // Convert old settings
                if ($proxy === '\\Socket') {
                    $proxy = DefaultStream::getName();
                }
                if ($proxy === '\\SocksProxy') {
                    $proxy = SocksProxy::getName();
                }
                if ($proxy === '\\HttpProxy') {
                    $proxy = HttpProxy::getName();
                }
                if ($proxy === '\\MTProxySocket') {
                    $proxy = ObfuscatedStream::getName();
                }
                if ($proxy === DefaultStream::getName()) {
                    continue;
                }
                if (!$dc_number && $proxy === ObfuscatedStream::getName()) {
                    continue;
                }
                $extra = $proxy_extras[$key];
                if (!isset(\class_implements($proxy)[StreamInterface::class])) {
                    throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['proxy_class_invalid']);
                }
                if ($proxy === ObfuscatedStream::getName() && \in_array(\strlen($extra['secret']), [17, 34])) {
                    $combos[] = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [$proxy, $extra], [IntermediatePaddedStream::getName(), []]];
                }
                foreach ($combos as $k => $orig) {
                    $combo = [];
                    if ($proxy === ObfuscatedStream::getName()) {
                        $combo = $orig;
                        if ($combo[\count($combo) - 2][0] === ObfuscatedStream::getName()) {
                            $combo[\count($combo) - 2][1] = $extra;
                        } else {
                            $mtproto = \end($combo);
                            $combo[\count($combo) - 1] = [$proxy, $extra];
                            $combo[] = $mtproto;
                        }
                    } else {
                        if ($orig[1][0] === BufferedRawStream::getName()) {
                            list($first, $second) = [\array_slice($orig, 0, 2), \array_slice($orig, 2)];
                            $first[] = [$proxy, $extra];
                            $combo = \array_merge($first, $second);
                        } elseif (\in_array($orig[1][0], [WsStream::getName(), WssStream::getName()])) {
                            list($first, $second) = [\array_slice($orig, 0, 1), \array_slice($orig, 1)];
                            $first[] = [BufferedRawStream::getName(), []];
                            $first[] = [$proxy, $extra];
                            $combo = \array_merge($first, $second);
                        }
                    }

                    \array_unshift($combos, $combo);
                    //unset($combos[$k]);
                }
            }

            if ($dc_number) {
                $combos[] = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [HttpsStream::getName(), []]];
            }
            $combos = \array_unique($combos, SORT_REGULAR);
        }
        /* @var $context \Amp\ConnectContext */
        $context = $context ?? (new ConnectContext())->withMaxAttempts(1)->withConnectTimeout(1000 * $this->settings[$dc_config_number]['timeout']);

        foreach ($combos as $combo) {
            $ipv6 = [$this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4', $this->settings[$dc_config_number]['ipv6'] ? 'ipv4' : 'ipv6'];

            foreach ($ipv6 as $ipv6) {
                // This is only for non-MTProto connections
                if (!$dc_number) {
                    /* @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                    $ctx = (new ConnectionContext())
                        ->setSocketContext($context)
                        ->setUri($uri)
                        ->setIpv6($ipv6 === 'ipv6');

                    foreach ($combo as $stream) {
                        if ($stream[0] === DefaultStream::getName() && $stream[1] === []) {
                            $stream[1] = new DoHConnector($this, $ctx);
                        }
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
                $port = $this->dclist[$test][$ipv6][$dc_number]['port'];

                foreach (\array_unique([$port, 443, 80, 88, 5222]) as $port) {
                    $stream = \end($combo)[0];

                    if ($stream === HttpsStream::getName()) {
                        $subdomain = $this->dclist['ssl_subdomains'][\preg_replace('/\D+/', '', $dc_number)];
                        if (\strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiw_test1' : 'apiw1';

                        $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                    } elseif ($stream === HttpStream::getName()) {
                        $uri = 'tcp://'.$address.':'.$port.'/api';
                    } else {
                        $uri = 'tcp://'.$address.':'.$port;
                    }

                    if ($combo[1][0] === WssStream::getName()) {
                        $subdomain = $this->dclist['ssl_subdomains'][\preg_replace('/\D+/', '', $dc_number)];
                        if (\strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiws_test' : 'apiws';

                        $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                    } elseif ($combo[1][0] === WsStream::getName()) {
                        $subdomain = $this->dclist['ssl_subdomains'][\preg_replace('/\D+/', '', $dc_number)];
                        if (\strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiws_test' : 'apiws';

                        //$uri = 'tcp://' . $subdomain . '.web.telegram.org:' . $port . '/' . $path;
                        $uri = 'tcp://'.$address.':'.$port.'/'.$path;
                    }

                    /* @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                    $ctx = (new ConnectionContext())
                        ->setDc($dc_number)
                        ->setTest($this->settings[$dc_config_number]['test_mode'])
                        ->setSocketContext($context)
                        ->setUri($uri)
                        ->setIpv6($ipv6 === 'ipv6');

                    foreach ($combo as $stream) {
                        if ($stream[0] === DefaultStream::getName() && $stream[1] === []) {
                            $stream[1] = new DoHConnector($this, $ctx);
                        }
                        if (\in_array($stream[0], [WsStream::class, WssStream::class]) && $stream[1] === []) {
                            $stream[1] = new Rfc6455Connector($this->HTTPClient);
                        }
                        $ctx->addStream(...$stream);
                    }
                    $ctxs[] = $ctx;
                }
            }
        }

        if (isset($this->dclist[$test][$ipv6][$dc_number.'_bk']['ip_address'])) {
            $ctxs = \array_merge($ctxs, $this->generateContexts($dc_number.'_bk'));
        }

        if (empty($ctxs)) {
            unset($this->sockets[$dc_number]);

            $this->API->logger->logger("No info for DC $dc_number", \danog\MadelineProto\Logger::ERROR);
        } elseif (\MADELINEPROTO_TEST === 'pony') {
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
     * @return \Amp\Http\Client\DelegateHttpClient
     */
    public function getHTTPClient(): DelegateHttpClient
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
     * @return \Generator<string>
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
     * @return Promise<Connection>
     */
    public function waitGetConnection(string $dc): Promise
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
     * @return array<string, DataCenterConnection>
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
        $test = $this->settings['all']['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings['all']['ipv6'] ? 'ipv6' : 'ipv4';

        return $all ? \array_keys((array) $this->dclist[$test][$ipv6]) : \array_keys((array) $this->sockets);
    }
}
