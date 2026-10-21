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

use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Ipc\Sync\ChannelException;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Serialization;
use Throwable;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

use function Amp\delay;

/**
 * IPC client.
 *
 * @internal
 */
abstract class ClientAbstract
{
    /**
     * Delay before the second reconnection in a row that didn't get any answer, in seconds: it doubles with each
     * further one, up to MAX_RECONNECT_DELAY.
     */
    private const RECONNECT_DELAY = 1.0;
    /**
     * Maximum delay between reconnections, in seconds.
     */
    private const MAX_RECONNECT_DELAY = 30.0;
    /**
     * An idle connection that lasted this long (in seconds) was a working one: losing it doesn't delay reconnecting.
     */
    private const STABLE_CONNECTION = 10.0;
    /**
     * Number of connections a call is sent on without getting an answer before giving up.
     */
    private const MAX_SENDS = 5;
    /**
     * IPC server socket.
     */
    protected ChannelledSocket $server;
    protected ?Future $serverFuture = null;

    private int $id = 0;
    /**
     * Requests promise array.
     *
     * @var array<int, list{string|int, array|Wrapper, DeferredFuture, int}> Function, arguments, result, number of connections it was sent on
     */
    private array $requests = [];
    /**
     * Cancels the reconnection in progress, if any.
     */
    private ?DeferredCancellation $reconnecting = null;
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
        // Reconnections in a row that didn't get any answer
        $failures = 0;
        $connectedAt = microtime(true);
        $failure = null;
        do {
            $answered = false;
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
                $answered = true;
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
                    // A server that answered, or an idle connection that lasted, was a working one. Otherwise the
                    // server dropped us (it's shutting down with its socket still bound, it fails to handle our
                    // calls, ...): reconnecting right away would spin on it, so wait longer and longer.
                    if ($answered || (!$this->requests && microtime(true) - $connectedAt >= self::STABLE_CONNECTION)) {
                        $failures = 0;
                    } else {
                        $failures++;
                    }
                    $requests = [];
                    $this->reconnecting = new DeferredCancellation;
                    try {
                        $cancellation = $this->reconnecting->getCancellation();
                        // Calls sent on several connections in a row, each dropped before answering them, would be
                        // sent again forever.
                        $calls = [];
                        foreach ($this->requests as [$function, , , $sends]) {
                            if ($sends >= self::MAX_SENDS) {
                                $calls[] = $function;
                            }
                        }
                        if ($calls) {
                            throw new ChannelException("The IPC server of {$this->session} dropped the connection ".self::MAX_SENDS." times in a row without answering ".implode(', ', array_unique($calls)).", giving up: please check the logs of the IPC server (or of the instance running the session)!");
                        }
                        if ($failures > 1) {
                            $wait = min(self::RECONNECT_DELAY * 2 ** ($failures - 2), self::MAX_RECONNECT_DELAY);
                            $this->logger("The IPC server dropped the connection without answering, reconnecting in $wait seconds...", Logger::WARNING);
                            delay($wait, true, $cancellation);
                        }
                        // Connects to the server of the session if there's one (or an instance loading it), only
                        // starting one if there's none.
                        [$server] = Serialization::reconnect($this->session, $cancellation);
                        if (!$server instanceof ChannelledSocket) {
                            throw $server instanceof Throwable ? $server : new ChannelException("Could not reconnect to the IPC server, please check the logs!");
                        }
                        $this->server = $server;
                        $connectedAt = microtime(true);
                        $this->logger('Reconnected to IPC server!');
                        $requests = $this->requests;
                        $this->requests = [];
                        $this->id = 0;
                        while ($requests) {
                            [$function, $arguments, $deferred, $sends] = array_shift($requests);
                            if ($arguments instanceof Wrapper) {
                                // Its callbacks were connected to the previous server, which can't call them anymore.
                                // (Interrupted uploads and sends are resumed by the server on its own.)
                                $arguments->disconnect();
                                $deferred->error(new ChannelException("Disconnected from IPC server while calling $function!"));
                                continue;
                            }
                            $id = $this->id++;
                            $this->requests[$id] = [$function, $arguments, $deferred, $sends + 1];
                            try {
                                $this->server->send([$function, $arguments]);
                            } catch (Throwable $e) {
                                // Dropped already: the calls are sent again on the next connection (or failed)
                                $this->logger("Could not send $function to the IPC server: $e");
                                foreach ($requests as $request) {
                                    $this->requests[$this->id++] = $request;
                                }
                                break;
                            }
                        }
                        $f->complete();
                        $this->serverFuture = null;
                        $this->logger('Resumed IPC queries!');
                    } catch (Throwable $e) {
                        if ($e instanceof CancelledException && !$this->run) {
                            $e = new ChannelException("Disconnected from the IPC server while reconnecting to it");
                        } else {
                            $this->logger("Could not reconnect to IPC server: $e", Logger::FATAL_ERROR);
                        }
                        $failure = $e;
                        // The requests that were not sent again yet are failed below, too
                        /** @var array<int, list{string|int, array|Wrapper, DeferredFuture, int}> $requests */
                        foreach ($requests as $request) {
                            $this->requests[] = $request;
                        }
                        // Fail future calls, and pending calls below, instead of leaving them hanging
                        $f->getFuture()->ignore();
                        $f->error($e);
                        $this->run = false;
                    } finally {
                        $this->reconnecting = null;
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
                $deferred->error(new ChannelException("Disconnected from IPC server while calling $function!", previous: $failure));
            }
        }
    }
    /**
     * Disconnect cleanly from main instance.
     */
    public function disconnect(): void
    {
        $this->run = false;
        $this->reconnecting?->cancel();
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
        $this->requests[$id] = [$function, $arguments, $deferred, 1];
        try {
            $this->server->send([$function, $arguments]);
        } catch (Throwable $e) {
            // The connection is lost: the main loop sends the call again once reconnected, or fails it.
            // (Throwing here left the call pending, its result unhandled.)
            $this->logger("Could not send $function to the IPC server: $e");
        }
        $result = $deferred->getFuture()->await();
        if ($result instanceof ExitFailure) {
            throw $result->getException();
        }
        return $result;
    }
}
