<?php

declare(strict_types=1);

/**
 * DoH module.
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

namespace danog\MadelineProto;

use Amp\Dns\DnsConfig;
use Amp\Dns\DnsConfigLoader;
use Amp\Dns\Resolver;
use Amp\Dns\Rfc1035StubResolver;
use Amp\Dns\UnixDnsConfigLoader;
use Amp\Dns\WindowsDnsConfigLoader;
use Amp\DoH\DoHConfig;
use Amp\DoH\Nameserver;
use Amp\DoH\Rfc8484StubResolver;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Cookie\CookieInterceptor;
use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\Cookie\LocalCookieJar;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ConnectContext;
use Amp\Socket\DnsSocketConnector;
use Amp\Websocket\Client\Rfc6455ConnectionFactory;
use Amp\Websocket\Client\Rfc6455Connector;
use danog\MadelineProto\Settings\Connection;
use danog\MadelineProto\Stream\ConnectionContext;
use Throwable;

/**
 * @psalm-import-type TDcList from DataCenter
 */
final class DoHWrapper
{
    /**
     * HTTP client.
     *
     */
    public readonly HttpClient $HTTPClient;
    /**
     * DNS over HTTPS client.
     *
     */
    public readonly Resolver $DoHClient;
    /**
     * Non-proxied DNS over HTTPS client.
     *
     */
    public readonly Resolver $nonProxiedDoHClient;
    /**
     * Cookie jar.
     *
     */
    public readonly CookieJar $CookieJar;
    /**
     * DNS connector.
     */
    public readonly DnsSocketConnector $dnsConnector;
    /**
     * DoH connector.
     */
    public readonly Rfc6455Connector $webSocketConnector;

    public function __construct(
        private Connection $settings,
        private LoggerGetter $loggerGetter,
        ?CookieJar $jar = null
    ) {
        $configProvider = new class implements DnsConfigLoader {
            public function loadConfig(): DnsConfig
            {
                $loader = \stripos(PHP_OS, 'win') === 0 ? new WindowsDnsConfigLoader() : new UnixDnsConfigLoader();
                try {
                    return $loader->loadConfig();
                } catch (Throwable) {
                    return new DnsConfig([
                        '1.1.1.1',
                        '1.0.0.1',
                        '[2606:4700:4700::1111]',
                        '[2606:4700:4700::1001]',
                    ]);
                }
            }
        };

        $this->CookieJar = $jar ?? new LocalCookieJar();
        $this->HTTPClient = (new HttpClientBuilder())->interceptNetwork(new CookieInterceptor($this->CookieJar))->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($settings, $loggerGetter))))->build();
        $DoHHTTPClient = (new HttpClientBuilder())->interceptNetwork(new CookieInterceptor($this->CookieJar))->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($settings, $loggerGetter, true))))->build();
        $DoHConfig = new DoHConfig([new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'), new Nameserver('https://dns.google/resolve')], $DoHHTTPClient);
        $nonProxiedDoHConfig = new DoHConfig([new Nameserver('https://mozilla.cloudflare-dns.com/dns-query'), new Nameserver('https://dns.google/resolve')]);
        $this->DoHClient = Magic::$altervista || Magic::$zerowebhost || !$settings->getUseDoH()
            ? new Rfc1035StubResolver(null, $configProvider)
            : new Rfc8484StubResolver($DoHConfig);
        $this->nonProxiedDoHClient = Magic::$altervista || Magic::$zerowebhost || !$settings->getUseDoH()
            ? new Rfc1035StubResolver(null, $configProvider)
            : new Rfc8484StubResolver($nonProxiedDoHConfig);

        $this->dnsConnector = new DnsSocketConnector(new Rfc1035StubResolver(null, $configProvider));
        $this->webSocketConnector = new Rfc6455Connector(
            new Rfc6455ConnectionFactory(),
            $this->HTTPClient
        );
    }

    /**
     * Generate contexts.
     *
     * @param TDcList|null $dcList
     * @param integer        $dc_number DC ID to generate contexts for
     * @param string         $uri       URI
     * @param ConnectContext $context   Connection context
     *
     * @return ConnectionContext[]
     */
    public function generateContexts(?array $dclist = null, int $dc_number = 0, string $uri = '', ConnectContext $context = null): array
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
            $only = $dclist[$test][$ipv6][$dc_number]['tcpo_only'] ?? false;
            if ($only || isset($dclist[$test][$ipv6][$dc_number]['secret'])) {
                $extra = isset($dclist[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $dclist[$test][$ipv6][$dc_number]['secret']] : [];
                $combo = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, $extra], [IntermediatePaddedStream::class, []]];
                if ($only) {
                    \array_unshift($combos, $combo);
                } else {
                    $combos []= $combo;
                }
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
        $context = $context ?? (new ConnectContext())->withConnectTimeout(1000 * $this->settings->getTimeout())->withBindTo($this->settings->getBindTo());
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
                    if (!isset($dclist[$test][$ipv6][$dc_number]['ip_address'])) {
                        continue;
                    }
                    $address = $dclist[$test][$ipv6][$dc_number]['ip_address'];
                    if ($ipv6 === 'ipv6') {
                        $address = "[$address]";
                    }
                    $port = $dclist[$test][$ipv6][$dc_number]['port'];
                    foreach (\array_unique([$port, 443, 80, 88, 5222]) as $port) {
                        $stream = \end($combo)[0];
                        if ($stream === HttpsStream::class) {
                            $subdomain = $this->settings->getSslSubdomains()[$dc_number] ?? null;
                            if (!$subdomain) {
                                continue;
                            }
                            if (DataCenter::isMedia($dc_number)) {
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
                            $subdomain = $this->settings->getSslSubdomains()[$dc_number] ?? null;
                            if (!$subdomain) {
                                continue;
                            }
                            if (DataCenter::isMedia($dc_number)) {
                                $subdomain .= '-1';
                            }
                            $path = $this->settings->getTestMode() ? 'apiws_test' : 'apiws';
                            $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                        } elseif ($combo[1][0] === WsStream::class) {
                            $subdomain = $this->settings->getSslSubdomains()[$dc_number];
                            if (DataCenter::isMedia($dc_number)) {
                                $subdomain .= '-1';
                            }
                            $path = $this->settings->getTestMode() ? 'apiws_test' : 'apiws';
                            //$uri = 'tcp://' . $subdomain . '.web.telegram.org:' . $port . '/' . $path;
                            $uri = 'tcp://'.$address.':'.$port.'/'.$path;
                        }
                        $ctx = (new ConnectionContext())
                            ->setDc($dc_number)
                            ->setCdn($this->settings->getTestMode())
                            ->setSocketContext($context)
                            ->setUri($uri)
                            ->setIpv6($ipv6 === 'ipv6')
                        ;
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
        return $ctxs;
    }
}
