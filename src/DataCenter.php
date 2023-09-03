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
use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Amp\Socket\ConnectContext;
use Amp\Sync\LocalKeyedMutex;
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
use Throwable;

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
    }

    public function __sleep()
    {
        return ['sockets', 'currentDatacenter', 'API'];
    }
    public static function isTest(int $dc): bool
    {
        return \abs($dc) > 10000;
    }
    public static function isMedia(int $dc): bool
    {
        return $dc < 0;
    }
    private function getSettings(): \danog\MadelineProto\Settings\Connection
    {
        return $this->API->getSettings()->getConnection();
    }
    public function isCdn(int $dc): bool
    {
        $test = $this->getSettings()->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->getSettings()->getIpv6() ? 'ipv6' : 'ipv4';
        return $this->API->dcList[$test][$ipv6][$dc]['cdn'] ?? false;
    }
    /**
     * Connect to specified DC.
     *
     * @param int     $dc_number DC to connect to
     * @param integer $id        Connection ID to re-establish (optional)
     */
    public function dcConnect(int $dc_number, int $id = -1): bool
    {
        $lock = $this->connectMutex->acquire("$dc_number $id");
        try {
            $old = isset($this->sockets[$dc_number]) && (
                $this->sockets[$dc_number]->shouldReconnect()
                    || (
                        $id !== -1
                        && $this->sockets[$dc_number]->hasConnection($id)
                        && $this->sockets[$dc_number]->getConnection($id)->shouldReconnect()
                    )
            );
            if (isset($this->sockets[$dc_number]) && !$old) {
                $this->API->logger->logger("Not reconnecting to DC {$dc_number} ({$id})");
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
                        $this->sockets[$dc_number]->connect($ctx, $id);
                    } else {
                        $this->API->logger->logger("Connecting to DC {$dc_number} from scratch", Logger::WARNING);
                        $this->sockets[$dc_number] = new DataCenterConnection();
                        $this->sockets[$dc_number]->setExtra($this->API);
                        $this->sockets[$dc_number]->connect($ctx);
                    }
                    if ($ctx->getIpv6()) {
                        Magic::setIpv6(true);
                    }
                    $this->API->logger->logger("OK, connected to DC $dc_number!", Logger::WARNING);
                    return true;
                } catch (Throwable $e) {
                    if (\defined('MADELINEPROTO_TEST') && \constant('MADELINEPROTO_TEST') === 'pony') {
                        throw $e;
                    }
                    $this->API->logger->logger("Connection failed ({$dc_number}): $e", Logger::ERROR);
                }
            }
            throw new Exception("Could not connect to DC {$dc_number}");
        } finally {
            $lock->release();
        }
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
     * Generate contexts.
     *
     * @param integer        $dc_number DC ID to generate contexts for
     * @param ConnectContext $context   Connection context
     * @return array<ConnectionContext>
     */
    public function generateContexts(int $dc_number, ?ConnectContext $context = null): array
    {
        $ctxs = [];
        $combos = [];
        $test = $this->getSettings()->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->getSettings()->getIpv6() ? 'ipv6' : 'ipv4';
        $default = match ($this->getSettings()->getProtocol()) {
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
        if ($this->getSettings()->getObfuscated() && !\in_array($default[2][0], [HttpsStream::class, HttpStream::class], true)) {
            $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], \end($default)];
        }
        if ($this->getSettings()->getTransport() && !\in_array($default[2][0], [HttpsStream::class, HttpStream::class], true)) {
            switch ($this->getSettings()->getTransport()) {
                case DefaultStream::class:
                    if ($this->getSettings()->getObfuscated()) {
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
        $combos[] = $default;

        if (!isset($this->API->dcList[$test][$ipv6][$dc_number])) {
            return [];
        }

        $only = $this->API->dcList[$test][$ipv6][$dc_number]['tcpo_only'];
        if ($only || isset($this->API->dcList[$test][$ipv6][$dc_number]['secret'])) {
            $extra = isset($this->API->dcList[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $this->API->dcList[$test][$ipv6][$dc_number]['secret']] : [];
            $combo = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, $extra], [IntermediatePaddedStream::class, []]];
            if ($only) {
                \array_unshift($combos, $combo);
            } else {
                $combos []= $combo;
            }
        }
        $proxyCombos = [];
        foreach ($this->getSettings()->getProxies() as $proxy => $extras) {
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
                            $mtproto = \end($combo);
                            $combo[\count($combo) - 1] = [$proxy, $extra];
                            $combo[] = $mtproto;
                        }
                    } else {
                        if ($orig[1][0] === BufferedRawStream::class) {
                            [$first, $second] = [\array_slice($orig, 0, 2), \array_slice($orig, 2)];
                            $first[] = [$proxy, $extra];
                            $combo = \array_merge($first, $second);
                        } elseif (\in_array($orig[1][0], [WsStream::class, WssStream::class], true)) {
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
        if ($this->getSettings()->getRetry()) {
            $combos = \array_merge($proxyCombos, $combos);
            $combos[] = [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpsStream::class, []]];
        } elseif ($proxyCombos) {
            $combos = $proxyCombos;
        }
        $combos = \array_unique($combos, SORT_REGULAR);

        $context ??= (new ConnectContext())->withConnectTimeout($this->getSettings()->getTimeout())->withBindTo($this->getSettings()->getBindTo());
        foreach ($combos as $combo) {
            foreach ([true, false] as $useDoH) {
                $ipv6Combos = [
                    $this->getSettings()->getIpv6() ? 'ipv6' : 'ipv4',
                    $this->getSettings()->getIpv6() ? 'ipv4' : 'ipv6'
                ];
                foreach ($ipv6Combos as $ipv6) {
                    if (!isset($this->API->dcList[$test][$ipv6][$dc_number]['ip_address'])) {
                        continue;
                    }
                    $address = $this->API->dcList[$test][$ipv6][$dc_number]['ip_address'];
                    if ($ipv6 === 'ipv6') {
                        $address = "[$address]";
                    }
                    $port = $this->API->dcList[$test][$ipv6][$dc_number]['port'];
                    foreach (\array_unique([$port, 443, 80, 88, 5222]) as $port) {
                        $stream = \end($combo)[0];
                        if ($stream === HttpsStream::class) {
                            $subdomain = $this->getSettings()->getSslSubdomains()[\abs($dc_number)] ?? null;
                            if (!$subdomain) {
                                continue;
                            }
                            if (DataCenter::isMedia($dc_number)) {
                                $subdomain .= '-1';
                            }
                            $path = $this->getSettings()->getTestMode() ? 'apiw_test1' : 'apiw1';
                            $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                        } elseif ($stream === HttpStream::class) {
                            $uri = 'tcp://'.$address.':'.$port.'/api';
                        } else {
                            $uri = 'tcp://'.$address.':'.$port;
                        }
                        $ctx = (new ConnectionContext())
                            ->setDc($dc_number)
                            ->setCdn($this->isCdn($dc_number))
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
                                    $subdomain = $this->getSettings()->getSslSubdomains()[\abs($dc_number)] ?? null;
                                    if (!$subdomain) {
                                        continue;
                                    }
                                    if (DataCenter::isMedia($dc_number)) {
                                        $subdomain .= '-1';
                                    }
                                    $path = $this->getSettings()->getTestMode() ? 'apiws_test' : 'apiws';
                                    $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                                } else {
                                    $path = $this->getSettings()->getTestMode() ? 'apiws_test' : 'apiws';
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
            unset($this->sockets[$dc_number]);
            $this->API->logger->logger("No info for DC {$dc_number}", Logger::ERROR);
        } elseif (\defined('MADELINEPROTO_TEST') && \constant('MADELINEPROTO_TEST') === 'pony') {
            return [$ctxs[0]];
        }
        return $ctxs;
    }
    /**
     * Get contents of file.
     *
     * @param string $url URL to fetch
     */
    public function fileGetContents(string $url): string
    {
        return ($this->dohWrapper->HTTPClient->request(new Request($url)))->getBody()->buffer();
    }
    public function waitGetConnection(int $dc): Connection {
        return $this->getDataCenterConnection($dc)->waitGetConnection();
    }
    /**
     * Get DataCenterConnection instance.
     *
     * @param int $dc DC ID
     */
    public function getDataCenterConnection(int $dc): DataCenterConnection
    {
        if (!isset($this->sockets[$dc])) {
            $this->dcConnect($dc);
        }
        return $this->sockets[$dc];
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
