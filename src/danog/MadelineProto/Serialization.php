<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Loop;
use Amp\Promise;
use danog\MadelineProto\Db\DbPropertiesFactory;
use danog\MadelineProto\Db\DriverArray;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\MTProtoSession\Session;
use danog\MadelineProto\Settings\DatabaseAbstract;

use function Amp\File\exists;
use function Amp\File\get;
use function Amp\Ipc\connect;

/**
 * Manages serialization of the MadelineProto instance.
 */
abstract class Serialization
{
    /**
     * Header for session files.
     */
    const PHP_HEADER = '<?php __HALT_COMPILER();';
    /**
     * Serialization version.
     */
    const VERSION = 1;

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
     *
     * @internal
     *
     * @return \Generator
     *
     * @psalm-return \Generator<void, mixed, mixed, array{0: ChannelledSocket|APIWrapper|\Throwable|null|0, 1: callable|null}>
     */
    public static function unserialize(SessionPaths $session, SettingsAbstract $settings, bool $forceFull = false): \Generator
    {
        if (yield exists($session->getSessionPath())) {
            // Is new session
            $isNew = true;
        } elseif (yield exists($session->getLegacySessionPath())) {
            // Is old session
            $isNew = false;
        } else {
            // No session exists yet, lock for when we create it
            return [null, yield from Tools::flockGenerator($session->getLockPath(), LOCK_EX, 1)];
        }

        //Logger::log('Waiting for exclusive session lock...');
        $warningId = Loop::delay(1000, static function () use (&$warningId) {
            Logger::log("It seems like the session is busy.");
            /*if (\defined(\MADELINE_WORKER::class)) {
                Logger::log("Exiting since we're in a worker");
                Magic::shutdown(1);
            }*/
            Logger::log("Telegram does not support starting multiple instances of the same session, make sure no other instance of the session is running.");
            $warningId = Loop::repeat(5000, fn () => Logger::log('Still waiting for exclusive session lock...'));
            Loop::unreference($warningId);
        });
        Loop::unreference($warningId);

        $lightState = null;
        $cancelFlock = new Deferred;
        $cancelIpc = new Deferred;
        $canContinue = true;
        $ipcSocket = null;
        $unlock = yield from Tools::flockGenerator($session->getLockPath(), LOCK_EX, 1, $cancelFlock->promise(), $forceFull ? null : static function () use ($session, $cancelFlock, $cancelIpc, &$canContinue, &$ipcSocket, &$lightState) {
            $cancelFull = static function () use (&$cancelFlock): void {
                if ($cancelFlock !== null) {
                    $copy = $cancelFlock;
                    $cancelFlock = null;
                    $copy->resolve(true);
                }
            };
            $ipcSocket = Tools::call(self::tryConnect($session->getIpcPath(), $cancelIpc->promise(), $cancelFull));
            $session->getLightState()->onResolve(static function (?\Throwable $e, ?LightState $res) use ($cancelFull, &$canContinue, &$lightState) {
                if ($res) {
                    $lightState = $res;
                    if (!$res->canStartIpc()) {
                        $canContinue = false;
                        $cancelFull();
                    }
                } else {
                    $lightState = false;
                }
            });
        });
        Loop::cancel($warningId);

        if (!$unlock) { // Canceled, don't have lock
            return $ipcSocket;
        }
        if (!$canContinue) { // Have lock, can't use it
            Logger::log("Session has event handler, but it's not started.", Logger::ERROR);
            Logger::log("We don't have access to the event handler class, so we can't start it.", Logger::ERROR);
            Logger::log("Please start the event handler or unset it to use the IPC server.", Logger::ERROR);
            $unlock();
            return $ipcSocket;
        }

        try {
            /** @var LightState */
            $lightState ??= yield $session->getLightState();
        } catch (\Throwable $e) {
        }

        if ($lightState && !$forceFull) {
            if (!$class = $lightState->getEventHandler()) {
                // Unlock and fork
                $unlock();
                $cancelIpc->resolve(Server::startMe($session));
                return $ipcSocket ?? yield from self::tryConnect($session->getIpcPath(), $cancelIpc->promise());
            } elseif (!\class_exists($class)) {
                // Have lock, can't use it
                $unlock();
                Logger::log("Session has event handler, but it's not started.", Logger::ERROR);
                Logger::log("We don't have access to the event handler class, so we can't start it.", Logger::ERROR);
                Logger::log("Please start the event handler or unset it to use the IPC server.", Logger::ERROR);
                return $ipcSocket ?? yield from self::tryConnect($session->getIpcPath(), $cancelIpc->promise());
            }
        }

        $tempId = Shutdown::addCallback($unlock = static function () use ($unlock) {
            Logger::log("Unlocking exclusive session lock!");
            $unlock();
            Logger::log("Unlocked exclusive session lock!");
        });
        Logger::log("Got exclusive session lock!");

        if ($isNew) {
            $unserialized = yield from $session->unserialize();
            if ($unserialized instanceof DriverArray) {
                Logger::log("Extracting session from database...");
                if ($settings instanceof Settings) {
                    $settings = $settings->getDb();
                }
                if ($settings instanceof DatabaseAbstract) {
                    $tableName = (string) $unserialized;
                    $unserialized = yield DbPropertiesFactory::get(
                        $settings,
                        $tableName,
                        DbPropertiesFactory::TYPE_ARRAY,
                        $unserialized
                    );
                } else {
                    yield from $unserialized->initStartup();
                }
                $unserialized = yield $unserialized['data'];
                if (!$unserialized) {
                    throw new Exception("Could not extract session from database!");
                }
            }
        } else {
            $unserialized = yield from self::legacyUnserialize($session->getLegacySessionPath());
        }

        if ($unserialized === false) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['deserialization_error']);
        }

        Shutdown::removeCallback($tempId);
        return [$unserialized, $unlock];
    }

    /**
     * Try connecting to IPC socket.
     *
     * @param string    $ipcPath       IPC path
     * @param Promise   $cancelConnect Cancelation token (triggers cancellation of connection)
     * @param ?callable(): void $cancelFull    Cancelation token source (can trigger cancellation of full unserialization)
     *
     * @psalm-param Promise<\Throwable|null> $cancelConnect
     *
     * @return \Generator
     * @psalm-return \Generator<mixed, mixed, mixed, array{0: ChannelledSocket|\Throwable|0, 1: null}>
     */
    public static function tryConnect(string $ipcPath, Promise $cancelConnect, ?callable $cancelFull = null): \Generator
    {
        for ($x = 0; $x < 60; $x++) {
            Logger::log("MadelineProto is starting, please wait...");
            try {
                \clearstatcache(true, $ipcPath);
                $socket = yield connect($ipcPath);
                Logger::log("Connected to IPC socket!");
                if ($cancelFull) {
                    $cancelFull();
                }
                return [$socket, null];
            } catch (\Throwable $e) {
                $e = $e->getMessage();
                if ($e !== 'The endpoint does not exist!') {
                    Logger::log("$e while connecting to IPC socket");
                }
            }
            if ($res = yield Tools::timeoutWithDefault($cancelConnect, 1000, null)) {
                if ($res instanceof \Throwable) {
                    return [$res, null];
                }
                $cancelConnect = (new Deferred)->promise();
            }
        }
        return [0, null];
    }

    /**
     * Deserialize legacy session.
     *
     * @param string $session
     * @return \Generator
     */
    private static function legacyUnserialize(string $session): \Generator
    {
        $tounserialize = yield get($session);

        try {
            $unserialized = \unserialize($tounserialize);
        } catch (\danog\MadelineProto\Bug74586Exception $e) {
            \class_exists('\\Volatile');
            $tounserialize = \str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
            foreach (['RSA', 'TL\\TLMethods', 'TL\\TLConstructors', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                \class_exists('\\danog\\MadelineProto\\'.$class);
            }
            $unserialized = \danog\Serialization::unserialize($tounserialize);
        } catch (\danog\MadelineProto\Exception $e) {
            if ($e->getFile() === 'MadelineProto' && $e->getLine() === 1) {
                throw $e;
            }
            if (\defined('MADELINEPROTO_TEST') && \constant("MADELINEPROTO_TEST") === 'pony') {
                throw $e;
            }
            \class_exists('\\Volatile');
            foreach (['RSA', 'TL\\TLMethods', 'TL\\TLConstructors', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                \class_exists('\\danog\\MadelineProto\\'.$class);
            }
            $changed = false;
            if (\strpos($tounserialize, 'O:26:"danog\\MadelineProto\\Button":') !== false) {
                Logger::log("SUBBING BUTTONS!");
                $tounserialize = \str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
                $changed = true;
            }
            if (\strpos($e->getMessage(), "Erroneous data format for unserializing 'phpseclib\\Math\\BigInteger'") === 0) {
                Logger::log("SUBBING BIGINTEGOR!");
                $tounserialize = \str_replace('phpseclib\\Math\\BigInteger', 'phpseclib\\Math\\BigIntegor', $tounserialize);
                $changed = true;
            }
            if (\strpos($tounserialize, 'C:25:"phpseclib\\Math\\BigInteger"') !== false) {
                Logger::log("SUBBING TGSECLIB old!");
                $tounserialize = \str_replace('C:25:"phpseclib\\Math\\BigInteger"', 'C:26:"phpseclib3\\Math\\BigInteger"', $tounserialize);
                $changed = true;
            }
            if (\strpos($tounserialize, 'C:24:"tgseclib\\Math\\BigInteger"') !== false) {
                Logger::log("SUBBING TGSECLIB!");
                $tounserialize = \str_replace('C:24:"tgseclib\\Math\\BigInteger"', 'C:26:"phpseclib3\\Math\\BigInteger"', $tounserialize);
                $changed = true;
            }
            Logger::log((string) $e, Logger::ERROR);
            if (!$changed) {
                throw $e;
            }
            try {
                $unserialized = \danog\Serialization::unserialize($tounserialize);
            } catch (\Throwable $e) {
                $unserialized = \unserialize($tounserialize);
            }
        } catch (\Throwable $e) {
            Logger::log((string) $e, Logger::ERROR);
            throw $e;
        }
        if ($unserialized instanceof \danog\PlaceHolder) {
            $unserialized = \danog\Serialization::unserialize($tounserialize);
        }

        return $unserialized;
    }
}
