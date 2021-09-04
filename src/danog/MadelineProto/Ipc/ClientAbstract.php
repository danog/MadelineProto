<?php
/**
 * API wrapper module.
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

namespace danog\MadelineProto\Ipc;

use Amp\Deferred;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Promise;
use danog\MadelineProto\Logger;

use function Amp\Ipc\connect;

/**
 * IPC client.
 */
abstract class ClientAbstract
{
    /**
     * IPC server socket.
     */
    protected ChannelledSocket $server;
    /**
     * Requests promise array.
     *
     * @var Deferred[]
     */
    private array $requests = [];
    /**
     * Wrappers array.
     *
     * @var Wrapper[]
     */
    private array $wrappers = [];
    /**
     * Whether to run loop.
     */
    protected bool $run = true;
    /**
     * Logger instance.
     */
    public Logger $logger;

    protected function __construct()
    {
    }
    /**
     * Logger.
     *
     * @param string $param Parameter
     * @param int    $level Logging level
     * @param string $file  File where the message originated
     *
     * @return void
     */
    public function logger($param, int $level = Logger::NOTICE, string $file = ''): void
    {
        if ($file === null) {
            $file = \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        isset($this->logger) ? $this->logger->logger($param, $level, $file) : Logger::$default->logger($param, $level, $file);
    }
    /**
     * Main loop.
     *
     * @return \Generator
     */
    protected function loopInternal(): \Generator
    {
        do {
            while (true) {
                $payload = null;
                try {
                    $payload = yield $this->server->receive();
                } catch (\Throwable $e) {
                    Logger::log("Got exception while receiving in IPC client: $e");
                }
                if (!$payload) {
                    break;
                }
                [$id, $payload] = $payload;
                if (!isset($this->requests[$id])) {
                    Logger::log("Got response for non-existing ID $id!");
                } else {
                    $promise = $this->requests[$id];
                    unset($this->requests[$id]);
                    if (isset($this->wrappers[$id])) {
                        yield $this->wrappers[$id]->disconnect();
                        unset($this->wrappers[$id]);
                    }
                    if ($payload instanceof ExitFailure) {
                        $promise->fail($payload->getException());
                    } else {
                        $promise->resolve($payload);
                    }
                    unset($promise);
                }
            }
            if ($this->run) {
                $this->logger("Reconnecting to IPC server!");
                try {
                    yield $this->server->disconnect();
                } catch (\Throwable $e) {
                }
                if ($this instanceof Client) {
                    try {
                        yield Server::startMe($this->session);
                        $this->server = yield connect($this->session->getIpcPath());
                    } catch (\Throwable $e) {
                        Logger::log("Got exception while reconnecting in IPC client: $e");
                    }
                } else {
                    return;
                }
            }
        } while ($this->run);
    }
    /**
     * Disconnect cleanly from main instance.
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, Promise, mixed, void>
     */
    public function disconnect(): \Generator
    {
        $this->run = false;
        yield $this->server->disconnect();
        foreach ($this->wrappers as $w) {
            yield from $w->disconnect();
        }
    }
    /**
     * Call function.
     *
     * @param string|int    $function  Function name
     * @param array|Wrapper $arguments Arguments
     *
     * @return \Generator
     */
    public function __call($function, $arguments): \Generator
    {
        $this->requests []= $deferred = new Deferred;
        if ($arguments instanceof Wrapper) {
            $this->wrappers[\count($this->requests) - 1] = $arguments;
        }
        yield $this->server->send([$function, $arguments]);
        return yield $deferred->promise();
    }
}
