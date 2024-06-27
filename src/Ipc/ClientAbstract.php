<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Ipc;

use Amp\DeferredFuture;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\Logger;
use Throwable;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

use function Amp\Ipc\connect;

/**
 * IPC client.
 *
 * @internal
 */
abstract class ClientAbstract
{
    /**
     * IPC server socket.
     */
    protected ChannelledSocket $server;
    private int $id = 0;
    /**
     * Requests promise array.
     *
     * @var array<int, list{string|int, array|Wrapper, DeferredFuture}>
     */
    private array $requests = [];
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
     * @param mixed  $param Parameter
     * @param int    $level Logging level
     * @param string $file  File where the message originated
     */
    public function logger(mixed $param, int $level = Logger::NOTICE, string $file = ''): void
    {
        if ($file === '') {
            $file = basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        isset($this->logger) ? $this->logger->logger($param, $level, $file) : Logger::$default->logger($param, $level, $file);
    }
    /**
     * Main loop.
     */
    protected function loopInternal(): void
    {
        do {
            while (true) {
                $payload = null;
                try {
                    $payload = $this->server->receive();
                } catch (Throwable $e) {
                    Logger::log("Got exception while receiving in IPC client: $e");
                }
                if (!$payload) {
                    break;
                }
                [$id, $payload] = $payload;
                if (!isset($this->requests[$id])) {
                    Logger::log("Got response for non-existing ID $id!");
                } else {
                    $promise = $this->requests[$id][2];
                    unset($this->requests[$id]);
                    if ($payload instanceof ExitFailure) {
                        $promise->error($payload->getException());
                    } else {
                        $promise->complete($payload);
                    }
                    unset($promise);
                }
            }
            if ($this->run) {
                $this->logger('Reconnecting to IPC server!');
                try {
                    $this->server->disconnect();
                } catch (Throwable $e) {
                }
                if ($this instanceof Client) {
                    try {
                        Server::startMe($this->session)->await();
                        $this->server = connect($this->session->getIpcPath());
                        $requests = $this->requests;
                        $this->requests = [];
                        $this->id = 0;
                        foreach ($requests as [$function, $arguments, $deferred]) {
                            $id = $this->id++;
                            $this->requests[$id] = [$function, $arguments, $deferred];
                            $this->server->send([$function, $arguments]);
                        }
                    } catch (Throwable $e) {
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
     */
    public function disconnect(): void
    {
        $this->run = false;
        $this->server->disconnect();
        foreach ($this->requests as [, $args, $promise]) {
            if ($args instanceof Wrapper) {
                $args->disconnect();
            }
        }
    }
    /**
     * Call function.
     *
     * @param string|int    $function  Function name
     * @param array|Wrapper $arguments Arguments
     */
    public function __call(string|int $function, array|Wrapper $arguments)
    {
        $deferred = new DeferredFuture;
        $id = $this->id++;
        $this->requests[$id] = [$function, $arguments, $deferred];
        $this->server->send([$function, $arguments]);
        $result = $deferred->getFuture()->await();
        if ($result instanceof ExitFailure) {
            throw $result->getException();
        }
        return $result;
    }
}
