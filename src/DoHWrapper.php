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
use Amp\Dns\DnsResolver;
use Amp\Dns\Rfc1035StubDnsResolver;
use Amp\Dns\UnixDnsConfigLoader;
use Amp\Dns\WindowsDnsConfigLoader;
use Amp\DoH\DoHConfig;
use Amp\DoH\DoHNameserver;
use Amp\DoH\Rfc8484StubDoHResolver;
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
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;
use Throwable;

/**
 * @psalm-import-type TDcList from DataCenter
 * @internal
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
    public readonly DnsResolver $DoHClient;
    /**
     * Non-proxied DNS over HTTPS client.
     *
     */
    public readonly DnsResolver $nonProxiedDoHClient;
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
        private SettingsGetter&LoggerGetter $API,
        ?CookieJar $jar = null
    ) {
        $configProvider = new class implements DnsConfigLoader {
            public function loadConfig(): DnsConfig
            {
                $loader = stripos(PHP_OS, 'win') === 0 ? new WindowsDnsConfigLoader() : new UnixDnsConfigLoader();
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
        $this->HTTPClient = (new HttpClientBuilder())->interceptNetwork(new CookieInterceptor($this->CookieJar))->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($this, $API))))->build();
        $DoHHTTPClient = (new HttpClientBuilder())->interceptNetwork(new CookieInterceptor($this->CookieJar))->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(new ContextConnector($this, $API, true))))->build();
        $DoHConfig = new DoHConfig([new DoHNameserver('https://mozilla.cloudflare-dns.com/dns-query'), new DoHNameserver('https://dns.google/resolve')], $DoHHTTPClient);
        $nonProxiedDoHConfig = new DoHConfig([new DoHNameserver('https://mozilla.cloudflare-dns.com/dns-query'), new DoHNameserver('https://dns.google/resolve')]);
        $this->DoHClient = Magic::$altervista || Magic::$zerowebhost || !$API->getSettings()->getConnection()->getUseDoH()
            ? new Rfc1035StubDnsResolver(null, $configProvider)
            : new Rfc8484StubDoHResolver($DoHConfig);
        $this->nonProxiedDoHClient = Magic::$altervista || Magic::$zerowebhost || !$API->getSettings()->getConnection()->getUseDoH()
            ? new Rfc1035StubDnsResolver(null, $configProvider)
            : new Rfc8484StubDoHResolver($nonProxiedDoHConfig);

        $this->dnsConnector = new DnsSocketConnector(new Rfc1035StubDnsResolver(null, $configProvider));
        $this->webSocketConnector = new Rfc6455Connector(
            new Rfc6455ConnectionFactory(),
            $this->HTTPClient
        );
    }

    /**
     * Generate contexts.
     *
     * @return ConnectionContext[]
     */
    public function generateContexts(string $uri, ?ConnectContext $context = null): array
    {
        $ctxs = [];
        $combos = [
            [[DefaultStream::class, []], [BufferedRawStream::class, []]],
        ];
        if ($this->API->getSettings()->getConnection()->getRetry()) {
            $proxyCombos = [];
            foreach ($this->API->getSettings()->getConnection()->getProxies() as $proxy => $extras) {
                if ($proxy === ObfuscatedStream::class) {
                    continue;
                }
                foreach ($extras as $extra) {
                    foreach ($combos as $orig) {
                        $combo = [];
                        if ($orig[1][0] === BufferedRawStream::class) {
                            [$first, $second] = [\array_slice($orig, 0, 2), \array_slice($orig, 2)];
                            $first[] = [$proxy, $extra];
                            $combo = array_merge($first, $second);
                        } elseif (\in_array($orig[1][0], [WsStream::class, WssStream::class], true)) {
                            [$first, $second] = [\array_slice($orig, 0, 1), \array_slice($orig, 1)];
                            $first[] = [BufferedRawStream::class, []];
                            $first[] = [$proxy, $extra];
                            $combo = array_merge($first, $second);
                        }
                        $proxyCombos []= $combo;
                    }
                }
            }
            $combos = array_merge($proxyCombos, $combos);
            $combos = array_unique($combos, SORT_REGULAR);
        }

        $context ??= (new ConnectContext())->withConnectTimeout($this->API->getSettings()->getConnection()->getTimeout())->withBindTo($this->API->getSettings()->getConnection()->getBindTo());
        foreach ($combos as $combo) {
            foreach ([true, false] as $useDoH) {
                $ipv6Combos = [
                    $this->API->getSettings()->getConnection()->getIpv6(),
                    !$this->API->getSettings()->getConnection()->getIpv6(),
                ];
                foreach ($ipv6Combos as $ipv6) {
                    $ctx = (new ConnectionContext())->setSocketContext($context)->setUri($uri)->setIpv6($ipv6);
                    foreach ($combo as $stream) {
                        if ($stream[0] === DefaultStream::class && $stream[1] === []) {
                            $stream[1] = $useDoH ? new DoHConnector($this, $ctx) : $this->dnsConnector;
                        }
                        $ctx->addStream(...$stream);
                    }
                    $ctxs[] = $ctx;
                }
            }
        }
        return $ctxs;
    }
}
