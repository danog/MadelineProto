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

use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\Request;
use Amp\Socket\ConnectContext;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\Settings\Connection as ConnectionSettings;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\Serializable;
use Throwable;

/**
 * Manages datacenters.
 */
class DataCenter
{
    use Serializable;
    /**
     * All socket connections to DCs.
     *
     * @var array<string|int, DataCenterConnection>
     */
    public array $sockets = [];
    /**
     * Current DC ID.
     *
     */
    public string|int $curdc = 0;
    /**
     * Main instance.
     *
     */
    private MTProto $API;
    /**
     * DC list.
     *
     */
    private array $dclist = [];
    /**
     * Settings.
     *
     */
    private ConnectionSettings $settings;
    private DoHWrapper $dohWrapper;

    public function __sleep()
    {
        return ['sockets', 'curdc', 'dclist', 'settings'];
    }
    public function __wakeup(): void
    {
        if (\is_array($this->settings)) {
            $settings = new ConnectionSettings;
            $settings->mergeArray(['connection_settings' => $this->settings]);
            $this->settings = $settings;
        }
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
     * @param array       $dclist       DC IP list
     * @param ConnectionSettings $settings     Settings
     * @param boolean     $reconnectAll Whether to reconnect to all DCs or just to changed ones
     * @param CookieJar   $jar          Cookie jar
     */
    public function __magic_construct(MTProto $API, array $dclist, ConnectionSettings $settings, bool $reconnectAll = true, ?CookieJar $jar = null): void
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
     * @param string  $dc_number DC to connect to
     * @param integer $id        Connection ID to re-establish (optional)
     */
    public function dcConnect(string $dc_number, int $id = -1): bool
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
     * @return array<ConnectionContext>
     */
    public function generateContexts(int $dc_number = 0, string $uri = '', ?ConnectContext $context = null): array
    {
        $ctxs = $this->dohWrapper->generateContexts(
            $this->dclist,
            $dc_number,
            $uri,
            $context
        );
        if (empty($ctxs)) {
            unset($this->sockets[$dc_number]);
            $this->API->logger->logger("No info for DC {$dc_number}", Logger::ERROR);
        } elseif (\defined('MADELINEPROTO_TEST') && \constant('MADELINEPROTO_TEST') === 'pony') {
            return [$ctxs[0]];
        }
        return $ctxs;
    }
    /**
     * Get main API.
     */
    public function getAPI(): MTProto
    {
        return $this->API;
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
     * @param string $dc DC ID
     */
    public function getAuthConnection(string $dc): Connection
    {
        return $this->sockets[$dc]->getAuthConnection();
    }
    /**
     * Get Connection instance.
     *
     * @param string $dc DC ID
     */
    public function getConnection(string $dc): Connection
    {
        return $this->sockets[$dc]->getConnection();
    }
    /**
     * Get Connection instance asynchronously.
     *
     * @param string $dc DC ID
     */
    public function waitGetConnection(string $dc): Connection
    {
        return $this->sockets[$dc]->waitGetConnection();
    }
    /**
     * Get DataCenterConnection instance.
     *
     * @param string $dc DC ID
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
     */
    public function has(string $dc): bool
    {
        return isset($this->sockets[$dc]);
    }
    /**
     * Check if connected to datacenter using HTTP.
     *
     * @param string $datacenter DC ID
     */
    public function isHttp(string $datacenter): bool
    {
        return $this->sockets[$datacenter]->isHttp();
    }
    /**
     * Check if connected to datacenter directly using IP address.
     *
     * @param string $datacenter DC ID
     */
    public function byIPAddress(string $datacenter): bool
    {
        return $this->sockets[$datacenter]->byIPAddress();
    }
    /**
     * Get all DC IDs.
     *
     * @param boolean $all Whether to get all possible DC IDs, or only connected ones
     */
    public function getDcs(bool $all = true): array
    {
        $test = $this->settings->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->settings->getIpv6() ? 'ipv6' : 'ipv4';
        return $all ? \array_keys((array) $this->dclist[$test][$ipv6]) : \array_keys((array) $this->sockets);
    }
}
