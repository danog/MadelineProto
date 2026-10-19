<?php

declare(strict_types=1);

/**
 * Serialization module.
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
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\TimeoutException;
use AssertionError;
use danog\AsyncOrm\DbArrayBuilder;
use danog\AsyncOrm\Driver\DriverArray;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\ValueType;
use danog\MadelineProto\Ipc\AlreadyServedException;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Settings\Database\DriverDatabaseAbstract;
use Revolt\EventLoop;
use Throwable;

use const LOCK_EX;
use const LOCK_NB;
use const LOCK_SH;
use const LOCK_UN;

use function Amp\async;
use function Amp\delay;
use function Amp\File\exists;
use function Amp\Ipc\connect;

/**
 * Manages serialization of the MadelineProto instance.
 *
 * @internal
 */
abstract class Serialization
{
    /**
     * Unserialize session.
     *
     * Logic for deserialization is as follows.
     * - If the session is unlocked
     *   - Try starting IPC server:
     *     - Fetch light state
     *     - If don't need event handler
     *       - Unlock
     *       - Fork
     *       - Lock (fork)
     *       - Deserialize full (fork)
     *       - Start IPC server (fork)
     *       - Store IPC state (fork)
     *     - If need event handler
     *       - If have event handler class
     *         - Deserialize full
     *         - Start IPC server
     *         - Store IPC state
     *     - Else Fallthrough
     *   - Wait for a new IPC state for a maximum of 30 seconds, then throw
     *   - Execute original request via IPC
     *
     * - If the session is locked
     *   - In parallel (concurrent):
     *     - The IPC server should be running, connect
     *     - Try starting full session
     *       - Fetch light state
     *       - If don't need event handler
     *         - Wait lock
     *         - Unlock
     *         - Fork
     *         - Lock (fork)
     *         - Deserialize full (fork)
     *         - Start IPC server (fork)
     *         - Store IPC state (fork)
     *       - If need event handler and have event handler class
     *         - Wait lock
     *         - Deserialize full
     *         - Start IPC server
     *         - Store IPC state
     *   - Wait for a new IPC session for a maximum of 30 seconds, then throw
     *   - Execute original request via IPC
     *
     *
     *
     * - If receiving a startAndLoop or setEventHandler request on an IPC session:
     *   - Shutdown remote IPC server
     *   - Deserialize full
     *   - Start IPC server
     *   - Store IPC state
     *
     * @param SessionPaths     $session   Session name
     * @param SettingsAbstract $settings  Settings
     * @param bool             $forceFull Whether to force full session deserialization
     * @param bool             $selfRestart Whether full deserialization is only forced by a self-restart, which waits for its predecessor to release the lock: connect to a live server instead, which won't release it
     * @internal
     * @return array{0: (ChannelledSocket|APIWrapper|Throwable|null|0), 1: (callable|null)}
     */
    public static function unserialize(SessionPaths $session, SettingsAbstract $settings, bool $forceFull = false, bool $selfRestart = false): array
    {
        //Logger::log('Waiting for exclusive session lock...');
        $warningId = EventLoop::delay(1, static function () use (&$warningId): void {
            if (isset($_GET['MadelineSelfRestart'])) {
                Logger::log("MadelineProto self-restarted successfully!");
            } else {
                Logger::log('It seems like the session is busy.');
                Logger::log('Telegram does not support starting multiple instances of the same session, make sure no other instance of the session is running.');
                $warningId = EventLoop::repeat(5, static fn () => Logger::log('Still waiting for exclusive session lock...'));
                EventLoop::unreference($warningId);
            }
        });
        EventLoop::unreference($warningId);

        $lightState = null;
        $cancelFlock = new DeferredCancellation;
        $cancelIpc = new DeferredFuture;
        // Cancelled once we stop waiting for the lock
        $lockWait = new DeferredCancellation;
        $canContinue = true;
        $ipcSocket = null;
        $alreadyServed = false;
        $cancelFull = static function () use (&$cancelFlock): void {
            if ($cancelFlock !== null) {
                $copy = $cancelFlock;
                $cancelFlock = null;
                $copy->cancel();
            }
        };
        if (!$forceFull) {
            $failureCb = static function () use ($session, $cancelFull, $lockWait, &$canContinue, &$ipcSocket, &$lightState): void {
                if ($lockWait->isCancelled()) {
                    return;
                }
                $lightStateRead = async(static function () use ($session, $cancelFull, &$canContinue, &$lightState): void {
                    try {
                        $lightState = $session->getLightState();
                        if (!$lightState->canStartIpc()) {
                            $canContinue = false;
                            $cancelFull();
                        }
                    } catch (Throwable) {
                        $lightState = false;
                    }
                });
                try {
                    // The lock holder is an instance that serves (or will serve) the session once it's loaded, or a
                    // client that releases the lock in a moment: connect as soon as the holder listens, however
                    // long it takes to load the session, unless we get the lock first.
                    while (!$lockWait->isCancelled()) {
                        Logger::log('MadelineProto is starting, please wait...');
                        try {
                            clearstatcache(true, $session->getIpcPath());
                            $socket = connect($session->getIpcPath());
                            Logger::log('Connected to IPC socket!');
                            $ipcSocket = [$socket, null];
                            $cancelFull();
                            return;
                        } catch (Throwable $e) {
                            $e = $e->getMessage();
                            if ($e !== 'The endpoint does not exist!') {
                                Logger::log("$e while connecting to IPC socket");
                            }
                        }
                        try {
                            delay(1.0, true, $lockWait->getCancellation());
                        } catch (CancelledException) {
                        }
                    }
                } finally {
                    // Its result is used once we get the lock, and it must not outlive this call: at shutdown,
                    // ext-uv runs the callbacks of the file operations still in flight on its destroyed loop.
                    $lightStateRead->await();
                }
            };
        } elseif (Magic::$isIpcWorker) {
            // An IPC worker that doesn't get the lock was started concurrently with another one (by concurrent
            // clients, or by the process and the web runner of the same client): if the lock holder serves the
            // session, this worker is redundant and must exit, without reporting an error.
            // Only a server that answers counts: one that is shutting down may still have its socket bound.
            $failureCb = static function () use ($session, $cancelFull, $lockWait, &$alreadyServed): void {
                while (!$lockWait->isCancelled()) {
                    if (self::isServed($session->getIpcPath(), $lockWait->getCancellation())) {
                        $alreadyServed = true;
                        $cancelFull();
                        return;
                    }
                    try {
                        delay(1.0, true, $lockWait->getCancellation());
                    } catch (CancelledException) {
                    }
                }
            };
        } elseif ($selfRestart) {
            // A self-restarted request forces full instances to take over the session of the request it replaces,
            // which releases the lock as it exits; but the script may also open other sessions, served by an IPC
            // worker that never releases the lock: a live server answers, so connect to it instead of waiting
            // forever. If it's the event handler's session, startAndLoop() then stops the server and takes over.
            $failureCb = static function () use ($session, $cancelFull, $lockWait, &$ipcSocket): void {
                while (!$lockWait->isCancelled()) {
                    if (self::isServed($session->getIpcPath(), $lockWait->getCancellation())) {
                        try {
                            $socket = connect($session->getIpcPath());
                            Logger::log('Connected to IPC socket!');
                            $ipcSocket = [$socket, null];
                            $cancelFull();
                            return;
                        } catch (Throwable) {
                        }
                    }
                    try {
                        delay(1.0, true, $lockWait->getCancellation());
                    } catch (CancelledException) {
                    }
                }
            };
        } else {
            $failureCb = null;
        }
        // Awaited once we stop waiting for the lock, so that it doesn't outlive this call
        $failure = null;
        try {
            $unlock = Tools::flock(
                $session->getLockPath(),
                LOCK_EX,
                1,
                $cancelFlock->getCancellation(),
                $failureCb ? static function () use ($failureCb, &$failure): void {
                    $failure = async($failureCb);
                } : null,
            );
        } finally {
            $lockWait->cancel();
            EventLoop::cancel($warningId);
            $failure?->await();
        }

        if (!$unlock) { // Canceled, don't have lock
            if ($alreadyServed) {
                throw new AlreadyServedException("Another process is already serving the session {$session->getSessionDirectoryPath()}");
            }
            return $ipcSocket;
        }
        if (!$canContinue) { // Have lock, can't use it
            Logger::log("Session has event handler, but it's not started.", Logger::ERROR);
            Logger::log("We don't have access to the event handler class, so we can't start it.", Logger::ERROR);
            Logger::log('Please start the event handler or unset it to use the IPC server.', Logger::ERROR);
            $unlock();
            return $ipcSocket;
        }

        try {
            $lightState ??= $session->getLightState();
        } catch (Throwable) {
        }

        \assert($lightState === null || $lightState instanceof LightState);

        $exists = exists($session->getSessionPath());

        if ($lightState && !$forceFull) {
            if (!$class = $lightState->getEventHandler()) {
                // Unlock and fork
                $unlock();
                return self::startServerAndConnect($session, $ipcSocket);
            } elseif (!class_exists($class)) {
                // Have lock, can't use it
                $unlock();
                Logger::log("Session has event handler (class $class), but it's not started.", Logger::ERROR);
                Logger::log("We don't have access to the event handler class, so we can't start it.", Logger::ERROR);
                Logger::log('Please start the event handler or unset it to use the IPC server.', Logger::ERROR);
                return $ipcSocket ?? self::tryConnect($session->getIpcPath(), $cancelIpc->getFuture(), customE: new AssertionError("Please make sure the $class class is in scope, or that the event handler is running (in a separate process or in the current process)."));
            } elseif (is_subclass_of($class, EventHandler::class)) {
                EventHandler::cachePlugins($class);
            }
        } elseif ($lightState) {
            $class = $lightState->getEventHandler();
            if ($class && !class_exists($class)) {
                // Have lock, can't use it
                $unlock();
                Logger::log("Session has event handler, but it's not started.", Logger::ERROR);
                Logger::log("We don't have access to the event handler class, so we can't start it.", Logger::ERROR);
                Logger::log('Please start the event handler or unset it to use the IPC server.', Logger::ERROR);
                throw new AssertionError("Please make sure the $class class is in scope, or that the event handler is running (in a separate process or in the current process).");
            } elseif ($class && is_subclass_of($class, EventHandler::class)) {
                EventHandler::cachePlugins($class);
            }
        } elseif ($exists) {
            throw new AssertionError("Could not read the lightstate file, check logs!");
        }

        $tempId = Shutdown::addCallback($unlock = static function () use ($unlock): void {
            Logger::log('Unlocking exclusive session lock!');
            $unlock();
            Logger::log('Unlocked exclusive session lock!');
        });
        Logger::log('Got exclusive session lock!');

        if ($exists) {
            $unserialized = $session->unserialize();
        } else {
            $unserialized = null;
        }
        if ($settings instanceof Settings) {
            $settings = $settings->getDb();
        }
        $prefix = null;
        if ($unserialized instanceof DriverArray
            || $unserialized instanceof DbArrayBuilder
            || (
                !$exists
                && $settings instanceof DriverDatabaseAbstract
                && $prefix = $settings->getEphemeralFilesystemPrefix()
            )
        ) {
            $tableName = null;
            $array = null;
            if ($prefix !== null) {
                $tableName = "{$prefix}_MTProto_session";
            } elseif ($unserialized instanceof DriverArray) {
                $unserialized = (array) $unserialized;
                $tableName = $unserialized["\0*\0table"];
                $settings = $unserialized["\0*\0dbSettings"];
            } else {
                \assert($unserialized instanceof DbArrayBuilder);
                $array = $unserialized->build();
            }
            if ($tableName !== null && $settings instanceof DriverDatabaseAbstract) {
                Logger::log('Extracting session from database...');
                $array = (new DbArrayBuilder(
                    $tableName,
                    $settings->getOrmSettings(),
                    KeyType::STRING,
                    ValueType::SCALAR,
                ))->build();
            }
            \assert($array !== null);
            $unserialized = $array->get('data');
            if (!$unserialized && $exists) {
                throw new Exception('Could not extract session from database!');
            }
        }
        \assert($unserialized instanceof APIWrapper || $unserialized === null);

        Shutdown::removeCallback($tempId);
        return [$unserialized, $unlock];
    }

    /**
     * Whether the session lock is held by another process (an instance loading or serving the session, or a client
     * about to start one): the IPC server, if any, can only be started by the lock holder.
     */
    public static function isLockedByAnotherProcess(SessionPaths $session): bool
    {
        $res = @fopen($session->getLockPath(), 'c');
        if (!$res) {
            return false;
        }
        try {
            if (flock($res, LOCK_SH|LOCK_NB)) {
                flock($res, LOCK_UN);
                return false;
            }
            return true;
        } finally {
            fclose($res);
        }
    }

    /**
     * Whether a live server answers on the IPC socket.
     *
     * Connecting is not enough: the socket of a server that is shutting down may still be bound.
     */
    private static function isServed(string $ipcPath, Cancellation $cancellation): bool
    {
        try {
            clearstatcache(true, $ipcPath);
            $socket = connect($ipcPath);
        } catch (Throwable) {
            return false;
        }
        try {
            $socket->send(['isIpcWorker', []]);
            // The server answers once it has loaded the session
            $res = async($socket->receive(...))->await($cancellation);
            return \is_array($res);
        } catch (Throwable) {
            return false;
        } finally {
            try {
                $socket->disconnect();
            } catch (Throwable) {
            }
        }
    }

    /**
     * Start the IPC server of a session, and connect to it (or to the one another process started concurrently).
     *
     * @internal
     *
     * @param  ?array{0: ChannelledSocket, 1: null}              $connected An existing connection to return instead
     * @return array{0: (ChannelledSocket|Throwable|0), 1: null}
     */
    public static function startServerAndConnect(SessionPaths $session, ?array $connected = null): array
    {
        $stopMonitor = new DeferredCancellation;
        $monitor = Server::startMe($session, $stopMonitor->getCancellation());
        $result = null;
        try {
            return $result = $connected ?? self::tryConnect($session->getIpcPath(), $monitor, waitStartup: true);
        } finally {
            // We're connected (or gave up) before the server reports its startup, possibly: the monitor polls the IPC
            // state file until then, so it must not outlive this call. At shutdown, ext-uv runs the callbacks of the
            // file operations still in flight on its destroyed loop.
            $stopMonitor->cancel();
            try {
                $monitor->await();
            } catch (Throwable $e) {
                // Otherwise tryConnect threw it already
                if ($result !== null) {
                    Logger::log("Error while monitoring the IPC server startup: $e", Logger::ERROR);
                }
            }
        }
    }

    /**
     * Try connecting to IPC socket.
     *
     * @param  string                                            $ipcPath       IPC path
     * @param  Future<(bool|Throwable|null)>                     $cancelConnect Cancelation token (triggers cancellation of connection)
     * @param  null|callable(): void                             $cancelFull    Cancelation token source (can trigger cancellation of full unserialization)
     * @param  bool                                              $waitStartup   Whether $cancelConnect is the monitor of a server startup: the attempts only count once it's resolved, since the server only listens once it has loaded the session, however long that takes
     * @return array{0: (ChannelledSocket|Throwable|0), 1: null}
     */
    public static function tryConnect(string $ipcPath, Future $cancelConnect, ?callable $cancelFull = null, ?Throwable $customE = null, bool $waitStartup = false): array
    {
        $x = 0;
        while ($waitStartup || $x++ < 25) {
            Logger::log('MadelineProto is starting, please wait...');
            try {
                clearstatcache(true, $ipcPath);
                $socket = connect($ipcPath);
                Logger::log('Connected to IPC socket!');
                if ($cancelFull) {
                    $cancelFull();
                }
                return [$socket, null];
            } catch (Throwable $e) {
                $e = $e->getMessage();
                if ($e !== 'The endpoint does not exist!') {
                    Logger::log("$e while connecting to IPC socket");
                }
            }
            try {
                $res = $cancelConnect->await(Tools::getTimeoutCancellation(1.0, "Timeout while connecting to IPC socket"));
                if ($res instanceof Throwable) {
                    return [$res, null];
                }
                // The server started (or the startup is over): it listens now, if it ever will
                $cancelConnect = (new DeferredFuture)->getFuture();
                $waitStartup = false;
            } catch (CancelledException $e) {
                if (!$e->getPrevious() instanceof TimeoutException) {
                    throw $e;
                }
            }
        }
        return [$customE ?? 0, null];
    }
}
