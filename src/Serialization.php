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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

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
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Settings\Database\DriverDatabaseAbstract;
use Revolt\EventLoop;
use Throwable;

use const LOCK_EX;

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
     * @internal
     * @return array{0: (ChannelledSocket|APIWrapper|Throwable|null|0), 1: (callable|null)}
     */
    public static function unserialize(SessionPaths $session, SettingsAbstract $settings, bool $forceFull = false): array
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
        $canContinue = true;
        $ipcSocket = null;
        $unlock = Tools::flock($session->getLockPath(), LOCK_EX, 1, $cancelFlock->getCancellation(), $forceFull ? null : static function () use ($session, &$cancelFlock, $cancelIpc, &$canContinue, &$ipcSocket, &$lightState): void {
            $cancelFull = static function () use (&$cancelFlock): void {
                if ($cancelFlock !== null) {
                    $copy = $cancelFlock;
                    $cancelFlock = null;
                    $copy->cancel();
                }
            };
            EventLoop::queue(static function () use ($session, $cancelFull, &$canContinue, &$lightState): void {
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
            $ipcSocket = self::tryConnect($session->getIpcPath(), $cancelIpc->getFuture(), $cancelFull);
        });
        EventLoop::cancel($warningId);

        if (!$unlock) { // Canceled, don't have lock
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
                $monitor = Server::startMe($session);
                EventLoop::queue(static function () use ($cancelIpc, $monitor): void {
                    try {
                        $cancelIpc->complete($monitor->await());
                    } catch (\Throwable $e) {
                        $cancelIpc->error($e);
                    }
                });
                return $ipcSocket ?? self::tryConnect($session->getIpcPath(), $cancelIpc->getFuture());
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
     * Try connecting to IPC socket.
     *
     * @param  string                                            $ipcPath       IPC path
     * @param  Future<(Throwable|null)>                          $cancelConnect Cancelation token (triggers cancellation of connection)
     * @param  null|callable(): void                             $cancelFull    Cancelation token source (can trigger cancellation of full unserialization)
     * @return array{0: (ChannelledSocket|Throwable|0), 1: null}
     */
    public static function tryConnect(string $ipcPath, Future $cancelConnect, ?callable $cancelFull = null, ?Throwable $customE = null): array
    {
        for ($x = 0; $x < 25; $x++) {
            Logger::log('MadelineProto is starting, please wait...');
            if (\PHP_OS_FAMILY === 'Windows') {
                Logger::log(Lang::$current_lang['windows_warning']);
            }
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
                if ($res = $cancelConnect->await(Tools::getTimeoutCancellation(1.0))) {
                    if ($res instanceof Throwable) {
                        return [$res, null];
                    }
                    $cancelConnect = (new DeferredFuture)->getFuture();
                }
            } catch (CancelledException $e) {
                if (!$e->getPrevious() instanceof TimeoutException) {
                    throw $e;
                }
            }
        }
        return [$customE ?? 0, null];
    }
}
