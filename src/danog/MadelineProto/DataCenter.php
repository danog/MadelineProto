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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Artax\Client;
use Amp\Artax\Cookie\ArrayCookieJar;
use Amp\Artax\Cookie\CookieJar;
use Amp\Artax\DefaultClient;
use Amp\Artax\HttpSocketPool;
use Amp\CancellationToken;
use Amp\Deferred;
use Amp\Dns\Record;
use Amp\Dns\Resolver;
use Amp\Dns\Rfc1035StubResolver;
use Amp\DoH\DoHConfig;
use Amp\DoH\Nameserver;
use Amp\DoH\Rfc8484StubResolver;
use Amp\Loop;
use Amp\MultiReasonException;
use Amp\NullCancellationToken;
use Amp\Promise;
use Amp\Socket\ClientConnectContext;
use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectException;
use Amp\Socket\Socket;
use Amp\TimeoutException;
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
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;
use function Amp\call;
use function Amp\Socket\Internal\parseUri;

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
     * @var \Amp\Artax\Client
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
    private $NonProxiedDoHClient;
    /**
     * Cookie jar.
     *
     * @var \Amp\Artax\Cookie\CookieJar
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
     * @param MTProto $API          Main MTProto instance
     * @param array $dclist         DC IP list
     * @param array $settings       Settings
     * @param boolean $reconnectAll Whether to reconnect to all DCs or just to changed ones
     * @param CookieJar $jar        Cookie jar
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
            $test = ($API->get_cached_config()['test_mode'] ?? false) ? 'test' : 'main';
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
                    $socket->needReconnect(true);
                    $socket->setExtra($this->API);
                    $socket->disconnect();
                }
            } else {
                unset($this->sockets[$key]);
            }
        }

        if ($reconnectAll || $changedSettings || !$this->CookieJar) {
            $this->CookieJar = $jar ?? new ArrayCookieJar;
            $this->HTTPClient = new DefaultClient($this->CookieJar, new HttpSocketPool(new ProxySocketPool([$this, 'rawConnectAsync'])));

            $DoHHTTPClient = new DefaultClient(
                $this->CookieJar,
                new HttpSocketPool(
                    new ProxySocketPool(
                    function (string $uri, CancellationToken $token = null, ClientConnectContext $ctx = null) {
                        return $this->rawConnectAsync($uri, $token, $ctx, true);
                    }
                )
                )
            );
            $DoHConfig = new DoHConfig(
                [
                new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'),
                new Nameserver('https://google.com/resolve', Nameserver::GOOGLE_JSON, ["Host" => "dns.google.com"]),
            ],
                $DoHHTTPClient
            );
            $NonProxiedDoHConfig = new DoHConfig(
                [
                new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'),
                new Nameserver('https://google.com/resolve', Nameserver::GOOGLE_JSON, ["Host" => "dns.google.com"]),
            ]
            );
            $this->DoHClient = Magic::$altervista || Magic::$zerowebhost ? new Rfc1035StubResolver() : new Rfc8484StubResolver($DoHConfig);
            $this->NonProxiedDoHClient = Magic::$altervista || Magic::$zerowebhost ? new Rfc1035StubResolver() : new Rfc8484StubResolver($NonProxiedDoHConfig);
        }
    }

    /**
     * Asynchronously establish an encrypted TCP connection (non-blocking).
     *
     * Note: Once resolved the socket stream will already be set to non-blocking mode.
     *
     * @param ConnectionContext    $ctx
     * @param string               $uricall
     * @param ClientConnectContext $socketContext
     * @param ClientTlsContext     $tlsContext
     * @param CancellationToken    $token
     *
     * @return Promise<ClientSocket>
     */
    public function cryptoConnect(
        ConnectionContext $ctx,
        string $uri,
        ClientConnectContext $socketContext = null,
        ClientTlsContext $tlsContext = null,
        CancellationToken $token = null
    ): Promise {
        return call(function () use ($ctx, $uri, $socketContext, $tlsContext, $token) {
            $tlsContext = $tlsContext ?? new ClientTlsContext;

            if ($tlsContext->getPeerName() === null) {
                $tlsContext = $tlsContext->withPeerName(\parse_url($uri, PHP_URL_HOST));
            }

            /** @var ClientSocket $socket */
            $socket = yield $this->socketConnect($ctx, $uri, $socketContext, $token);

            $promise = $socket->enableCrypto($tlsContext);

            if ($token) {
                $deferred = new Deferred;
                $id = $token->subscribe([$deferred, "fail"]);

                $promise->onResolve(function ($exception) use ($id, $token, $deferred) {
                    if ($token->isRequested()) {
                        return;
                    }

                    $token->unsubscribe($id);

                    if ($exception) {
                        $deferred->fail($exception);
                        return;
                    }

                    $deferred->resolve();
                });

                $promise = $deferred->promise();
            }

            try {
                yield $promise;
            } catch (\Throwable $exception) {
                $socket->close();
                throw $exception;
            }

            return $socket;
        });
    }
    /**
     * Asynchronously establish a socket connection to the specified URI.
     *
     * @param ConnectionContext                   $ctx Connection context
     * @param string                 $uri URI in scheme://host:port format. TCP is assumed if no scheme is present.
     * @param ClientConnectContext   $socketContext Socket connect context to use when connecting.
     * @param CancellationToken|null $token
     *
     * @return Promise<\Amp\Socket\ClientSocket>
     */
    public function socketConnect(ConnectionContext $ctx, string $uri, ClientConnectContext $socketContext = null, CancellationToken $token = null): Promise
    {
        return call(function () use ($ctx, $uri, $socketContext, $token) {
            $socketContext = $socketContext ?? new ClientConnectContext;
            $token = $token ?? new NullCancellationToken;
            $attempt = 0;
            $uris = [];
            $failures = [];

            list($scheme, $host, $port) = parseUri($uri);

            if ($host[0] === '[') {
                $host = \substr($host, 1, -1);
            }

            if ($port === 0 || @\inet_pton($host)) {
                // Host is already an IP address or file path.
                $uris = [$uri];
            } else {
                // Host is not an IP address, so resolve the domain name.
                // When we're connecting to a host, we may need to resolve the domain name, first.
                // The resolution is usually done using DNS over HTTPS.
                //
                // The DNS over HTTPS resolver needs to resolve the domain name of the DOH server:
                // this is handled internally by the DNS over HTTPS client,
                // by redirecting the resolution request to the plain DNS client.
                //
                // However, if the DoH connection is proxied with a proxy that has a domain name itself,
                // we cannot resolve it with the DoH resolver, since this will cause an infinite loop
                //
                // resolve host.com => (DoH resolver) => resolve dohserver.com => (simple resolver) => OK
                //
                //                                     |> resolve dohserver.com => (simple resolver) => OK
                // resolve host.com => (DoH resolver) =|
                //                                     |> resolve proxy.com => (non-proxied resolver) => OK
                //
                //
                // This means that we must detect if the domain name we're trying to resolve is a proxy domain name.
                //
                // Here, we simply check if the connection URI has changed since we first set it:
                // this would indicate that a proxy class has changed the connection URI to the proxy URI.
                //
                if ($ctx->isDns()) {
                    $records = yield $this->NonProxiedDoHClient->resolve($host, $socketContext->getDnsTypeRestriction());
                } else {
                    $records = yield $this->DoHClient->resolve($host, $socketContext->getDnsTypeRestriction());
                }
                \usort($records, function (Record $a, Record $b) {
                    return $a->getType() - $b->getType();
                });
                if ($ctx->getIpv6()) {
                    $records = \array_reverse($records);
                }

                foreach ($records as $record) {
                    /** @var Record $record */
                    if ($record->getType() === Record::AAAA) {
                        $uris[] = \sprintf("%s://[%s]:%d", $scheme, $record->getValue(), $port);
                    } else {
                        $uris[] = \sprintf("%s://%s:%d", $scheme, $record->getValue(), $port);
                    }
                }
            }

            $flags = \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_ASYNC_CONNECT;
            $timeout = $socketContext->getConnectTimeout();

            foreach ($uris as $builtUri) {
                try {
                    $context = \stream_context_create($socketContext->toStreamContextArray());

                    if (!$socket = @\stream_socket_client($builtUri, $errno, $errstr, null, $flags, $context)) {
                        throw new ConnectException(\sprintf(
                            "Connection to %s failed: [Error #%d] %s%s",
                            $uri,
                            $errno,
                            $errstr,
                            $failures ? "; previous attempts: ".\implode($failures) : ""
                        ), $errno);
                    }

                    \stream_set_blocking($socket, false);

                    $deferred = new Deferred;
                    $watcher = Loop::onWritable($socket, [$deferred, 'resolve']);
                    $id = $token->subscribe([$deferred, 'fail']);

                    try {
                        yield Promise\timeout($deferred->promise(), $timeout);
                    } catch (TimeoutException $e) {
                        throw new ConnectException(\sprintf(
                            "Connecting to %s failed: timeout exceeded (%d ms)%s",
                            $uri,
                            $timeout,
                            $failures ? "; previous attempts: ".\implode($failures) : ""
                        ), 110); // See ETIMEDOUT in http://www.virtsync.com/c-error-codes-include-errno
                    } finally {
                        Loop::cancel($watcher);
                        $token->unsubscribe($id);
                    }

                    // The following hack looks like the only way to detect connection refused errors with PHP's stream sockets.
                    if (\stream_socket_get_name($socket, true) === false) {
                        \fclose($socket);
                        throw new ConnectException(\sprintf(
                            "Connection to %s refused%s",
                            $uri,
                            $failures ? "; previous attempts: ".\implode($failures) : ""
                        ), 111); // See ECONNREFUSED in http://www.virtsync.com/c-error-codes-include-errno
                    }
                } catch (ConnectException $e) {
                    // Includes only error codes used in this file, as error codes on other OS families might be different.
                    // In fact, this might show a confusing error message on OS families that return 110 or 111 by itself.
                    $knownReasons = [
                        110 => "connection timeout",
                        111 => "connection refused",
                    ];

                    $code = $e->getCode();
                    $reason = $knownReasons[$code] ?? ("Error #".$code);

                    if (++$attempt === $socketContext->getMaxAttempts()) {
                        break;
                    }

                    $failures[] = "{$uri} ({$reason})";

                    continue; // Could not connect to host, try next host in the list.
                }
                if ($ctx->hasReadCallback()) {
                    $socket = new class($socket) extends ClientSocket {
                        private $callback;
                        public function setReadCallback($callback)
                        {
                            $this->callback = $callback;
                        }

                        /** @inheritdoc */
                        public function read(): Promise
                        {
                            $promise = parent::read();
                            $promise->onResolve(function ($e, $res) {
                                if ($res) {
                                    ($this->callback)();
                                }
                            });
                            return $promise;
                        }
                    };
                    $socket->setReadCallback($ctx->getReadCallback());
                } else {
                    $socket = new ClientSocket($socket);
                }

                return $socket;
            }

            // This is reached if either all URIs failed or the maximum number of attempts is reached.
            throw $e;
        });
    }

    public function rawConnectAsync(string $uri, CancellationToken $token = null, ClientConnectContext $ctx = null, $fromDns = false): \Generator
    {
        $ctxs = $this->generateContexts(0, $uri, $ctx);
        if (empty($ctxs)) {
            throw new Exception("No contexts for raw connection to URI $uri");
        }
        foreach ($ctxs as $ctx) {
            /* @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
            try {
                $ctx->setIsDns($fromDns);
                $ctx->setCancellationToken($token);
                $result = yield $ctx->getStream();
                $this->API->logger->logger('OK!', \danog\MadelineProto\Logger::WARNING);

                return $result->getSocket();
            } catch (\Throwable $e) {
                if (\defined('MADELINEPROTO_TEST') && MADELINEPROTO_TEST === 'pony') {
                    throw $e;
                }
                $this->API->logger->logger('Connection failed: '.$e, \danog\MadelineProto\Logger::ERROR);
                if ($e instanceof MultiReasonException) {
                    foreach ($e->getReasons() as $reason) {
                        $this->API->logger->logger('Multireason: '.$reason, \danog\MadelineProto\Logger::ERROR);
                    }
                }
            }
        }

        throw new \danog\MadelineProto\Exception("Could not connect to URI $uri");
    }

    public function dcConnectAsync(string $dc_number, int $id = -1): \Generator
    {
        $old = isset($this->sockets[$dc_number]) && (
            $this->sockets[$dc_number]->shouldReconnect() ||
            ($id !== -1 && $this->sockets[$dc_number]->hasConnection($id) && $this->sockets[$dc_number]->getConnection($id)->shouldReconnect())
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
                if (\defined('MADELINEPROTO_TEST') && MADELINEPROTO_TEST === 'pony') {
                    throw $e;
                }
                $this->API->logger->logger('Connection failed: '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            } catch (\Exception $e) {
                $this->API->logger->logger('Connection failed: '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            }
        }

        throw new \danog\MadelineProto\Exception("Could not connect to DC $dc_number");
    }

    public function generateContexts($dc_number = 0, string $uri = '', ClientConnectContext $context = null)
    {
        $ctxs = [];
        $combos = [];

        $dc_config_number = isset($this->settings[$dc_number]) ? $dc_number : 'all';
        $test = $this->settings[$dc_config_number]['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4';

        switch ($this->settings[$dc_config_number]['protocol']) {
            case 'tcp_abridged':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [AbridgedStream::getName(), []]];
                break;
            case 'tcp_intermediate':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [IntermediateStream::getName(), []]];
                break;
            case 'obfuscated2':
                $this->settings[$dc_config_number]['protocol'] = 'tcp_intermediate_padded';
                $this->settings[$dc_config_number]['obfuscated'] = true;
                // no break
            case 'tcp_intermediate_padded':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [IntermediatePaddedStream::getName(), []]];
                break;
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
                throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_invalid']);
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
                if (!isset(\class_implements($proxy)['danog\\MadelineProto\\Stream\\StreamInterface'])) {
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
        /* @var $context \Amp\ClientConnectContext */
        $context = $context ?? (new ClientConnectContext())->withMaxAttempts(1)->withConnectTimeout(1000 * $this->settings[$dc_config_number]['timeout']);

        foreach ($combos as $combo) {
            $ipv6 = [$this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4', $this->settings[$dc_config_number]['ipv6'] ? 'ipv4' : 'ipv6'];

            foreach ($ipv6 as $ipv6) {
                // This is only for non-MTProto connections
                if (!$dc_number) {
                    /** @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                    $ctx = (new ConnectionContext())
                        ->setSocketContext($context)
                        ->setUri($uri)
                        ->setIpv6($ipv6 === 'ipv6');

                    foreach ($combo as $stream) {
                        if ($stream[0] === DefaultStream::getName() && $stream[1] === []) {
                            $stream[1] = [
                                function (
                                    string $uri,
                                    ClientConnectContext $socketContext = null,
                                    CancellationToken $token = null
                                ) use ($ctx): Promise {
                                    return $this->socketConnect($ctx, $uri, $socketContext, $token);
                                },
                                function (
                                    string $uri,
                                    ClientConnectContext $socketContext = null,
                                    ClientTlsContext $tlsContext = null,
                                    CancellationToken $token = null
                                ) use ($ctx): Promise {
                                    return $this->cryptoConnect($ctx, $uri, $socketContext, $tlsContext, $token);
                                },
                            ];
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

                        $uri = 'tcp://'.$subdomain.'.'.'web.telegram.org'.':'.$port.'/'.$path;
                    } elseif ($combo[1][0] === WsStream::getName()) {
                        $subdomain = $this->dclist['ssl_subdomains'][\preg_replace('/\D+/', '', $dc_number)];
                        if (\strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiws_test' : 'apiws';

                        //$uri = 'tcp://' . $subdomain . '.web.telegram.org:' . $port . '/' . $path;
                        $uri = 'tcp://'.$address.':'.$port.'/'.$path;
                    }

                    /** @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                    $ctx = (new ConnectionContext())
                        ->setDc($dc_number)
                        ->setTest($this->settings[$dc_config_number]['test_mode'])
                        ->setSocketContext($context)
                        ->setUri($uri)
                        ->setIpv6($ipv6 === 'ipv6');

                    foreach ($combo as $stream) {
                        if ($stream[0] === DefaultStream::getName() && $stream[1] === []) {
                            $stream[1] = [
                                function (
                                    string $uri,
                                    ClientConnectContext $socketContext = null,
                                    CancellationToken $token = null
                                ) use ($ctx): Promise {
                                    return $this->socketConnect($ctx, $uri, $socketContext, $token);
                                },
                                function (
                                    string $uri,
                                    ClientConnectContext $socketContext = null,
                                    ClientTlsContext $tlsContext = null,
                                    CancellationToken $token = null
                                ) use ($ctx): Promise {
                                    return $this->cryptoConnect($ctx, $uri, $socketContext, $tlsContext, $token);
                                },
                            ];
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
        } elseif (\defined('MADELINEPROTO_TEST') && MADELINEPROTO_TEST === 'pony') {
            return [$ctxs[0]];
        }

        return $ctxs;
    }

    /**
     * Get Artax async HTTP client.
     *
     * @return \Amp\Artax\Client
     */
    public function getHTTPClient(): Client
    {
        return $this->HTTPClient;
    }

    /**
     * Get Artax async HTTP client.
     *
     * @return \Amp\Artax\CookieJar
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

    public function fileGetContents($url): \Generator
    {
        return yield (yield $this->getHTTPClient()->request($url))->getBody();
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
    public function get_dcs($all = true): array
    {
        $test = $this->settings['all']['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings['all']['ipv6'] ? 'ipv6' : 'ipv4';

        return $all ? \array_keys((array) $this->dclist[$test][$ipv6]) : \array_keys((array) $this->sockets);
    }
}
