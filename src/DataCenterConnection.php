<?php

declare(strict_types=1);

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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\DeferredFuture;
use Amp\Future;
use danog\MadelineProto\Loop\Generic\PeriodicLoopInternal;
use danog\MadelineProto\MTProto\ConnectionState;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Reactive\SimpleSubscriber;
use danog\MadelineProto\Settings\Connection as ConnectionSettings;
use danog\MadelineProto\Stream\ContextIterator;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

use function count;

/**
 * Datacenter connection.
 * @internal
 * @implements SimpleSubscriber<ConnectionState>
 */
final class DataCenterConnection implements SimpleSubscriber
{
    public const READ_WEIGHT = 1;
    public const READ_WEIGHT_MEDIA = 5;
    public const WRITE_WEIGHT = 10;

    /** @deprecated */
    private ?PermAuthKey $permAuthKey;

    /**
     * Promise for connection.
     *
     */
    private Future $connectionsPromise;
    /**
     * Deferred for connection.
     *
     */
    private ?DeferredFuture $connectionsDeferred = null;
    public readonly NewAuthKey $auth;
    /**
     * Connections open to a certain DC.
     *
     * @var array<int, Connection>
     */
    private array $connections = [];
    /**
     * Connection weights.
     *
     * @var array<int, int>
     */
    private array $availableConnections = [];
    /**
     * Connection contexts.
     */
    private ?ContextIterator $ctx = null;
    /**
     * Loop to keep weights at sane value.
     */
    private ?PeriodicLoopInternal $robinLoop = null;
    /**
     * Decrement roundrobin weight by this value if busy reading.
     *
     */
    private int $decRead = 1;
    /**
     * Decrement roundrobin weight by this value if busy writing.
     *
     */
    private int $decWrite = 10;
    /**
     * Backed up messages.
     *
     */
    private array $backup = [];
    /**
     * Whether this socket has to be reconnected.
     *
     */
    private bool $needsReconnect = false;

    public function __construct(private readonly MTProto $API, private readonly int $datacenter)
    {
        $media = DataCenter::isMedia($this->datacenter);
        $this->auth = new NewAuthKey(
            $media,
            $this->API->isCdn($this->datacenter),
            $this->datacenter,
            $this->API->loginState,
            $media ? $this->API->datacenter->getDataCenterConnection(-$this->datacenter)->auth : null
        );
        $this->auth->connectionState->subscribe($this);
    }

    public function importFromLegacy(self $legacy): void
    {
        $this->auth->setAuthKey($legacy->permAuthKey?->getAuthKey());
    }

    /**
     * @psalm-pure
     */
    public function __sleep()
    {
        return ['auth', 'API', 'datacenter'];
    }

    #[\Override]
    public function onSimpleStateChange($state): void
    {
        try {
            $this->initAuthorization($state);
        } catch (SecurityException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SecurityException("An error occurred while handling state transition to {$state->name} in DC {$this->datacenter}: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Indicate if this socket needs to be reconnected.
     *
     * @param boolean $needsReconnect Whether the socket has to be reconnected
     *
     * @psalm-external-mutation-free
     */
    public function needReconnect(bool $needsReconnect): void
    {
        $this->needsReconnect = $needsReconnect;
    }
    /**
     * Whether this sockets needs to be reconnected.
     */
    public function shouldReconnect(): bool
    {
        return $this->needsReconnect;
    }
    /**
     * @psalm-mutation-free
     */
    public function getCtxs(): ContextIterator
    {
        \assert($this->ctx !== null);
        return $this->ctx;
    }
    private function initAuthorization(ConnectionState $state): void
    {
        if (!isset($this->connectionsPromise)) {
            $this->API->datacenter->getDataCenterConnection($this->datacenter);
        }
        $logger = $this->API->logger;
        $this->waitGetConnection();
        $connection = $this->getAuthConnection();
        $this->createSession();

        // Skip old states in case of unexpected server-side abort back to the unencrypted state.
        if ($state !== $this->auth->connectionState->getState()) {
            $logger->logger("Skipping outdated auth key transition to {$state->name} in DC {$this->datacenter}", Logger::NOTICE);
            return;
        }
        $logger->logger("Handling auth key transition to {$state->name} in DC {$this->datacenter}", Logger::NOTICE);

        if ($state === ConnectionState::UNENCRYPTED_NO_PERMANENT) {
            Assert::false($this->auth->isMedia);
            Assert::false($this->auth->isCdn);
            $logger->logger(sprintf('Generating permanent authorization key for DC %s...', $this->datacenter), Logger::NOTICE);
            $connection->createAuthKey(false);
        } elseif ($state === ConnectionState::UNENCRYPTED) {
            $logger->logger(sprintf('Generating temporary authorization key for DC %s...', $this->datacenter), Logger::NOTICE);
            $connection->createAuthKey(true);
        } elseif ($state === ConnectionState::ENCRYPTED_NOT_BOUND) {
            $expires_in = MTProto::PFS_DURATION;
            for ($retry_id_total = 1; $retry_id_total <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id_total++) {
                try {
                    $logger->logger('Binding authorization keys...', Logger::VERBOSE);
                    $nonce = Tools::random(8);
                    $expires_at = time() + $expires_in;
                    $temp_auth_key_id = $this->auth->getTempId();
                    $perm_auth_key_id = $this->auth->getID();
                    $temp_session_id = $connection->session_id;
                    $message_data = ($this->API->getTL()->serializeObject(['type' => ''], ['_' => 'bind_auth_key_inner', 'nonce' => $nonce, 'temp_auth_key_id' => $temp_auth_key_id, 'perm_auth_key_id' => $perm_auth_key_id, 'temp_session_id' => $temp_session_id, 'expires_at' => $expires_at], 'bindTempAuthKey_inner'));
                    $message_id = $connection->msgIdHandler->generateMessageId();
                    $seq_no = 0;
                    $encrypted_data = Tools::random(16).Tools::packSignedLong($message_id).pack('VV', $seq_no, \strlen($message_data)).$message_data;
                    $message_key = substr(sha1($encrypted_data, true), -16);
                    $padding = Tools::random(Tools::posmod(-\strlen($encrypted_data), 16));
                    [$aes_key, $aes_iv] = $this->auth->pfsKdf($message_key);
                    $encrypted_message = $this->auth->getID().$message_key.Crypt::igeEncrypt($encrypted_data.$padding, $aes_key, $aes_iv);
                    $res = $connection->methodCallAsyncRead('auth.bindTempAuthKey', ['perm_auth_key_id' => $perm_auth_key_id, 'nonce' => $nonce, 'expires_at' => $expires_at, 'encrypted_message' => $encrypted_message, 'madelineMsgId' => $message_id, 'specialMethodType' => SpecialMethodType::UNAUTHED_METHOD]);
                    if ($res === true) {
                        $logger->logger("Bound temporary and permanent authorization keys, DC {$this->datacenter}", Logger::NOTICE);
                        $this->auth->bind();
                        return;
                    }
                } catch (SecurityException $e) {
                    $logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', Logger::WARNING);
                } catch (Exception $e) {
                    $logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', Logger::WARNING);
                } catch (RPCErrorException $e) {
                    $logger->logger('An RPCErrorException occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', Logger::WARNING);
                }
            }
            throw new SecurityException('An error occurred while binding temporary and permanent authorization keys.');
        } elseif ($state === ConnectionState::ENCRYPTED_NOT_INITED) {
            $this->API->logger('Writing client info (also executing help.getConfig)...', Logger::NOTICE);
            if ($this->auth->isCdn) {
                $message = $connection->mainPendingOutgoing->peek();
                Assert::notNull($message);
                $method = $message->getSerializedBody();
            } else {
                $method = $this->API->getTL()->serializeMethod(
                    'help.getConfig',
                    []
                );
            }
            $connection->methodCallAsyncRead('invokeWithLayer', [
                'layer' => $this->API->settings->getSchema()->getLayer(),
                'query' => $this->API->getTL()->serializeMethod(
                    'initConnection',
                    [
                        'api_id' => $this->API->settings->getAppInfo()->getApiId(),
                        'api_hash' => $this->API->settings->getAppInfo()->getApiHash(),
                        'device_model' => !$this->auth->isCdn ? $this->API->settings->getAppInfo()->getDeviceModel() : 'n/a',
                        'system_version' => !$this->auth->isCdn ? $this->API->settings->getAppInfo()->getSystemVersion() : 'n/a',
                        'app_version' => $this->API->settings->getAppInfo()->getAppVersion(),
                        'system_lang_code' => $this->API->settings->getAppInfo()->getSystemLangCode(),
                        'lang_code' => $this->API->settings->getAppInfo()->getLangCode(),
                        'lang_pack' => $this->API->settings->getAppInfo()->getLangPack(),
                        'proxy' => $connection->getInputClientProxy(),
                        'query' => $method,
                    ]
                ),
                'specialMethodType' => SpecialMethodType::UNAUTHED_METHOD,
            ]);
            $this->auth->init();
        } elseif ($state === ConnectionState::ENCRYPTED_NOT_AUTHED) {
            Assert::eq($this->API->loginState->getState()->state, API::LOGGED_IN);
            $authed = $this->API->loginState->getState()->authorizedDc;
            Assert::notNull($authed);

            $logger->logger('Trying to copy authorization from DC '.$authed.' to DC '.$this->datacenter);
            $authorized_socket =  $this->API->datacenter->getDataCenterConnection($authed);
            $authorized_socket->waitGetConnection();
            $e = $authorized_socket->getAuthConnection()->methodCallAsyncRead(
                'auth.exportAuthorization',
                ['dc_id' => $this->datacenter % 10_000, 'specialMethodType' => SpecialMethodType::USER_RELATED]
            );
            $e['specialMethodType'] = SpecialMethodType::UNAUTHED_METHOD;
            $connection->methodCallAsyncRead('auth.importAuthorization', $e);
            $this->auth->authorize();
        }

        $logger->logger("Finished auth key transition to {$state->name} in DC {$this->datacenter}", Logger::NOTICE);
    }

    /**
     * Reset MTProto sessions.
     */
    public function resetSession(string $why): void
    {
        foreach ($this->connections as $socket) {
            $socket->resetSession($why);
        }
    }
    /**
     * Create MTProto sessions if needed.
     */
    public function createSession(): void
    {
        foreach ($this->connections as $socket) {
            $socket->createSession();
        }
    }
    /**
     * Has connection context?
     *
     * @psalm-mutation-free
     */
    public function hasCtx(): bool
    {
        return isset($this->ctx);
    }
    /**
     * Connect function.
     *
     * @param int $id Optional connection ID to reconnect
     */
    public function connect(int $id = -1): void
    {
        $media = $this->auth->isMedia || $this->auth->isCdn;
        if ($media) {
            if (!$this->robinLoop) {
                $this->robinLoop = new PeriodicLoopInternal(
                    $this->API,
                    $this->even(...),
                    "robin loop DC {$this->datacenter}",
                    $this->API->getSettings()->getConnection()->getRobinPeriod()
                );
            }
            $this->robinLoop->start();
        }
        $this->decRead = $media ? self::READ_WEIGHT_MEDIA : self::READ_WEIGHT;
        $this->decWrite = self::WRITE_WEIGHT;
        if ($id === -1 || !isset($this->connections[$id])) {
            if ($this->connections) {
                $this->API->logger('Already connected!', Logger::WARNING);
                return;
            }
            $f = new DeferredFuture;
            $this->connectionsPromise = $f->getFuture();
            $this->connectMore(1);
            $f->complete();
            if (isset($this->connectionsDeferred)) {
                $connectionsDeferred = $this->connectionsDeferred;
                $this->connectionsDeferred = null;
                $connectionsDeferred->complete();
            }
            $this->restoreBackup();
        } else {
            $this->availableConnections[$id] = 0;
            $this->connections[$id]->setExtra($this, $this->datacenter, $id);
        }
    }
    /**
     * Connect to the DC using count more sockets.
     *
     * @param integer $count Number of sockets to open
     *
     * @psalm-external-mutation-free
     */
    private function connectMore(int $count): void
    {
        $count += $previousCount = \count($this->connections);
        for ($x = $previousCount; $x < $count; $x++) {
            $connection = new Connection();
            $connection->setExtra($this, $this->datacenter, $x);
            $this->connections[$x] = $connection;
            $this->availableConnections[$x] = 0;
        }
    }
    /**
     * Signal that a connection ID disconnected.
     *
     * @param integer $id Connection ID
     */
    public function signalDisconnect(int $id): void
    {
        $backup = $this->connections[$id]->backupSession();
        $list = '';
        foreach ($backup as $message) {
            $message->unlink();
            $list .= $message->constructor;
            $list .= ', ';
        }
        $this->API->logger("Backed up {$list} from DC {$this->datacenter}.{$id}");
        $this->backup = array_merge($this->backup, $backup);
        unset($this->connections[$id], $this->availableConnections[$id]);
    }
    /**
     * Close all connections to DC.
     */
    public function disconnect(): void
    {
        $this->connectionsDeferred = new DeferredFuture();
        $this->connectionsPromise = $this->connectionsDeferred->getFuture();
        if (!isset($this->ctx)) {
            return;
        }
        $this->API->logger("Disconnecting from shared DC {$this->datacenter}");
        if ($this->robinLoop) {
            $this->robinLoop->stop();
            $this->robinLoop = null;
        }
        $before = \count($this->backup);
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }
        $count = \count($this->backup) - $before;
        $this->API->logger("Backed up {$count}, added to {$before} existing messages) from DC {$this->datacenter}");
        $this->connections = [];
        $this->availableConnections = [];
    }
    /**
     * Reconnect to DC.
     */
    public function reconnect(): void
    {
        $this->API->logger("Reconnecting shared DC {$this->datacenter}");
        $this->disconnect();
        $this->connect();
    }
    /**
     * Restore backed up messages.
     */
    private function restoreBackup(): void
    {
        $backup = $this->backup;
        $this->backup = [];
        $count = \count($backup);
        $this->API->logger("Restoring {$count} messages to DC {$this->datacenter}");
        /** @var MTProtoOutgoingMessage */
        foreach ($backup as $message) {
            if ($message instanceof Container || $message->hasReply()) {
                continue;
            }
            if ($message->hasSeqno()) {
                $message->setSeqno(null);
            }
            if ($message->hasMsgId()) {
                $message->setMsgId(null);
            }
            $message->connection = $connection = $this->getConnection();
            $this->API->logger("Restoring $message to DC {$this->datacenter}");
            EventLoop::queue($connection->sendMessage(...), $message);
        }
    }
    /**
     * Get connection for authorization.
     *
     * @psalm-mutation-free
     */
    private function getAuthConnection(): Connection
    {
        return $this->connections[0];
    }
    /**
     * Check if any connection is available.
     *
     * @param integer $id Connection ID
     *
     * @psalm-mutation-free
     */
    public function hasConnection(int $id = -1): bool|int
    {
        return $id < 0 ? \count($this->connections) : isset($this->connections[$id]);
    }
    /**
     * Get best socket in round robin, asynchronously.
     */
    public function waitGetConnection(): Connection
    {
        if (empty($this->availableConnections)) {
            $this->connectionsPromise->await();
        }
        return $this->getConnection();
    }
    /**
     * Get best socket in round robin.
     *
     * @param integer $id Connection ID, for manual fetching
     *
     * @psalm-external-mutation-free
     */
    public function getConnection(int $id = -1): Connection
    {
        if ($id >= 0) {
            return $this->connections[$id];
        }
        if (\count($this->availableConnections) <= 1) {
            return $this->connections[0];
        }
        $max = max($this->availableConnections);
        $key = array_search($max, $this->availableConnections, true);
        // Decrease to implement round robin
        $this->availableConnections[$key]--;
        return $this->connections[$key];
    }
    /**
     * Even out round robin values.
     */
    public function even(): void
    {
        if (!$this->availableConnections) {
            return;
        }
        $min = min($this->availableConnections);
        if ($min < 50) {
            foreach ($this->availableConnections as &$count) {
                $count += 50;
            }
        } elseif ($min < 100) {
            $max = $this->auth->isMedia || $this->auth->isCdn ? $this->API->getSettings()->getConnection()->getMaxMediaSocketCount() : 1;
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
     * @psalm-external-mutation-free
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
     * @psalm-external-mutation-free
     */
    public function writing(bool $writing, int $x): void
    {
        if (!isset($this->availableConnections[$x])) {
            return;
        }
        $this->availableConnections[$x] += $writing ? -$this->decWrite : $this->decWrite;
    }
    /**
     * @psalm-external-mutation-free
     */
    public function setCtx(ContextIterator $ctx): void
    {
        $this->ctx = $ctx;
    }
    /**
     * Get main instance.
     */
    public function getExtra(): MTProto
    {
        return $this->API;
    }
    /**
     * Get DC-specific settings.
     *
     * @psalm-mutation-free
     */
    public function getSettings(): ConnectionSettings
    {
        return $this->API->getSettings()->getConnection();
    }
    /**
     * Get global settings.
     *
     * @psalm-mutation-free
     */
    public function getGenericSettings(): Settings
    {
        return $this->API->getSettings();
    }
}
