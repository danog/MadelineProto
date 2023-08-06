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
    /**
     * DC list.
     *
     * @param TDcList
     */
    private array $dclist = [];
    /**
     * Settings.
     */
    private ConnectionSettings $settings;
    private DoHWrapper $dohWrapper;

    public function __sleep()
    {
        return ['sockets', 'currentDatacenter', 'dclist', 'settings'];
    }
    public static function isTest(int $dc): bool
    {
        return \abs($dc) > 10000;
    }
    public static function isMedia(int $dc): bool
    {
        return $dc < 0;
    }
    public function isCdn(int $dc): bool
    {
        $test = $this->settings->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->settings->getIpv6() ? 'ipv6' : 'ipv4';
        return $this->dclist[$test][$ipv6][$dc]['cdn'] ?? false;
    }
    public function __wakeup(): void
    {
        $array = [];
        foreach ($this->sockets as $id => $socket) {
            if ($socket instanceof Connection) {
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
            if (!\is_int($id)) {
                unset($this->sockets[$id]);
            }
        }
        $this->setDataCenterConnections($array);
    }
    /**
     * Set auth key information from saved auth array.
     *
     * @param array $saved Saved auth array
     */
    public function setDataCenterConnections(array $saved): void
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
     * @param ConnectionSettings $settings     Settings
     * @param boolean     $reconnectAll Whether to reconnect to all DCs or just to changed ones
     * @param CookieJar   $jar          Cookie jar
     */
    public function __construct(MTProto $API, array $dclist, ConnectionSettings $settings, bool $reconnectAll = true, ?CookieJar $jar = null)
    {
        $this->API = $API;
        $changed = [];
        $changedSettings = $settings->hasChanged();
        if (!$reconnectAll) {
            $changed = [];
            $test = $API->getCachedConfig()['test_mode'] ?? false ? 'test' : 'main';
            foreach ($this->dclist[$test] as $ipv6 => $dcs) {
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
            if ($socket instanceof DataCenterConnection && \is_int($key)) {
                if ($reconnectAll || isset($changed[$id])) {
                    $this->API->logger->logger('Disconnecting all before reconnect!');
                    $socket->needReconnect(true);
                    $socket->setExtra($this->API);
                    $socket->disconnect();
                }
            } else {
                unset($this->sockets[$key]);
            }
        }
        if ($reconnectAll || $changedSettings || !isset($this->dohWrapper)) {
            $this->dohWrapper = new DoHWrapper(
                $settings,
                $API,
                $jar
            );
        }
        $this->settings->applyChanges();
    }
    /**
     * Connect to specified DC.
     *
     * @param int     $dc_number DC to connect to
     * @param integer $id        Connection ID to re-establish (optional)
     */
    public function dcConnect(int $dc_number, int $id = -1): bool
    {
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
                $this->API->logger->logger('OK!', Logger::WARNING);
                return true;
            } catch (Throwable $e) {
                if (\defined('MADELINEPROTO_TEST') && \constant('MADELINEPROTO_TEST') === 'pony') {
                    throw $e;
                }
                $this->API->logger->logger("Connection failed ({$dc_number}): $e", Logger::ERROR);
            }
        }
        throw new Exception("Could not connect to DC {$dc_number}");
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
        $test = $this->settings->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->settings->getIpv6() ? 'ipv6' : 'ipv4';
        $default = match ($this->settings->getProtocol()) {
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
        if ($this->settings->getObfuscated() && !\in_array($default[2][0], [HttpsStream::class, HttpStream::class], true)) {
            $default = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, []], \end($default)];
        }
        if ($this->settings->getTransport() && !\in_array($default[2][0], [HttpsStream::class, HttpStream::class], true)) {
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
        $combos[] = $default;
        if ($this->settings->getRetry()) {
            $only = $this->dclist[$test][$ipv6][$dc_number]['tcpo_only'];
            if ($only || isset($this->dclist[$test][$ipv6][$dc_number]['secret'])) {
                $extra = isset($this->dclist[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $this->dclist[$test][$ipv6][$dc_number]['secret']] : [];
                $combo = [[DefaultStream::class, []], [BufferedRawStream::class, []], [ObfuscatedStream::class, $extra], [IntermediatePaddedStream::class, []]];
                if ($only) {
                    \array_unshift($combos, $combo);
                } else {
                    $combos []= $combo;
                }
            }
            $proxyCombos = [];
            foreach ($this->settings->getProxies() as $proxy => $extras) {
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
            $combos = \array_merge($proxyCombos, $combos);
            $combos[] = [[DefaultStream::class, []], [BufferedRawStream::class, []], [HttpsStream::class, []]];
            $combos = \array_unique($combos, SORT_REGULAR);
        }
        $context ??= (new ConnectContext())->withConnectTimeout($this->settings->getTimeout())->withBindTo($this->settings->getBindTo());
        foreach ($combos as $combo) {
            foreach ([true, false] as $useDoH) {
                $ipv6Combos = [
                    $this->settings->getIpv6() ? 'ipv6' : 'ipv4',
                    $this->settings->getIpv6() ? 'ipv4' : 'ipv6'
                ];
                foreach ($ipv6Combos as $ipv6) {
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
                            $subdomain = $this->settings->getSslSubdomains()[\abs($dc_number)] ?? null;
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
                            $subdomain = $this->settings->getSslSubdomains()[\abs($dc_number)] ?? null;
                            if (!$subdomain) {
                                continue;
                            }
                            if (DataCenter::isMedia($dc_number)) {
                                $subdomain .= '-1';
                            }
                            $path = $this->settings->getTestMode() ? 'apiws_test' : 'apiws';
                            $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                        } elseif ($combo[1][0] === WsStream::class) {
                            $path = $this->settings->getTestMode() ? 'apiws_test' : 'apiws';
                            $uri = 'tcp://'.$address.':'.$port.'/'.$path;
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
    /**
     * Get Connection instance for authorization.
     *
     * @param int $dc DC ID
     */
    public function getAuthConnection(int $dc): Connection
    {
        return $this->sockets[$dc]->getAuthConnection();
    }
    /**
     * Get Connection instance.
     *
     * @param int $dc DC ID
     */
    public function getConnection(int $dc): Connection
    {
        return $this->sockets[$dc]->getConnection();
    }
    /**
     * Get Connection instance asynchronously.
     *
     * @param int $dc DC ID
     */
    public function waitGetConnection(int $dc): Connection
    {
        return $this->sockets[$dc]->waitGetConnection();
    }
    /**
     * Get DataCenterConnection instance.
     *
     * @param int $dc DC ID
     */
    public function getDataCenterConnection(int $dc): DataCenterConnection
    {
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
    /**
     * Check if a DC is present.
     *
     * @param int $dc DC ID
     */
    public function has(int $dc): bool
    {
        return isset($this->sockets[$dc]);
    }
    /**
     * Check if connected to datacenter directly using IP address.
     *
     * @param int $datacenter DC ID
     */
    public function byIPAddress(int $datacenter): bool
    {
        return $this->sockets[$datacenter]->byIPAddress();
    }
    /**
     * Get all DC IDs.
     *
     * @param boolean $all Whether to get all possible DC IDs, or only connected ones
     */
    public function getDcs(bool $all): array
    {
        $test = $this->settings->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->settings->getIpv6() ? 'ipv6' : 'ipv4';
        return $all ? \array_keys($this->dclist[$test][$ipv6]) : \array_keys($this->sockets);
    }
}
