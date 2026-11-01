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

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\Future;
use danog\MadelineProto\MTProto\ConnectionState;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Reactive\SimpleSubscriber;
use danog\MadelineProto\RPCError\FloodWaitError;
use danog\MadelineProto\Settings\Connection as ConnectionSettings;
use danog\MadelineProto\Stream\ContextIterator;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

use function Amp\delay;
use function count;

/**
 * Datacenter connection.
 * @internal
 * @implements SimpleSubscriber<ConnectionState>
 */
final class DataCenterConnection implements SimpleSubscriber
{
    /**
     * Maximum number of file requests (parts of up to 1 MB) waiting for an answer on a socket of a media or CDN DC.
     *
     * The server answers them one after the other: more of them only queue up on the socket, delaying the last ones
     * past their timeout, while the server drops connections with too much pending (tdesktop keeps at most 2 MB of
     * parts in flight per session too, opening up to 8 sessions instead).
     */
    public const MAX_FILE_REQUESTS_PER_SOCKET = 2;
    /**
     * Maximum number of parallel sessions to the main DC, whatever the server allows (as in TDLib).
     */
    public const MAX_SESSIONS = 100;

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
     * Connection contexts.
     */
    private ?ContextIterator $ctx = null;
    /**
     * Requests waiting for a socket with room for a file request.
     *
     * @var array<int, DeferredFuture<null>>
     */
    private array $fileRequestWaiters = [];
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
    /**
     * The other sessions to the DC, each with its own temporary auth key bound to the permanent one, when the
     * server allows more than one (tmp_sessions, see setSessionCount()): this is the first one.
     *
     * @var list<self>
     */
    private array $parallelSessions = [];
    /**
     * Index of this session among the sessions to the DC (0 for the first one, which owns the others).
     */
    private int $sessionIndex = 0;
    /**
     * The first session to the DC, for a parallel session.
     */
    private ?self $firstSession = null;
    /**
     * Whether this parallel session was closed (see close()): it must never connect again.
     */
    private bool $closed = false;

    /**
     * @param ?self $firstSession The first session to the DC, for a parallel session (see setSessionCount())
     */
    public function __construct(private readonly MTProto $API, private readonly int $datacenter, ?self $firstSession = null, int $sessionIndex = 0)
    {
        $media = DataCenter::isMedia($this->datacenter);
        $this->sessionIndex = $sessionIndex;
        $this->firstSession = $firstSession;
        $this->auth = new NewAuthKey(
            $media,
            $this->API->isCdn($this->datacenter),
            $this->datacenter,
            $this->API->loginState,
            $firstSession?->auth ?? ($media ? $this->API->datacenter->getDataCenterConnection(-$this->datacenter)->auth : null)
        );
        $this->auth->connectionState->subscribe($this);
    }

    /**
     * DC ID, followed by the index of the session for the parallel sessions (e.g. 2#1), for logs.
     *
     * @psalm-mutation-free
     */
    public function getLabel(): string
    {
        return $this->sessionIndex === 0 ? (string) $this->datacenter : "{$this->datacenter}#{$this->sessionIndex}";
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
        return ['auth', 'API', 'datacenter', 'parallelSessions', 'sessionIndex', 'firstSession'];
    }

    #[\Override]
    public function onSimpleStateChange($state): void
    {
        if ($this->closed) {
            return;
        }
        try {
            $this->initAuthorization($state);
        } catch (\Throwable $e) {
            if ($this->closed) {
                // Closed meanwhile: its requests fail, the auth key it was setting up is not needed anymore.
                $this->API->logger("Abandoning the auth key transition to {$state->name} of the closed session to DC {$this->getLabel()}: $e", Logger::NOTICE);
                return;
            }
            if ($e instanceof SecurityException) {
                throw $e;
            }
            throw new SecurityException("An error occurred while handling state transition to {$state->name} in DC {$this->getLabel()}: ".$e->getMessage(), 0, $e);
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
            $logger->logger("Skipping outdated auth key transition to {$state->name} in DC {$this->getLabel()}", Logger::NOTICE);
            return;
        }
        $logger->logger("Handling auth key transition to {$state->name} in DC {$this->getLabel()}", Logger::NOTICE);

        if ($state === ConnectionState::UNENCRYPTED_NO_PERMANENT) {
            Assert::false($this->auth->isMedia);
            Assert::false($this->auth->isCdn);
            $logger->logger(\sprintf('Generating permanent authorization key for DC %s...', $this->getLabel()), Logger::NOTICE);
            $connection->createAuthKey(false);
        } elseif ($state === ConnectionState::UNENCRYPTED) {
            $logger->logger(\sprintf('Generating temporary authorization key for DC %s...', $this->getLabel()), Logger::NOTICE);
            $connection->createAuthKey(true);
        } elseif ($state === ConnectionState::ENCRYPTED_NOT_BOUND) {
            $expires_in = MTProto::PFS_DURATION;
            for ($retry_id_total = 1; $retry_id_total <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id_total++) {
                if ($this->auth->connectionState->getState() !== ConnectionState::ENCRYPTED_NOT_BOUND) {
                    // A binding answered with CONNECTION_NOT_INITED (ResponseHandler): an earlier binding went
                    // through but its answer was lost (e.g. with a session reset), the key is bound.
                    // Or the key was dropped: the next transition takes over either way.
                    $logger->logger("Not binding authorization keys anymore in DC {$this->getLabel()}, now in state {$this->auth->connectionState->getState()->name}", Logger::NOTICE);
                    return;
                }
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
                        $logger->logger("Bound temporary and permanent authorization keys, DC {$this->getLabel()}", Logger::NOTICE);
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

            $logger->logger('Trying to copy authorization from DC '.$authed.' to DC '.$this->getLabel());
            $authorized_socket =  $this->API->datacenter->getDataCenterConnection($authed);
            $authorized_socket->waitGetConnection();
            for ($attempt = 1; ;) {
                try {
                    $e = $authorized_socket->getAuthConnection()->methodCallAsyncRead(
                        'auth.exportAuthorization',
                        ['dc_id' => $this->datacenter % 10_000, 'specialMethodType' => SpecialMethodType::USER_RELATED]
                    );
                    $e['specialMethodType'] = SpecialMethodType::UNAUTHED_METHOD;
                    $connection->methodCallAsyncRead('auth.importAuthorization', $e);
                    break;
                } catch (FloodWaitError $error) {
                    // Many processes of the account copying its authorization at once (CI runs creating sessions)
                    // get FLOOD_WAITs longer than the flood timeout: wait them out, as this runs in the transition of
                    // the state of the connection, where an exception kills the process, and the connection can't be
                    // used without the authorization anyway. Not through the floodWaitLimit of the calls: their
                    // timeout (the RPC drop timeout) would expire first.
                    $logger->logger("Got {$error->rpc} copying the authorization to DC {$this->getLabel()}, waiting {$error->waitTime} seconds", Logger::WARNING);
                    delay($error->waitTime);
                } catch (RPCErrorException $error) {
                    // A newer export of the same account (another process of the account copying its authorization
                    // at the same time, for example) invalidates the exported bytes: export them again, after a
                    // random delay, so that the processes don't keep invalidating each other's.
                    if ($error->rpc !== 'AUTH_BYTES_INVALID' || $attempt++ === 5) {
                        throw $error;
                    }
                    $logger->logger("The exported authorization for DC {$this->getLabel()} was invalidated, exporting it again", Logger::WARNING);
                    delay(random_int(100, 2000) / 1000);
                }
            }
            $this->auth->authorize();
        }

        $logger->logger("Finished auth key transition to {$state->name} in DC {$this->getLabel()}", Logger::NOTICE);
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
     * Set the number of sessions to the DC: one, or up to tmp_sessions to the main DC of the logged in account.
     *
     * Each parallel session has its own temporary auth key, bound to the permanent one (the server requires it
     * when it allows several sessions), and one socket: once its key is ready, calls are spread over the sessions
     * (see getConnection()), and the updates the server sends through any of them are handled as usual.
     * They are saved along with their temporary keys, so that a restart doesn't open more sessions than allowed.
     *
     * Only on the first session of a DC with a permanent auth key (not media or CDN DCs).
     */
    public function setSessionCount(int $count): void
    {
        Assert::eq($this->sessionIndex, 0);
        Assert::false($this->auth->isMedia);
        Assert::false($this->auth->isCdn);
        $count = max(1, min($count, self::MAX_SESSIONS));
        if ($count === \count($this->parallelSessions) + 1) {
            return;
        }
        $this->API->logger("Using $count sessions for DC {$this->datacenter}", Logger::NOTICE);
        while (\count($this->parallelSessions) + 1 < $count) {
            $session = new self($this->API, $this->datacenter, $this, \count($this->parallelSessions) + 1);
            $this->parallelSessions[] = $session;
            if (isset($this->ctx)) {
                // Otherwise connected along with this one (DataCenter::getDataCenterConnection()).
                $session->setCtx($this->ctx);
                $session->connect();
            }
        }
        while (\count($this->parallelSessions) + 1 > $count) {
            array_pop($this->parallelSessions)->close();
        }
    }
    /**
     * Close this parallel session for good: the server allows fewer sessions, or the account logged out.
     *
     * Nothing must connect it again, or there would be more sessions than allowed (the server would invalidate
     * the auth key with AUTH_KEY_DUPLICATED): its socket is closed, its auth key stops following the main key,
     * and its requests are sent through the first session (see forward()), now and if sent or resent later
     * (by a loop of its socket, or a call waiting for a flood wait, say).
     */
    private function close(): void
    {
        Assert::notNull($this->firstSession);
        $this->closed = true;
        $this->auth->detach($this->API->loginState);
        $this->disconnect();
        $backup = $this->backup;
        $this->backup = [];
        foreach ($backup as $message) {
            $this->forward($message);
        }
    }
    /**
     * Whether this parallel session was closed (see close()).
     *
     * @psalm-mutation-free
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }
    /**
     * Send a message of this closed session through the first session, or fail it if it belongs to this session
     * (the exchange, binding and initialization of its auth key).
     *
     * @internal
     */
    public function forward(MTProtoOutgoingMessage $message): void
    {
        Assert::true($this->closed);
        if ($message instanceof Container || $message->hasReply()) {
            return;
        }
        if ($message->unencrypted || $message->specialMethodType === SpecialMethodType::UNAUTHED_METHOD) {
            $message->reply(fn () => new Exception("The session to DC {$this->getLabel()} was closed"));
            return;
        }
        if (!$message->resetForResend()) {
            return;
        }
        $this->API->logger("Sending $message of the closed session to DC {$this->getLabel()} through the first one");
        $firstSession = $this->firstSession;
        EventLoop::queue(static function () use ($firstSession, $message): void {
            $firstSession->waitGetConnection()->sendMessage($message);
        });
    }
    /**
     * Get this session and the parallel ones (see setSessionCount()).
     *
     * @return non-empty-list<self>
     *
     * @psalm-mutation-free
     */
    public function getSessions(): array
    {
        return [$this, ...$this->parallelSessions];
    }
    /**
     * Get the index of this session among the ones to the DC (see setSessionCount()).
     *
     * @psalm-mutation-free
     */
    public function getSessionIndex(): int
    {
        return $this->sessionIndex;
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
        if ($this->closed) {
            return;
        }
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
            // Requests waiting for room on the sockets that were closed can use the new ones.
            $this->wakeFileRequestWaiters(true);
        } else {
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
        $this->API->logger("Backed up {$list} from DC {$this->getLabel()}.{$id}");
        $this->backup = array_merge($this->backup, $backup);
        unset($this->connections[$id]);
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
        $this->API->logger("Disconnecting from shared DC {$this->getLabel()}");
        $before = \count($this->backup);
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }
        $count = \count($this->backup) - $before;
        $this->API->logger("Backed up {$count}, added to {$before} existing messages) from DC {$this->getLabel()}");
        $this->connections = [];
        // Requests waiting for room on the closed sockets wait for the new ones.
        $this->wakeFileRequestWaiters(true);
    }
    /**
     * Reconnect to DC.
     */
    public function reconnect(): void
    {
        $this->API->logger("Reconnecting shared DC {$this->getLabel()}");
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
        $this->API->logger("Restoring {$count} messages to DC {$this->getLabel()}");
        /** @var MTProtoOutgoingMessage */
        foreach ($backup as $message) {
            if ($message instanceof Container || $message->hasReply()) {
                continue;
            }
            if (!$message->resetForResend()) {
                continue;
            }
            $message->connection = $connection = $this->getConnection();
            $this->API->logger("Restoring $message to DC {$this->getLabel()}");
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
     * Get a socket (see getConnection()), waiting for the sockets to be created.
     *
     * @param ?string $affinity Calls with the same affinity (the same peer) are sent through the same session
     */
    public function waitGetConnection(?string $affinity = null): Connection
    {
        if (!$this->connections) {
            $this->connectionsPromise->await();
        }
        return $this->getConnection(affinity: $affinity);
    }
    /**
     * Get a socket for a file request (a part to download or upload), waiting for one with room for it.
     *
     * On media and CDN DCs, each socket carries at most MAX_FILE_REQUESTS_PER_SOCKET of them: the least loaded
     * one is used, and more sockets are opened (up to the configured maximum) when all are full. Other DCs only
     * have one socket, used as before.
     *
     * fileRequestDone() must be called with the returned socket once the request is answered (or failed).
     */
    public function waitGetFileConnection(?Cancellation $cancellation = null): Connection
    {
        if (!$this->auth->isMedia && !$this->auth->isCdn) {
            $connection = $this->waitGetConnection();
            $connection->fileRequests++;
            return $connection;
        }
        $max = $this->API->getSettings()->getConnection()->getMaxMediaSocketCount();
        while (true) {
            if (!$this->connections) {
                $this->connectionsPromise->await($cancellation);
                continue;
            }
            $best = $this->getLeastLoadedConnection();
            if ($best->fileRequests < self::MAX_FILE_REQUESTS_PER_SOCKET) {
                $best->fileRequests++;
                return $best;
            }
            if (\count($this->connections) < $max) {
                $this->connectMore(1);
                continue;
            }
            $deferred = new DeferredFuture;
            $id = spl_object_id($deferred);
            $this->fileRequestWaiters[$id] = $deferred;
            try {
                $deferred->getFuture()->await($cancellation);
            } finally {
                unset($this->fileRequestWaiters[$id]);
            }
        }
    }
    /**
     * Signal that a file request sent with waitGetFileConnection() was answered (or failed).
     */
    public function fileRequestDone(Connection $connection): void
    {
        $connection->fileRequests--;
        $this->wakeFileRequestWaiters(false);
    }
    /**
     * Wake up the requests waiting for a socket for a file request (one, or all of them when the sockets changed).
     */
    private function wakeFileRequestWaiters(bool $all): void
    {
        foreach ($this->fileRequestWaiters as $id => $deferred) {
            unset($this->fileRequestWaiters[$id]);
            $deferred->complete();
            if (!$all) {
                return;
            }
        }
    }
    /**
     * Get a socket: the one with the given ID, or the one with the fewest file requests in flight.
     *
     * With parallel sessions (see setSessionCount()), the socket of a session whose key is ready: the one of the
     * session of the affinity if given (so that the calls about a chat are sent in order through one session, as
     * before), or the one with the fewest calls waiting for an answer.
     *
     * @param integer $id       Connection ID, for manual fetching
     * @param ?string $affinity Calls with the same affinity (the same peer) are sent through the same session
     */
    public function getConnection(int $id = -1, ?string $affinity = null): Connection
    {
        if ($id >= 0) {
            return $this->connections[$id];
        }
        $best = $this->getLeastLoadedConnection();
        if (!$this->parallelSessions) {
            return $best;
        }
        if ($affinity !== null) {
            $index = crc32($affinity) % (\count($this->parallelSessions) + 1);
            if ($index !== 0 && $this->parallelSessions[$index - 1]->isReady()) {
                return $this->parallelSessions[$index - 1]->getLeastLoadedConnection();
            }
            return $best;
        }
        $equal = 1;
        foreach ($this->parallelSessions as $session) {
            if (!$session->isReady()) {
                continue;
            }
            $connection = $session->getLeastLoadedConnection();
            if ($connection->pendingCalls < $best->pendingCalls) {
                $best = $connection;
                $equal = 1;
            } elseif ($connection->pendingCalls === $best->pendingCalls && random_int(0, $equal++) === 0) {
                // A random one among the least loaded, as in TDLib: all the sessions are used while idle too.
                $best = $connection;
            }
        }
        return $best;
    }
    /**
     * Whether calls can be sent through this parallel session: its key is authorized, and it has a socket.
     *
     * Until then, its calls go through the other sessions instead of waiting for its key.
     *
     * @psalm-mutation-free
     */
    private function isReady(): bool
    {
        return $this->connections && $this->auth->connectionState->getState() === ConnectionState::ENCRYPTED;
    }
    /**
     * Get the socket with the fewest file requests in flight.
     *
     * @psalm-mutation-free
     */
    private function getLeastLoadedConnection(): Connection
    {
        $best = null;
        foreach ($this->connections as $connection) {
            if ($best === null || $connection->fileRequests < $best->fileRequests) {
                $best = $connection;
            }
        }
        \assert($best !== null);
        return $best;
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
