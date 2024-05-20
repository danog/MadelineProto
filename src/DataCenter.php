<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Dns\DnsResolver;
use Amp\Http\Client\HttpClient;
use Amp\Socket\ConnectContext;
use Amp\Socket\InternetAddress;
use Amp\Socket\InternetAddressVersion;
use Amp\Sync\LocalKeyedMutex;
use AssertionError;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\UdpBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ContextIterator;
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
use Revolt\EventLoop;

/**
 * @psalm-type TDcOption=array{
 *      _: 'dcOption',
 *      cdn: bool,
 *      this_port_only: bool,
 *      tcpo_only: bool,
 *      ip_address: string,
 *      port: int,
 *      secret?: string
 * }
 * @psalm-type TDcList=array{
 *      test: array{
 *          ipv4: non-empty-array<int, TDcOption>,
 *          ipv6: non-empty-array<int, TDcOption>,
 *      },
 *      main: array{
 *          ipv4: non-empty-array<int, TDcOption>,
 *          ipv6: non-empty-array<int, TDcOption>,
 *      },
 * }
 * @internal Manages datacenters.
 */
final class DataCenter
{
    /**
     * All socket connections to DCs.
     *
     * @var array<int, DataCenterConnection>
     */
    private array $sockets = [];
    /**
     * Current DC ID.
     */
    public int $currentDatacenter = 1;
    /**
     * Main instance.
     */
    private MTProto $API;
    private DoHWrapper $dohWrapper;

    private LocalKeyedMutex $connectMutex;
    /**
     * Constructor function.
     */
    public function __construct(MTProto $API)
    {
        $this->connectMutex = new LocalKeyedMutex;
        $this->API = $API;
        if ($this->getSettings()->hasChanged()) {
            unset($this->dohWrapper);
        }
        $this->dohWrapper ??= new DoHWrapper($API);
        if ($this->getSettings()->hasChanged()) {
            foreach ($this->sockets as $dc => $socket) {
                if (\is_string($dc)) {
                    continue;
                }
                $socket->setExtra($this->API, $dc, $this->generateContexts($dc));
                $socket->reconnect();
            }
            $this->getSettings()->applyChanges();
        }
    }

    public function __sleep()
    {
        return isset($this->API) ? ['sockets', 'currentDatacenter', 'API'] : ['sockets', 'currentDatacenter'];
    }
    public static function isTest(int $dc): bool
    {
        return abs($dc) > 10000;
    }
    public static function isMedia(int $dc): bool
    {
        return $dc < 0;
    }
    private function getSettings(): \danog\MadelineProto\Settings\Connection
    {
        return $this->API->getSettings()->getConnection();
    }
    public function getHTTPClient(): HttpClient
    {
        return $this->dohWrapper->HTTPClient;
    }

    public function getDNSClient(): DnsResolver
    {
        return $this->dohWrapper->DoHClient;
    }

    /**
     * Normalizes "bindto" options to add a ":0" in case no port is present, otherwise PHP will silently ignore those.
     *
     * @throws \Error If an invalid option has been passed.
     *
     * @internal
     */
    private static function normalizeBindToOption(?string $bindTo = null): ?string
    {
        if ($bindTo === null) {
            return null;
        }

        if (preg_match("/\\[(?P<ip>[0-9a-f:]+)](:(?P<port>\\d+))?$/", $bindTo, $match)) {
            $ip = $match['ip'];
            $port = (int) ($match['port'] ?? 0);

            if (inet_pton($ip) === false) {
                throw new \Error("Invalid IPv6 address: $ip");
            }

            if ($port < 0 || $port > 65535) {
                throw new \Error("Invalid port: $port");
            }

            return "[$ip]:$port";
        }

        if (preg_match("/(?P<ip>\\d+\\.\\d+\\.\\d+\\.\\d+)(:(?P<port>\\d+))?$/", $bindTo, $match)) {
            $ip = $match['ip'];
            $port = (int) ($match['port'] ?? 0);

            if (inet_pton($ip) === false) {
                throw new \Error("Invalid IPv4 address: $ip");
            }

            if ($port < 0 || $port > 65535) {
                throw new \Error("Invalid port: $port");
            }

            return "$ip:$port";
        }

        throw new \Error("Invalid bindTo value: $bindTo");
    }

    /**
     * Generate contexts.
     *
     * @param integer $dc_number DC ID to generate contexts for
     */
    private function generateContexts(int $dc_number): ContextIterator
    {
        $settings = $this->getSettings();
        $test = $settings->getTestMode() ? 'test' : 'main';
        $ipv6 = $settings->getIpv6() ? 'ipv6' : 'ipv4';
        if (!isset($this->API->dcList[$test][$ipv6][$dc_number])) {
            throw new AssertionError("No info for DC $dc_number!");
        }

        $ctxs = [];
        $combos = [];
        $default = match ($settings->getProtocol()) {
            AbridgedStream::class =>
                [[DefaultStream::class, []], [BufferedRawStream::class, []], [AbridgedStream::class, []]],
            IntermediateStream::class =>
                [[DefaultStream::class, []], [BufferedRawStream::class, []], [IntermediateStream::class, []]],
            IntermediatePaddedStream::class =>
                [[DefaultStream::class, []], [BufferedRawStream::class, []], [IntermediatePaddedStream::class, []]],
            FullStream::class =>
                [[DefaultStream::class, []], [BufferedRawStream::class, []], [FullStream::class, []]],
            HttpStream::class =>
                [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpStream::class, []]],
            HttpsStream::class =>
                [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpsStream::class, []]],
            UdpBufferedStream::class =>
                [[DefaultStream::class, []], [UdpBufferedStream::class, []]],
        };
        if ($settings->getObfuscated() && !\in_array($default[2][0], [HttpsStream::class, HttpStream::class], true)) {
            $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], end($default)];
        }
        if (!\in_array($default[2][0], [HttpsStream::class, HttpStream::class], true)) {
            switch ($settings->getTransport()) {
                case DefaultStream::class:
                    if ($settings->getObfuscated()) {
                        $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], end($default)];
                    }
                    break;
                case WssStream::class:
                    $default = [[DefaultStream::class, []], [WssStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], end($default)];
                    break;
                case WsStream::class:
                    $default = [[DefaultStream::class, []], [WsStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], end($default)];
                    break;
            }
        }
        $combos[] = $default;

        $only = $this->API->dcList[$test][$ipv6][$dc_number]['tcpo_only'];
        if ($only || isset($this->API->dcList[$test][$ipv6][$dc_number]['secret'])) {
            $extra = isset($this->API->dcList[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $this->API->dcList[$test][$ipv6][$dc_number]['secret']] : [];
            $combo = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, $extra], [IntermediatePaddedStream::class, []]];
            if ($only) {
                array_unshift($combos, $combo);
            } else {
                $combos []= $combo;
            }
        }
        $proxyCombos = [];
        foreach ($settings->getProxies() as $proxy => $extras) {
            foreach ($extras as $extra) {
                if ($proxy === ObfuscatedStream::class && \in_array(\strlen($extra['secret']), [17, 34], true)) {
                    $combos[] = [[DefaultStream::class, []], [BufferedRawStream::class, []], [$proxy, $extra], [IntermediatePaddedStream::class, []]];
                }
                foreach ($combos as $orig) {
                    $combo = [];
                    if ($proxy === ObfuscatedStream::class) {
                        $combo = $orig;
                        if ($combo[\count($combo) - 2][0] === ObfuscatedStream::class) {
                            $combo[\count($combo) - 2][1] = $extra;
                        } else {
                            $mtproto = end($combo);
                            $combo[\count($combo) - 1] = [$proxy, $extra];
                            $combo[] = $mtproto;
                        }
                    } else {
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
                    }
                    $proxyCombos []= $combo;
                }
            }
        }
        if ($settings->getRetry()) {
            $combos = array_merge($proxyCombos, $combos);
            $combos[] = [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpsStream::class, []]];
        } elseif ($proxyCombos) {
            $combos = $proxyCombos;
        }
        $combos = array_unique($combos, SORT_REGULAR);

        $bind = self::normalizeBindToOption($settings->getBindTo());
        $onlyIPv6 = null;
        if ($bind !== null) {
            $onlyIPv6 = InternetAddress::fromString($bind)->getVersion() === InternetAddressVersion::IPv6
                ? 'ipv6'
                : 'ipv4';
        }
        $context = (new ConnectContext())->withConnectTimeout($settings->getTimeout())->withBindTo($bind);
        foreach ($combos as $combo) {
            foreach ([true, false] as $useDoH) {
                $ipv6Combos = [
                    $settings->getIpv6() ? 'ipv6' : 'ipv4',
                    $settings->getIpv6() ? 'ipv4' : 'ipv6',
                ];
                foreach ($ipv6Combos as $ipv6) {
                    if ($onlyIPv6 !== null && $onlyIPv6 !== $ipv6) {
                        continue;
                    }
                    if (!isset($this->API->dcList[$test][$ipv6][$dc_number]['ip_address'])) {
                        continue;
                    }
                    $address = $this->API->dcList[$test][$ipv6][$dc_number]['ip_address'];
                    if ($ipv6 === 'ipv6') {
                        $address = "[$address]";
                    }
                    $port = $this->API->dcList[$test][$ipv6][$dc_number]['port'];
                    foreach (array_unique([$port, 443, 80, 88, 5222]) as $port) {
                        $stream = end($combo)[0];
                        if ($stream === HttpsStream::class) {
                            $subdomain = $settings->getSslSubdomains()[abs($dc_number)] ?? null;
                            if (!$subdomain) {
                                continue;
                            }
                            if (DataCenter::isMedia($dc_number)) {
                                $subdomain .= '-1';
                            }
                            $path = $settings->getTestMode() ? 'apiw_test1' : 'apiw1';
                            $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                        } elseif ($stream === HttpStream::class) {
                            $uri = 'tcp://'.$address.':'.$port.'/api';
                        } else {
                            $uri = 'tcp://'.$address.':'.$port;
                        }
                        $ctx = (new ConnectionContext())
                            ->setDc($dc_number)
                            ->setCdn($this->API->isCdn($dc_number))
                            ->setSocketContext($context)
                            ->setUri($uri)
                            ->setIpv6($ipv6 === 'ipv6');
                        foreach ($combo as $stream) {
                            if ($stream[0] === DefaultStream::class && $stream[1] === []) {
                                $stream[1] = $useDoH ? new DoHConnector($this->dohWrapper, $ctx) : $this->dohWrapper->dnsConnector;
                            }
                            if (\in_array($stream[0], [WsStream::class, WssStream::class], true) && $stream[1] === []) {
                                $stream[1] = $this->dohWrapper->webSocketConnector;
                                if ($stream[0] === WssStream::class) {
                                    $subdomain = $settings->getSslSubdomains()[abs($dc_number)] ?? null;
                                    if (!$subdomain) {
                                        continue;
                                    }
                                    if (DataCenter::isMedia($dc_number)) {
                                        $subdomain .= '-1';
                                    }
                                    $path = $settings->getTestMode() ? 'apiws_test' : 'apiws';
                                    $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                                } else {
                                    $path = $settings->getTestMode() ? 'apiws_test' : 'apiws';
                                    $uri = 'tcp://'.$address.':'.$port.'/'.$path;
                                }
                                $ctx->setUri($uri);
                            }
                            /** @var array{0: class-string, 1: mixed} $stream */
                            /** @psalm-suppress TooFewArguments Psalm bug */
                            $ctx->addStream(...$stream);
                        }
                        $ctxs[] = $ctx;
                    }
                }
            }
        }
        if (empty($ctxs)) {
            throw new AssertionError("No info for DC $dc_number!");
        }
        return new ContextIterator($ctxs);
    }
    public function waitGetConnection(int $dc): Connection
    {
        return $this->getDataCenterConnection($dc)->waitGetConnection();
    }
    /**
     * Get DataCenterConnection instance.
     *
     * @param int $dc DC ID
     */
    public function getDataCenterConnection(int $dc): DataCenterConnection
    {
        if (!isset($this->sockets[$dc]) || !$this->sockets[$dc]->hasCtx()) {
            $this->API->logger("Acquiring connect lock for $dc!", Logger::VERBOSE);
            $lock = $this->connectMutex->acquire((string) $dc);
            try {
                if (isset($this->sockets[$dc]) && $this->sockets[$dc]->hasCtx()) {
                    return $this->sockets[$dc];
                }
                $ctxs = $this->generateContexts($dc);

                $this->API->logger("Connecting to DC {$dc}", Logger::WARNING);
                $this->sockets[$dc] ??= new DataCenterConnection();
                $this->sockets[$dc]->setExtra($this->API, $dc, $ctxs);
                $this->sockets[$dc]->connect();
            } finally {
                EventLoop::queue($lock->release(...));
            }
        }
        return $this->sockets[$dc];
    }
    public function has(int $dc): bool
    {
        if (isset($this->sockets[$dc])) {
            return true;
        }
        $test = $this->getSettings()->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->getSettings()->getIpv6() ? 'ipv6' : 'ipv4';
        return isset($this->API->dcList[$test][$ipv6][$dc]);
    }
    /**
     * Get all DataCenterConnection instances.
     *
     * @return array<int, DataCenterConnection>
     */
    public function getDataCenterConnections(): array
    {
        return $this->sockets;
    }
}
