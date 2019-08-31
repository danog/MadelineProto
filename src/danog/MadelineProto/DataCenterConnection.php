<?php
/**
 * Connection module handling all connections to a datacenter.
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

use danog\MadelineProto\Stream\ConnectionContext;

class DataCenterConnection
{
    /**
     * Temporary auth key.
     *
     * @var array
     */
    private $tempAuthKey;
    /**
     * Permanent auth key.
     *
     * @var array
     */
    private $authKey;

    /**
     * Whether this auth key is authorized (as in logged in).
     *
     * @var boolean
     */
    private $authorized = false;

    /**
     * Connections open to a certain DC
     *
     * @var array
     */
    private $connections = [];

    /**
     * Main API instance
     *
     * @var \danog\MadelineProto\MTProto
     */
    private $API;

    /**
     * Connection context
     *
     * @var ConnectionContext
     */
    private $ctx;

    /**
     * DC ID
     *
     * @var string
     */
    private $datacenter;
    
    /**
     * Get auth key.
     *
     * @param boolean $temp Whether to fetch the temporary auth key
     *
     * @return array
     */
    public function getAuthKey(bool $temp = true): array
    {
        return $this->{$temp ? 'tempAuthKey' : 'authKey'};
    }
    /**
     * Check if auth key is present.
     *
     * @param boolean $temp Whether to fetch the temporary auth key
     *
     * @return bool
     */
    public function hasAuthKey(bool $temp = true): bool
    {
        return $this->{$temp ? 'tempAuthKey' : 'authKey'} !== null;
    }
    /**
     * Set auth key.
     *
     * @param boolean $temp Whether to fetch the temporary auth key
     *
     * @return void
     */
    public function setAuthKey(array $key, bool $temp = true)
    {
        $this->{$temp ? 'tempAuthKey' : 'authKey'} = $key;
    }

    /**
     * Check if we are logged in.
     *
     * @return boolean
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * Set the authorized boolean.
     *
     * @param boolean $authorized Whether we are authorized
     *
     * @return void
     */
    public function authorized(bool $authorized)
    {
        $this->authorized = $authorized;
    }

    /**
     * Get connection context
     *
     * @return ConnectionContext
     */
    public function getCtx(): ConnectionContext
    {
        return $this->ctx;
    }

    /**
     * Connect function.
     *
     * @param ConnectionContext $ctx Connection context
     *
     * @return \Generator
     */
    public function connect(ConnectionContext $ctx): \Generator
    {
        $this->API->logger->logger("Trying connection via $ctx", \danog\MadelineProto\Logger::WARNING);

        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $media = $ctx->isMedia();

        $count = $media ? $this->API->settings['connection_settings']['media_socket_count'] : 1;

        $this->connections = [];
        for ($x = 0; $x < $count; $x++) {
            $this->connections[$x] = new Connection();
            yield $this->connections[$x]->connect(yield $ctx->getStream());
            $ctx = $this->ctx->getCtx();
        }
    }

    public function sendMessage($message, $flush = true)
    {
    }

    public function setExtra(API $API)
    {
        $this->API = $API;
    }

    public function disconnect()
    {
        $this->API->logger->logger("Disconnecting from DC {$this->datacenter}");
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }
        $this->connections = [];
    }

    public function reconnect(): \Generator
    {
        $this->API->logger->logger("Reconnecting DC {$this->datacenter}");
        foreach ($this->connections as $connection) {
            yield $connection->reconnect();
        }
        $this->disconnect();
        yield $this->API->datacenter->dcConnectAsync($this->ctx->getDc());
    }

    /**
     * Sleep function.
     *
     * @internal
     *
     * @return array
     */
    public function __sleep()
    {
        return ['authKey', 'tempAuthKey', 'authorized'];
    }
}
