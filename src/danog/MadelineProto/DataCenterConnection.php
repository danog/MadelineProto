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

use danog\MadelineProto\Loop\Generic\PeriodicLoop;
use danog\MadelineProto\MTProto\AuthKey;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use JsonSerializable;

class DataCenterConnection implements JsonSerializable
{
    /**
     * Temporary auth key.
     *
     * @var TempAuthKey|null
     */
    private $tempAuthKey;
    /**
     * Permanent auth key.
     *
     * @var PermAuthKey|null
     */
    private $authKey;

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
     * Linked DC ID.
     *
     * @var string
     */
    private $linked;

    /**
     * Loop to keep weights at sane value.
     *
     * @var \danog\MadelineProto\Loop\Generic\PeriodicLoop
     */
    private $robinLoop;

    /**
     * Decrement roundrobin weight by this value if busy reading.
     *
     * @var integer
     */
    private $decRead = 1;
    /**
     * Decrement roundrobin weight by this value if busy writing.
     *
     * @var integer
     */
    private $decWrite = 10;

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
     * @param boolean|null $temp Whether to fetch the temporary auth key
     *
     * @return bool
     */
    public function hasAuthKey(bool $temp = true): bool
    {
        return $this->{$temp ? 'tempAuthKey' : 'authKey'} !== null && $this->{$temp ? 'tempAuthKey' : 'authKey'}->hasAuthKey();
    }
    /**
     * Set auth key.
     *
     * @param AuthKey|null $key  The auth key
     * @param boolean|null $temp Whether to set the temporary auth key
     *
     * @return void
     */
    public function setAuthKey(?AuthKey $key, bool $temp = true)
    {
        $this->{$temp ? 'tempAuthKey' : 'authKey'} = $key;
    }

    /**
     * Get temporary authorization key.
     *
     * @return AuthKey
     */
    public function getTempAuthKey(): TempAuthKey
    {
        return $this->getAuthKey(true);
    }
    /**
     * Get permanent authorization key.
     *
     * @return AuthKey
     */
    public function getPermAuthKey(): PermAuthKey
    {
        return $this->getAuthKey(false);
    }

    /**
     * Check if has temporary authorization key.
     *
     * @return boolean
     */
    public function hasTempAuthKey(): bool
    {
        return $this->hasAuthKey(true);
    }

    /**
     * Check if has permanent authorization key.
     *
     * @return boolean
     */
    public function hasPermAuthKey(): bool
    {
        return $this->hasAuthKey(false);
    }

    /**
     * Set temporary authorization key.
     *
     * @param TempAuthKey|null $key Auth key
     *
     * @return void
     */
    public function setTempAuthKey(?TempAuthKey $key)
    {
        return $this->setAuthKey($key, true);
    }
    /**
     * Set permanent authorization key.
     *
     * @param PermAuthKey|null $key Auth key
     *
     * @return void
     */
    public function setPermAuthKey(?PermAuthKey $key)
    {
        return $this->setAuthKey($key, false);
    }

    /**
     * Bind temporary and permanent auth keys.
     *
     * @param bool $pfs Whether to bind using PFS
     *
     * @return void
     */
    public function bind(bool $pfs = true)
    {
        $this->tempAuthKey->bind($this->authKey, $pfs);
    }
    /**
     * Check if we are logged in.
     *
     * @return boolean
     */
    public function isAuthorized(): bool
    {
        return $this->hasTempAuthKey() ? $this->getTempAuthKey()->isAuthorized() : false;
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
        if ($authorized) {
            $this->getTempAuthKey()->authorized($authorized);
        } else if ($this->hasTempAuthKey()) {
            $this->getTempAuthKey()->authorized($authorized);
        }
    }

    /**
     * Link permanent authorization info of main DC to media DC.
     *
     * @param string $dc Main DC ID
     *
     * @return void
     */
    public function link(string $dc)
    {
        $this->linked = $dc;
        $this->authKey = &$this->API->datacenter->getDataCenterConnection($dc)->authKey;
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
                $this->robinLoop = new PeriodicLoop($this->API, [$this, 'even'], "Robin loop DC {$this->datacenter}", 10);
            }
            $this->robinLoop->start();
        }

        $incRead = $media ? 5 : 1;

        $this->connections = [];
        $this->availableConnections = [];
        for ($x = 0; $x < $count; $x++) {
            $this->availableConnections[$x] = 0;
            $this->connections[$x] = new Connection();
            $this->connections[$x]->setExtra($this, $x);
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
     * Get connection for authorization.
     *
     * @return Connection
     */
    public function getAuthConnection(): Connection
    {
        return $this->connections[0];
    }

    /**
     * Get best socket in round robin.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        if (\count($this->availableConnections) <= 1) {
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
     * Indicate that one of the sockets is busy reading.
     *
     * @param boolean $reading Whether we're busy reading
     * @param int     $x       Connection ID
     *
     * @return void
     */
    public function reading(bool $reading, int $x)
    {
        $this->availableConnections[$x] += $reading ? -$this->decRead : $this->decRead;
    }
    /**
     * Indicate that one of the sockets is busy writing.
     *
     * @param boolean $writing Whether we're busy writing
     * @param int     $x       Connection ID
     *
     * @return void
     */
    public function writing(bool $writing, int $x)
    {
        $this->availableConnections[$x] += $writing ? -$this->decWrite : $this->decWrite;
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
     * Check if is an HTTP connection.
     *
     * @return boolean
     */
    public function isHttp(): bool
    {
        return \in_array($this->ctx->getStreamName(), [HttpStream::getName(), HttpsStream::getName()]);
    }

    /**
     * Check if is a media connection
     *
     * @return boolean
     */
    public function isMedia(): bool
    {
        return $this->ctx->isMedia();
    }

    /**
     * Check if is a CDN connection
     *
     * @return boolean
     */
    public function isCDN(): bool
    {
        return $this->ctx->isCDN();
    }

    /**
     * Get DC-specific settings
     *
     * @return array
     */
    public function getSettings(): array
    {
        $dc_config_number = isset($this->API->settings['connection_settings'][$this->datacenter]) ? $this->datacenter : 'all';
        return $this->API->settings['connection_settings'][$dc_config_number];
    }

    /**
     * JSON serialize function.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->linked ?
        [
            'linked' => $this->linked,
            'tempAuthKey' => $this->tempAuthKey
        ] :
        [
            'authKey' => $this->authKey,
            'tempAuthKey' => $this->tempAuthKey
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
        return $this->linked ? ['linked', 'tempAuthKey'] : ['linked', 'authKey', 'tempAuthKey'];
    }
}
