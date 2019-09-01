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

use danog\MadelineProto\AuthKey\AuthKey;
use danog\MadelineProto\Stream\ConnectionContext;
use JsonSerializable;

class DataCenterConnection implements JsonSerializable
{
    /**
     * Temporary auth key.
     *
     * @var AuthKey
     */
    private $tempAuthKey;
    /**
     * Permanent auth key.
     *
     * @var AuthKey
     */
    private $authKey;

    /**
     * Whether this auth key is authorized (as in logged in).
     *
     * @var boolean
     */
    private $authorized = false;

    /**
     * Connections open to a certain DC.
     *
     * @var array<string, Connection>
     */
    private $connections = [];
    /**
     * Connection weights.
     *
     * @var array<string, int>
     */
    private $availableConnections = [];

    /**
     * Main API instance.
     *
     * @var \danog\MadelineProto\MTProto
     */
    private $API;

    /**
     * Connection context.
     *
     * @var ConnectionContext
     */
    private $ctx;

    /**
     * DC ID.
     *
     * @var string
     */
    private $datacenter;

    /**
     * Index for round robin.
     *
     * @var integer
     */
    private $index = 0;

    /**
     * Loop to keep weights at sane value.
     *
     * @var \danog\MadelineProto\Loop\Generic\PeriodicLoop
     */
    private $robinLoop;

    /**
     * Get auth key.
     *
     * @param boolean $temp Whether to fetch the temporary auth key
     *
     * @return AuthKey
     */
    public function getAuthKey(bool $temp = true): AuthKey
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
     * @param AuthKey|null $key  The auth key
     * @param boolean      $temp Whether to set the temporary auth key
     *
     * @return void
     */
    public function setAuthKey(?AuthKey $key, bool $temp = true)
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
     * Reset MTProto sessions.
     *
     * @return void
     */
    public function resetSession()
    {
        foreach ($this->connections as $socket) {
            $socket->resetSession();
        }
    }
    /**
     * Flush all pending packets.
     *
     * @return void
     */
    public function flush()
    {
        foreach ($this->connections as $socket) {
            $socket->flush();
        }
    }
    /**
     * Get connection context.
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
        $this->API->logger->logger("Trying shared connection via $ctx", \danog\MadelineProto\Logger::WARNING);

        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $media = $ctx->isMedia();


        $count = $media ? $this->API->settings['connection_settings']['media_socket_count'] : 1;

        if ($count > 1) {
            if (!$this->robinLoop) {
                $this->robinLoop = new PeriodicLoop($this, [$this, 'even'], "Robin loop DC {$this->datacenter}", 10);
            }
            $this->robinLoop->start();
        }

        $incRead = $media ? 5 : 1;

        $this->connections = [];
        $this->availableConnections = [];
        for ($x = 0; $x < $count; $x++) {
            $this->availableConnections[$x] = 0;
            $this->connections[$x] = new Connection();
            $this->connections[$x]->setExtra(
                $this,
                function (bool $reading) use ($x, $incRead) {
                    $this->availableConnections[$x] += $reading ? -$incRead : $incRead;
                },
                function (bool $writing) use ($x) {
                    $this->availableConnections[$x] += $writing ? -10 : 10;
                }
            );
            yield $this->connections[$x]->connect($ctx);
            $ctx = $this->ctx->getCtx();
        }
    }

    /**
     * Close all connections to DC.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->API->logger->logger("Disconnecting from shared DC {$this->datacenter}");
        if ($this->robinLoop) {
            $this->robinLoop->signal(true);
            $this->robinLoop = null;
        }
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }
        $this->connections = [];
        $this->availableConnections = [];
    }

    /**
     * Reconnect to DC.
     *
     * @return \Generator
     */
    public function reconnect(): \Generator
    {
        $this->API->logger->logger("Reconnecting shared DC {$this->datacenter}");
        $this->disconnect();
        yield $this->connect($this->ctx);
    }

    /**
     * Get best socket in round robin.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        if (\count($this->availableConnections) === 1) {
            return $this->connections[0];
        }
        \max($this->availableConnections);
        $key = \key($this->availableConnections);
        // Decrease to implement round robin
        $this->availableConnections[$key]--;
        return $this->connections[$key];
    }

    /**
     * Even out round robin values.
     *
     * @return void
     */
    public function even()
    {
        if (\min($this->availableConnections) < 1000) {
            foreach ($this->availableConnections as &$value) {
                $value += 1000;
            }
        }
    }

    /**
     * Set main instance.
     *
     * @param MTProto $API Main instance
     *
     * @return void
     */
    public function setExtra(MTProto $API)
    {
        $this->API = $API;
    }

    /**
     * Get main instance.
     *
     * @return MTProto
     */
    public function getExtra(): MTProto
    {
        return $this->API;
    }
    /**
     * JSON serialize function.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'authKey' => $this->authKey,
            'tempAuthKey' => $this->tempAuthKey,
            'authorized' => $this->authorized,
        ];
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
