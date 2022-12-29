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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Promise;
use Amp\Success;
use Amp\Sync\LocalMutex;
use danog\MadelineProto\Loop\Generic\PeriodicLoopInternal;
use danog\MadelineProto\MTProto\AuthKey;
use danog\MadelineProto\MTProto\OutgoingMessage;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Settings\Connection as ConnectionSettings;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use JsonSerializable;

/**
 * Datacenter connection.
 */
class DataCenterConnection implements JsonSerializable
{
    const READ_WEIGHT = 1;
    const READ_WEIGHT_MEDIA = 5;
    const WRITE_WEIGHT = 10;
    /**
     * Promise for connection.
     *
     * @var Promise
     */
    private $connectionsPromise;
    /**
     * Deferred for connection.
     *
     * @var Deferred
     */
    private $connectionsDeferred;
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
    private $permAuthKey;
    /**
     * Connections open to a certain DC.
     *
     * @var array<int, Connection>
     */
    private $connections = [];
    /**
     * Connection weights.
     *
     * @var array<int, int>
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
     */
    private ?PeriodicLoopInternal $robinLoop = null;
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
     * Backed up messages.
     *
     * @var array
     */
    private $backup = [];
    /**
     * Whether this socket has to be reconnected.
     *
     * @var boolean
     */
    private $needsReconnect = false;
    /**
     * Indicate if this socket needs to be reconnected.
     *
     * @param boolean $needsReconnect Whether the socket has to be reconnected
     *
     */
    public function needReconnect(bool $needsReconnect): void
    {
        $this->needsReconnect = $needsReconnect;
    }
    /**
     * Whether this sockets needs to be reconnected.
     *
     * @return boolean
     */
    public function shouldReconnect(): bool
    {
        return $this->needsReconnect;
    }
    private ?LocalMutex $initingAuth = null;
    /**
     * Init auth keys for single DC.
     *
     * @internal
     *
     */
    public function initAuthorization(): \Generator
    {
        $logger = $this->API->logger;
        $this->initingAuth ??= new LocalMutex;
        $lock = yield $this->initingAuth->acquire();
        try {
            $logger->logger("Initing auth for DC {$this->datacenter}", Logger::NOTICE);
            yield from $this->waitGetConnection();
            $connection = $this->getAuthConnection();
            $this->createSession();
            $cdn = $this->isCDN();
            $media = $this->isMedia();
            $pfs = $this->API->settings->getAuth()->getPfs();
            if (!$this->hasTempAuthKey() || !$this->hasPermAuthKey() || !$this->isBound()) {
                if (!$this->hasPermAuthKey() && !$cdn && !$media) {
                    $logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['gen_perm_auth_key'], $this->datacenter), \danog\MadelineProto\Logger::NOTICE);
                    $this->setPermAuthKey(yield from $connection->createAuthKey(false));
                }
                if ($media) {
                    $this->link(\intval($this->datacenter));
                    if ($this->hasTempAuthKey()) {
                        return;
                    }
                }
                if ($pfs) {
                    if (!$cdn) {
                        $logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['gen_temp_auth_key'], $this->datacenter), \danog\MadelineProto\Logger::NOTICE);
                        $this->setTempAuthKey(null);
                        $this->setTempAuthKey(yield from $connection->createAuthKey(true));
                        yield from $this->bindTempAuthKey();
                        yield from $connection->methodCallAsyncRead('help.getConfig', []);
                        yield from $this->syncAuthorization();
                    } elseif (!$this->hasTempAuthKey()) {
                        $logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['gen_temp_auth_key'], $this->datacenter), \danog\MadelineProto\Logger::NOTICE);
                        $this->setTempAuthKey(yield from $connection->createAuthKey(true));
                    }
                } else {
                    if (!$cdn) {
                        $this->bind(false);
                        yield from $connection->methodCallAsyncRead('help.getConfig', []);
                        yield from $this->syncAuthorization();
                    } elseif (!$this->hasTempAuthKey()) {
                        $logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['gen_temp_auth_key'], $this->datacenter), \danog\MadelineProto\Logger::NOTICE);
                        $this->setTempAuthKey(yield from $connection->createAuthKey(true));
                    }
                }
                $this->flush();
            } elseif (!$cdn) {
                yield from $this->syncAuthorization();
            }
        } finally {
            $lock->release();
        }
        if ($this->hasTempAuthKey()) {
            $connection->pingHttpWaiter();
        }
    }
    /**
     * Bind temporary and permanent auth keys.
     *
     *
     * @internal
     *
     *
     * @psalm-return \Generator<int|mixed, array|mixed, mixed, true>
     */
    public function bindTempAuthKey(): \Generator
    {
        $connection = $this->getAuthConnection();
        $logger = $this->API->logger;
        $expires_in = $this->API->settings->getAuth()->getDefaultTempAuthKeyExpiresIn();
        for ($retry_id_total = 1; $retry_id_total <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id_total++) {
            try {
                $logger->logger('Binding authorization keys...', \danog\MadelineProto\Logger::VERBOSE);
                $nonce = \danog\MadelineProto\Tools::random(8);
                $expires_at = \time() + $expires_in;
                $temp_auth_key_id = $this->getTempAuthKey()->getID();
                $perm_auth_key_id = $this->getPermAuthKey()->getID();
                $temp_session_id = $connection->session_id;
                $message_data = (yield from $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'bind_auth_key_inner', 'nonce' => $nonce, 'temp_auth_key_id' => $temp_auth_key_id, 'perm_auth_key_id' => $perm_auth_key_id, 'temp_session_id' => $temp_session_id, 'expires_at' => $expires_at], 'bindTempAuthKey_inner'));
                $message_id = $connection->msgIdHandler->generateMessageId();
                $seq_no = 0;
                $encrypted_data = \danog\MadelineProto\Tools::random(16).$message_id.\pack('VV', $seq_no, \strlen($message_data)).$message_data;
                $message_key = \substr(\sha1($encrypted_data, true), -16);
                $padding = \danog\MadelineProto\Tools::random(\danog\MadelineProto\Tools::posmod(-\strlen($encrypted_data), 16));
                list($aes_key, $aes_iv) = Crypt::oldAesCalculate($message_key, $this->getPermAuthKey()->getAuthKey());
                $encrypted_message = $this->getPermAuthKey()->getID().$message_key.Crypt::igeEncrypt($encrypted_data.$padding, $aes_key, $aes_iv);
                $res = yield from $connection->methodCallAsyncRead('auth.bindTempAuthKey', ['perm_auth_key_id' => $perm_auth_key_id, 'nonce' => $nonce, 'expires_at' => $expires_at, 'encrypted_message' => $encrypted_message], ['msg_id' => $message_id]);
                if ($res === true) {
                    $logger->logger("Bound temporary and permanent authorization keys, DC {$this->datacenter}", \danog\MadelineProto\Logger::NOTICE);
                    $this->bind();
                    return true;
                }
            } catch (\danog\MadelineProto\SecurityException $e) {
                $logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\Exception $e) {
                $logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $logger->logger('An RPCErrorException occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
            }
        }
        throw new \danog\MadelineProto\SecurityException('An error occurred while binding temporary and permanent authorization keys.');
    }
    /**
     * Sync authorization data between DCs.
     *
     */
    private function syncAuthorization(): \Generator
    {
        $socket = $this->getAuthConnection();
        $logger = $this->API->logger;
        if ($this->API->authorized === MTProto::LOGGED_IN && !$this->isAuthorized()) {
            foreach ($this->API->datacenter->getDataCenterConnections() as $authorized_dc_id => $authorized_socket) {
                if ($this->API->authorized_dc !== -1 && $authorized_dc_id !== $this->API->authorized_dc) {
                    continue;
                }
                if ($authorized_socket->hasTempAuthKey()
                    && $authorized_socket->hasPermAuthKey()
                    && $authorized_socket->isAuthorized()
                    && $this->API->authorized === MTProto::LOGGED_IN
                    && !$this->isAuthorized()
                    && !$authorized_socket->isCDN()
                ) {
                    try {
                        $logger->logger('Trying to copy authorization from DC '.$authorized_dc_id.' to DC '.$this->datacenter);
                        $exported_authorization = yield from $this->API->methodCallAsyncRead('auth.exportAuthorization', ['dc_id' => \preg_replace('|_.*|', '', $this->datacenter)], ['datacenter' => $authorized_dc_id]);
                        $authorization = yield from $socket->methodCallAsyncRead('auth.importAuthorization', $exported_authorization);
                        $this->authorized(true);
                        break;
                    } catch (\danog\MadelineProto\Exception $e) {
                        $logger->logger('Failure while syncing authorization from DC '.$authorized_dc_id.' to DC '.$this->datacenter.': '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        $logger->logger('Failure while syncing authorization from DC '.$authorized_dc_id.' to DC '.$this->datacenter.': '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
                        if ($e->rpc === 'DC_ID_INVALID') {
                            break;
                        }
                    }
                    // Turns out this DC isn't authorized after all
                }
            }
        }
    }
    /**
     * Get auth key.
     *
     * @param boolean $temp Whether to fetch the temporary auth key
     *
     */
    public function getAuthKey(bool $temp = true): AuthKey
    {
        if ($this->{$temp ? 'tempAuthKey' : 'permAuthKey'} === null) {
            throw new NothingInTheSocketException();
        }
        return $this->{$temp ? 'tempAuthKey' : 'permAuthKey'};
    }
    /**
     * Check if auth key is present.
     *
     *
     */
    public function hasAuthKey(bool $temp = true): bool
    {
        return $this->{$temp ? 'tempAuthKey' : 'permAuthKey'} !== null && $this->{$temp ? 'tempAuthKey' : 'permAuthKey'}->hasAuthKey();
    }
    /**
     * Set auth key.
     *
     * @param AuthKey|null $key  The auth key
     *
     */
    public function setAuthKey(?AuthKey $key, bool $temp = true): void
    {
        $this->{$temp ? 'tempAuthKey' : 'permAuthKey'} = $key;
    }
    /**
     * Get temporary authorization key.
     *
     */
    public function getTempAuthKey(): TempAuthKey
    {
        return $this->getAuthKey(true);
    }
    /**
     * Get permanent authorization key.
     *
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
     */
    public function setTempAuthKey(?TempAuthKey $key): void
    {
        $this->setAuthKey($key, true);
    }
    /**
     * Set permanent authorization key.
     *
     * @param PermAuthKey|null $key Auth key
     *
     */
    public function setPermAuthKey(?PermAuthKey $key): void
    {
        $this->setAuthKey($key, false);
    }
    /**
     * Bind temporary and permanent auth keys.
     *
     * @param bool $pfs Whether to bind using PFS
     *
     */
    public function bind(bool $pfs = true): void
    {
        if (!$pfs && !$this->tempAuthKey) {
            $this->tempAuthKey = new TempAuthKey();
        }
        $this->tempAuthKey->bind($this->permAuthKey, $pfs);
    }
    /**
     * Check if auth keys are bound.
     *
     * @return boolean
     */
    public function isBound(): bool
    {
        return $this->tempAuthKey ? $this->tempAuthKey->isBound() : false;
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
     */
    public function authorized(bool $authorized): void
    {
        if ($authorized) {
            $this->getTempAuthKey()->authorized($authorized);
        } elseif ($this->hasTempAuthKey()) {
            $this->getTempAuthKey()->authorized($authorized);
        }
    }
    /**
     * Link permanent authorization info of main DC to media DC.
     *
     * @param string $dc Main DC ID
     *
     */
    public function link(string $dc): void
    {
        $this->linked = $dc;
        $this->permAuthKey =& $this->API->datacenter->getDataCenterConnection($dc)->permAuthKey;
    }
    /**
     * Reset MTProto sessions.
     *
     */
    public function resetSession(): void
    {
        foreach ($this->connections as $socket) {
            $socket->resetSession();
        }
    }
    /**
     * Create MTProto sessions if needed.
     *
     */
    public function createSession(): void
    {
        foreach ($this->connections as $socket) {
            $socket->createSession();
        }
    }
    /**
     * Flush all pending packets.
     *
     */
    public function flush(): void
    {
        $this->API->logger->logger("Flushing pending messages, DC {$this->datacenter}", \danog\MadelineProto\Logger::NOTICE);
        foreach ($this->connections as $socket) {
            $socket->flush();
        }
    }
    /**
     * Get connection context.
     *
     */
    public function getCtx(): ConnectionContext
    {
        return $this->ctx;
    }
    /**
     * Has connection context?
     *
     */
    public function hasCtx(): bool
    {
        return isset($this->ctx);
    }
    /**
     * Connect function.
     *
     * @param ConnectionContext $ctx Connection context
     * @param int               $id  Optional connection ID to reconnect
     *
     */
    public function connect(ConnectionContext $ctx, int $id = -1): \Generator
    {
        $this->API->logger->logger("Trying shared connection via {$ctx} ({$id})");
        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $media = $ctx->isMedia() || $ctx->isCDN();
        $count = $media ? $this->API->getSettings()->getConnection()->getMinMediaSocketCount() : 1;
        if ($count > 1) {
            if (!$this->robinLoop) {
                $this->robinLoop = new PeriodicLoopInternal($this->API, [$this, 'even'], "robin loop DC {$this->datacenter}", $this->API->getSettings()->getConnection()->getRobinPeriod() * 1000);
            }
            $this->robinLoop->start();
        }
        $this->decRead = $media ? self::READ_WEIGHT_MEDIA : self::READ_WEIGHT;
        $this->decWrite = self::WRITE_WEIGHT;
        if ($id === -1 || !isset($this->connections[$id])) {
            if ($this->connections) {
                $this->API->logger->logger("Already connected!", Logger::WARNING);
                return;
            }
            yield from $this->connectMore($count);
            yield $this->restoreBackup();
            $this->connectionsPromise = new Success();
            if ($this->connectionsDeferred) {
                $connectionsDeferred = $this->connectionsDeferred;
                $this->connectionsDeferred = null;
                $connectionsDeferred->resolve();
            }
        } else {
            $this->availableConnections[$id] = 0;
            yield from $this->connections[$id]->connect($ctx);
        }
    }
    /**
     * Connect to the DC using count more sockets.
     *
     * @param integer $count Number of sockets to open
     *
     */
    private function connectMore(int $count): \Generator
    {
        $ctx = $this->ctx->getCtx();
        $count += $previousCount = \count($this->connections);
        for ($x = $previousCount; $x < $count; $x++) {
            $connection = new Connection();
            $connection->setExtra($this, $x);
            yield from $connection->connect($ctx);
            $this->connections[$x] = $connection;
            $this->availableConnections[$x] = 0;
            $ctx = $this->ctx->getCtx();
        }
    }
    /**
     * Signal that a connection ID disconnected.
     *
     * @param integer $id Connection ID
     *
     */
    public function signalDisconnect(int $id): void
    {
        $backup = $this->connections[$id]->backupSession();
        $list = '';
        foreach ($backup as $k => $message) {
            if ($message->getConstructor() === 'msgs_state_req'
                || $message->getConstructor() === 'ping_delay_disconnect'
                || $message->isUnencrypted()) {
                unset($backup[$k]);
                continue;
            }
            $list .= $message->getConstructor();
            $list .= ', ';
        }
        $this->API->logger->logger("Backed up {$list} from DC {$this->datacenter}.{$id}");
        $this->backup = \array_merge($this->backup, $backup);
        unset($this->connections[$id], $this->availableConnections[$id]);
    }
    /**
     * Close all connections to DC.
     *
     */
    public function disconnect(): void
    {
        $this->connectionsDeferred = new Deferred();
        $this->connectionsPromise = $this->connectionsDeferred->promise();
        $this->API->logger->logger("Disconnecting from shared DC {$this->datacenter}");
        if ($this->robinLoop) {
            $this->robinLoop->signal(true);
            $this->robinLoop = null;
        }
        $before = \count($this->backup);
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }
        $count = \count($this->backup) - $before;
        $this->API->logger->logger("Backed up {$count}, added to {$before} existing messages) from DC {$this->datacenter}");
        $this->connections = [];
        $this->availableConnections = [];
    }
    /**
     * Reconnect to DC.
     *
     */
    public function reconnect(): \Generator
    {
        $this->API->logger->logger("Reconnecting shared DC {$this->datacenter}");
        $this->disconnect();
        yield from $this->connect($this->ctx);
    }
    /**
     * Restore backed up messages.
     *
     */
    public function restoreBackup(): void
    {
        $backup = $this->backup;
        $this->backup = [];
        $count = \count($backup);
        $this->API->logger->logger("Restoring {$count} messages to DC {$this->datacenter}");
        /** @var OutgoingMessage */
        foreach ($backup as $message) {
            if ($message->hasSeqno()) {
                $message->setSeqno(null);
            }
            if ($message->hasMsgId()) {
                $message->setMsgId(null);
            }
            if (!($message->getState() & OutgoingMessage::STATE_REPLIED)) {
                Tools::callFork($this->getConnection()->sendMessage($message, false));
            }
        }
        $this->flush();
    }
    /**
     * Get connection for authorization.
     *
     */
    public function getAuthConnection(): Connection
    {
        return $this->connections[0];
    }
    /**
     * Check if any connection is available.
     *
     * @param integer $id Connection ID
     *
     * @return bool|int
     */
    public function hasConnection(int $id = -1)
    {
        return $id < 0 ? \count($this->connections) : isset($this->connections[$id]);
    }
    /**
     * Get best socket in round robin, asynchronously.
     *
     *
     * @psalm-return \Generator<int, Promise, mixed, Connection>
     */
    public function waitGetConnection(): \Generator
    {
        if (empty($this->availableConnections)) {
            yield $this->connectionsPromise;
        }
        return $this->getConnection();
    }
    /**
     * Get best socket in round robin.
     *
     * @param integer $id Connection ID, for manual fetching
     *
     */
    public function getConnection(int $id = -1): Connection
    {
        if ($id >= 0) {
            return $this->connections[$id];
        }
        if (\count($this->availableConnections) <= 1) {
            return $this->connections[0];
        }
        $max = \max($this->availableConnections);
        $key = \array_search($max, $this->availableConnections);
        // Decrease to implement round robin
        $this->availableConnections[$key]--;
        return $this->connections[$key];
    }
    /**
     * Even out round robin values.
     *
     */
    public function even(): void
    {
        if (!$this->availableConnections) {
            return;
        }
        $min = \min($this->availableConnections);
        if ($min < 50) {
            foreach ($this->availableConnections as &$count) {
                $count += 50;
            }
        } elseif ($min < 100) {
            $max = $this->isMedia() || $this->isCDN() ? $this->API->getSettings()->getConnection()->getMaxMediaSocketCount() : 1;
            if (\count($this->availableConnections) < $max) {
                $this->connectMore(2);
            } else {
                foreach ($this->availableConnections as &$value) {
                    $value += 1000;
                }
            }
        }
    }
    /**
     * Indicate that one of the sockets is busy reading.
     *
     * @param boolean $reading Whether we're busy reading
     * @param int     $x       Connection ID
     *
     */
    public function reading(bool $reading, int $x): void
    {
        if (!isset($this->availableConnections[$x])) {
            return;
        }
        $this->availableConnections[$x] += $reading ? -$this->decRead : $this->decRead;
    }
    /**
     * Indicate that one of the sockets is busy writing.
     *
     * @param boolean $writing Whether we're busy writing
     * @param int     $x       Connection ID
     *
     */
    public function writing(bool $writing, int $x): void
    {
        if (!isset($this->availableConnections[$x])) {
            return;
        }
        $this->availableConnections[$x] += $writing ? -$this->decWrite : $this->decWrite;
    }
    /**
     * Set main instance.
     *
     * @param MTProto $API Main instance
     *
     */
    public function setExtra($API): void
    {
        $this->API = $API;
    }
    /**
     * Get main instance.
     *
     * @return MTProto
     */
    public function getExtra()
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
        return \in_array($this->ctx->getStreamName(), [HttpStream::class, HttpsStream::class]);
    }
    /**
     * Check if is connected directly by IP address.
     *
     * @return boolean
     */
    public function byIPAddress(): bool
    {
        return !$this->ctx->hasStreamName(WssStream::class) && !$this->ctx->hasStreamName(HttpsStream::class);
    }
    /**
     * Check if is a media connection.
     *
     * @return boolean
     */
    public function isMedia(): bool
    {
        return $this->ctx->isMedia();
    }
    /**
     * Check if is a CDN connection.
     *
     * @return boolean
     */
    public function isCDN(): bool
    {
        return $this->ctx->isCDN();
    }
    /**
     * Get DC-specific settings.
     *
     */
    public function getSettings(): ConnectionSettings
    {
        return $this->API->getSettings()->getConnection();
    }
    /**
     * Get global settings.
     *
     */
    public function getGenericSettings(): Settings
    {
        return $this->API->getSettings();
    }
    /**
     * JSON serialize function.
     *
     */
    public function jsonSerialize(): array
    {
        return $this->linked ? ['linked' => $this->linked, 'tempAuthKey' => $this->tempAuthKey] : ['permAuthKey' => $this->permAuthKey, 'tempAuthKey' => $this->tempAuthKey];
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
        return $this->linked ? ['linked', 'tempAuthKey'] : ['permAuthKey', 'tempAuthKey'];
    }
}
