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
use danog\MadelineProto\RPCError\RateLimitError;
use danog\MadelineProto\Settings\Connection as ConnectionSettings;
use danog\MadelineProto\Stream\ContextIterator;
use Revolt\EventLoop;
use WeakMap;
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
     * File requests waiting for another one of this DC to be answered, after a FLOOD_WAIT.
     *
     * @var array<int, DeferredFuture<null>>
     */
    private array $fileFloodWaiters = [];
    /**
     * Number of file requests (parts to download or upload) a media or CDN DC may have in flight, spread over its sockets.
     *
     * The server takes a number of them at once that varies over time, rejecting the others with FLOOD_WAITs, and drops
     * sockets with too many of them (or answers -429), so it is learned like a TCP congestion window: it grows by one
     * per window of answers while file requests wait for room, and it is halved on a FLOOD_WAIT, a dropped socket or a
     * -429 (at most once per window of answers, for the requests of a same burst).
     * It only grows while all the requests in flight were written to the sockets: when they queue up to be written
     * (parts to upload, limited by the bandwidth), more of them would only queue up, and be rejected once they reach
     * the server (sending each part to upload twice).
     */
    private float $fileWindow = 1.0;

    /**
     * File requests answered since the file window was last halved.
     */
    private float $fileAnswersSinceDecrease = INF;
    /**
     * File requests that got room in the window but weren't written to their socket yet.
     */
    private int $fileRequestsUnsent = 0;

    /**
     * Streams whose drop by the server was already counted (both the read and the write loop see it).
     *
     * @var ?WeakMap<object, true>
     */
    private ?WeakMap $droppedStreams = null;
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
            $logger->logger(\sprintf('Generating permanent authorization key for DC %s...', $this->datacenter), Logger::NOTICE);
            $connection->createAuthKey(false);
        } elseif ($state === ConnectionState::UNENCRYPTED) {
            $logger->logger(\sprintf('Generating temporary authorization key for DC %s...', $this->datacenter), Logger::NOTICE);
            $connection->createAuthKey(true);
        } elseif ($state === ConnectionState::ENCRYPTED_NOT_BOUND) {
            $expires_in = MTProto::PFS_DURATION;
            for ($retry_id_total = 1; $retry_id_total <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id_total++) {
                if ($this->auth->connectionState->getState() !== ConnectionState::ENCRYPTED_NOT_BOUND) {
                    // A binding answered with CONNECTION_NOT_INITED (ResponseHandler): an earlier binding went
                    // through but its answer was lost (e.g. with a session reset), the key is bound.
                    // Or the key was dropped: the next transition takes over either way.
                    $logger->logger("Not binding authorization keys anymore in DC {$this->datacenter}, now in state {$this->auth->connectionState->getState()->name}", Logger::NOTICE);
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
                    $message_key = substr(Crypt::sha1($encrypted_data), -16);
                    $padding = Tools::random(Tools::posmod(-\strlen($encrypted_data), 16));
                    [$aes_key, $aes_iv] = $this->auth->pfsKdf($message_key);
                    $encrypted_message = $this->auth->getID().$message_key.Crypt::igeEncrypt($encrypted_data.$padding, $aes_key, $aes_iv);
                    // Encrypting can suspend: the inner message must name the current session and keys (the
                    // request itself is queued, and survives reconnections).
                    if ($temp_session_id !== $connection->session_id
                        || $temp_auth_key_id !== $this->auth->getTempId() || $perm_auth_key_id !== $this->auth->getID()
                    ) {
                        continue;
                    }
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
                    $logger->logger("Got {$error->rpc} copying the authorization to DC {$this->datacenter}, waiting {$error->waitTime} seconds", Logger::WARNING);
                    delay($error->waitTime);
                } catch (RPCErrorException $error) {
                    // A newer export of the same account (another process of the account copying its authorization
                    // at the same time, for example) invalidates the exported bytes: export them again, after a
                    // random delay, so that the processes don't keep invalidating each other's.
                    if ($error->rpc !== 'AUTH_BYTES_INVALID' || $attempt++ === 5) {
                        throw $error;
                    }
                    $logger->logger("The exported authorization for DC {$this->datacenter} was invalidated, exporting it again", Logger::WARNING);
                    delay(random_int(100, 2000) / 1000);
                }
            }
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
            $this->wakeFileRequestWaiters();
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
        $this->API->logger("Backed up {$list} from DC {$this->datacenter}.{$id}");
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
        $this->API->logger("Disconnecting from shared DC {$this->datacenter}");
        $before = \count($this->backup);
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }
        $count = \count($this->backup) - $before;
        $this->API->logger("Backed up {$count}, added to {$before} existing messages) from DC {$this->datacenter}");
        $this->connections = [];
        // Requests waiting for room on the closed sockets wait for the new ones.
        $this->wakeFileRequestWaiters();
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
            if (!$message->resetForResend()) {
                continue;
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
     * Get a socket (see getConnection()), waiting for the sockets to be created.
     */
    public function waitGetConnection(): Connection
    {
        if (!$this->connections) {
            $this->connectionsPromise->await();
        }
        return $this->getConnection();
    }
    /**
     * Get a socket for a file request (a part to download or upload), waiting for one with room for it.
     *
     * On media and CDN DCs, each socket carries at most the learned file window of them: the least loaded one is
     * used, and more sockets are opened (up to the configured maximum) when all are full. Other DCs only have one
     * socket, used as before.
     *
     * fileRequestSent() must be called once the request is written to the returned socket (or failed to be), and
     * fileRequestDone() with the socket once it is answered (or failed).
     */
    public function waitGetFileConnection(?Cancellation $cancellation = null): Connection
    {
        if (!$this->auth->isMedia && !$this->auth->isCdn) {
            $connection = $this->waitGetConnection();
            $connection->fileRequests++;
            $this->fileRequestsUnsent++;
            return $connection;
        }
        $max = $this->API->getSettings()->getConnection()->getMaxMediaSocketCount();
        while (true) {
            if (!$this->connections) {
                $this->connectionsPromise->await($cancellation);
                continue;
            }
            if ($this->getFileRequestsInFlight() < (int) $this->fileWindow) {
                $best = $this->getLeastLoadedConnection();
                // One socket per file request in the window, up to the maximum.
                if ($best->fileRequests > 0 && \count($this->connections) < min($max, (int) ceil($this->fileWindow))) {
                    $this->connectMore(1);
                    continue;
                }
                $best->fileRequests++;
                $this->fileRequestsUnsent++;
                return $best;
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
     * Signal that a file request that got room with waitGetFileConnection() was written to its socket (or failed to be).
     */
    public function fileRequestSent(): void
    {
        $this->fileRequestsUnsent--;
    }
    /**
     * Signal that a file request sent with waitGetFileConnection() is over.
     *
     * @param bool $answered Whether the server answered it (with a result, not an error).
     */
    public function fileRequestDone(Connection $connection, bool $answered): void
    {
        $connection->fileRequests--;
        if ($answered) {
            $this->fileAnswersSinceDecrease++;
            if ($this->fileRequestWaiters && $this->fileRequestsUnsent === 0) {
                // More file requests than room for them, and the server and the sockets keep up: one more per window.
                $this->fileWindow += 1.0 / $this->fileWindow;
            }
        }
        $this->wakeFileRequestWaiters();
        $inFlight = $this->getFileRequestsInFlight() > 0;
        // One request rejected with FLOOD_WAIT is sent again per answer; all of them once nothing else is in flight,
        // as no other answer will wake them up.
        foreach ($this->fileFloodWaiters as $id => $deferred) {
            unset($this->fileFloodWaiters[$id]);
            $deferred->complete();
            if ($inFlight) {
                break;
            }
        }
    }
    /**
     * Signal that the server closed a socket: halve the file window if it carried file requests.
     *
     * @param object $stream The stream that was closed.
     */
    public function fileConnectionDropped(Connection $connection, object $stream): void
    {
        $this->droppedStreams ??= new WeakMap;
        if (isset($this->droppedStreams[$stream])) {
            return;
        }
        $this->droppedStreams[$stream] = true;
        if ($connection->fileRequests <= 0) {
            return;
        }
        $this->decreaseFileWindow("the server dropped a socket with {$connection->fileRequests} file requests in flight");
    }
    /**
     * Halve the file window, at most once per window of answers.
     */
    private function decreaseFileWindow(string $why): void
    {
        if ($this->fileAnswersSinceDecrease < $this->fileWindow) {
            return;
        }
        $previous = $this->fileWindow;
        $this->fileWindow = max(1.0, min($this->fileWindow, (float) $this->getFileRequestsInFlight()) / 2);
        $this->fileAnswersSinceDecrease = 0;
        $this->API->logger("File requests in flight in DC {$this->datacenter}: {$previous} => {$this->fileWindow}, as $why", Logger::NOTICE);
    }
    /**
     * Number of file requests in flight on the sockets of this DC.
     */
    private function getFileRequestsInFlight(): int
    {
        $inFlight = 0;
        foreach ($this->connections as $socket) {
            $inFlight += $socket->fileRequests;
        }
        return $inFlight;
    }
    /**
     * Wait before sending again a file request rejected with a FLOOD_WAIT.
     *
     * A FLOOD_WAIT on a file request doesn't block anything: it rejects the requests beyond those the server takes at
     * once (a request sent right after it goes through, whatever its wait time). Send it again once another file
     * request of this DC is answered, or after the wait time if none is in flight.
     */
    public function waitAfterFileFlood(RateLimitError $e, ?Cancellation $cancellation = null): void
    {
        $this->decreaseFileWindow("the server rejected a file request with {$e->rpc}");
        if ($this->getFileRequestsInFlight() === 0) {
            $e->wait($cancellation);
            return;
        }
        $deferred = new DeferredFuture;
        $id = spl_object_id($deferred);
        $this->fileFloodWaiters[$id] = $deferred;
        try {
            $deferred->getFuture()->await($cancellation);
        } finally {
            unset($this->fileFloodWaiters[$id]);
        }
    }
    /**
     * Wake up the requests waiting for a socket for a file request, to look for room again.
     */
    private function wakeFileRequestWaiters(): void
    {
        foreach ($this->fileRequestWaiters as $id => $deferred) {
            unset($this->fileRequestWaiters[$id]);
            $deferred->complete();
        }
    }
    /**
     * Get a socket: the one with the fewest file requests in flight, or the one with the given ID.
     *
     * @param integer $id Connection ID, for manual fetching
     *
     * @psalm-mutation-free
     */
    public function getConnection(int $id = -1): Connection
    {
        if ($id >= 0) {
            return $this->connections[$id];
        }
        return $this->getLeastLoadedConnection();
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
