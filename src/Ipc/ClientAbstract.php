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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Ipc;

use Amp\DeferredFuture;
use Amp\Future;
use Amp\Ipc\Sync\ChannelException;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Serialization;
use Throwable;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

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
    protected ?Future $serverFuture = null;

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

    /**
     * @psalm-mutation-free
     */
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
                    Logger::log("Disconnected from IPC server!");
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
                if ($this instanceof Client) {
                    $this->logger('Reconnecting to IPC server!');
                    $f = new DeferredFuture;
                    $this->serverFuture = $f->getFuture();
                    try {
                        $this->server->disconnect();
                    } catch (Throwable $e) {
                    }
                    try {
                        // Connect as soon as any server is listening, even if it was started by another process
                        [$server] = Serialization::tryConnect($this->session->getIpcPath(), Server::startMe($this->session));
                        if (!$server instanceof ChannelledSocket) {
                            throw $server instanceof Throwable ? $server : new ChannelException("Could not reconnect to the IPC server, please check the logs!");
                        }
                        $this->server = $server;
                        $this->logger('Reconnected to IPC server!');
                        $requests = $this->requests;
                        $this->requests = [];
                        $this->id = 0;
                        foreach ($requests as [$function, $arguments, $deferred]) {
                            $id = $this->id++;
                            $this->requests[$id] = [$function, $arguments, $deferred];
                            $this->server->send([$function, $arguments]);
                        }
                        $f->complete();
                        $this->serverFuture = null;
                        $this->logger('Resumed IPC queries!');
                    } catch (Throwable $e) {
                        $this->logger("Could not reconnect to IPC server: $e", Logger::FATAL_ERROR);
                        // Fail future calls, and pending calls below, instead of leaving them hanging
                        $f->getFuture()->ignore();
                        $f->error($e);
                        $this->run = false;
                    }
                } else {
                    try {
                        $this->server->disconnect();
                    } catch (Throwable $e) {
                    }
                    break;
                }
            }
        } while ($this->run);
        $requests = $this->requests;
        $this->requests = [];
        foreach ($requests as [$function, , $deferred]) {
            if (!$this->run && $this instanceof Wrapper) {
                // Callback wrappers are closed once their IPC call returns: calls still pending at that point
                // (late progress notifications, cancellation waits) belong to nobody, so they're dropped.
                $deferred->complete(null);
            } else {
                $deferred->error(new ChannelException("Disconnected from IPC server while calling $function!"));
            }
        }
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
        if (!$this->run && $this instanceof Wrapper) {
            // See loopInternal().
            return null;
        }
        $this->serverFuture?->await();

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
