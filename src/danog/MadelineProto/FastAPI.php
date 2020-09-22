<?php

/**
 * API module.
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

use Amp\File\StatCache;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Promise;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\Server;

use function Amp\File\exists;
use function Amp\File\get;
use function Amp\File\isfile;
use function Amp\File\unlink;
use function Amp\Ipc\connect;

/**
 * IPC API wrapper for MadelineProto.
 */
class FastAPI extends API
{
    /**
     * Constructor function.
     *
     * @param string $session  Session name
     * @param array  $settings Settings
     *
     * @return void
     */
    public function __magic_construct(string $session, array $settings = []): void
    {
        Magic::classExists(true);
        $this->setInitPromise($this->__construct_async($session, $settings));
        foreach (\get_class_vars(APIFactory::class) as $key => $var) {
            if (\in_array($key, ['namespace', 'API', 'lua', 'async', 'asyncAPIPromise', 'methods'])) {
                continue;
            }
            if (!$this->{$key}) {
                $this->{$key} = $this->exportNamespace($key);
            }
        }
    }
    /**
     * Async constructor function.
     *
     * @param string $session  Session name
     * @param array  $settings Settings
     *
     * @return \Generator
     */
    public function __construct_async(string $session, array $settings = []): \Generator
    {
        $this->logger = Logger::constructorFromSettings($settings);
        $session = new SessionPaths($session);
        if (!$client = yield from $this->checkInit($session, $settings)) {
            try {
                yield unlink($session->getIpcPath());
            } catch (\Throwable $e) {
            }
            StatCache::clear($session->getIpcPath());
            Server::startMe($session);
            $inited = false;
            $this->logger->logger("Waiting for IPC server to start...");
            for ($x = 0; $x < 30; $x++) {
                yield Tools::sleep(1);
                StatCache::clear($session->getIpcPath());
                if ($client = yield from $this->checkInit($session, $settings)) {
                    $inited = true;
                    break;
                }
                Server::startMe($session);
            }
            if (!$client) {
                throw new Exception("The IPC server isn't running, please check logs!");
            }
        }
        $this->API = new Client($client, $this->logger);
        $this->methods = self::getInternalMethodList($this->API, MTProto::class);
        $this->logger->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
    }
    /**
     * Try initializing session.
     *
     * @param SessionPaths $session  Session paths
     * @param array        $settings Settings
     *
     * @return \Generator
     */
    private function checkInit(SessionPaths $session, array $settings): \Generator
    {
        StatCache::clear($session->getIpcPath());
        StatCache::clear($session->getSessionPath());
        if (!(yield exists($session->getSessionPath()))
            || (yield exists($session->getIpcPath())
            && yield isfile($session->getIpcPath())
            && yield get($session->getIpcPath()) === Server::NOT_INITED)
        ) { // Should init API ID|session
            Logger::log("Session not initialized, initializing it now...");
            $API = new API($session->getSessionPath(), $settings);
            yield from $API->initAsynchronously();
            unset($API);
            Logger::log("Destroying temporary MadelineProto...");
            while (\gc_collect_cycles());
            Logger::log("Destroyed temporary MadelineProto!");
            return null; // Should start IPC server
        }
        return yield from $this->tryConnect($session->getIpcPath());
    }
    /**
     * Try connecting to IPC socket.
     *
     * @param string $ipcPath IPC path
     *
     * @return \Generator<int, Promise<ChannelledSocket>, mixed, ChannelledSocket|null>
     */
    private function tryConnect(string $ipcPath): \Generator
    {
        Logger::log("Trying to connect to IPC socket...");
        try {
            \clearstatcache(true, $ipcPath);
            return yield connect($ipcPath);
        } catch (\Throwable $e) {
            $e = $e->getMessage();
            Logger::log("$e while connecting to IPC socket");
            return null;
        }
    }
    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     *
     * @return void
     */
    public function startAndLoop(string $eventHandler): void
    {
        throw new Exception("Can't use ".__FUNCTION__." in an IPC client instance, please use a full ".API::class." instance, instead!");
    }
    /**
     * Start multiple instances of MadelineProto and the event handlers (enables async).
     *
     * @param API[]           $instances    Instances of madeline
     * @param string[]|string $eventHandler Event handler(s)
     *
     * @return Promise
     */
    public static function startAndLoopMulti(array $instances, $eventHandler): void
    {
        throw new Exception("Can't use ".__FUNCTION__." in an IPC client instance, please use a full ".API::class." instance, instead!");
    }
}
