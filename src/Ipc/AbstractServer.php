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

use Amp\CompositeException;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\BetterPrometheus\BetterGauge;
use danog\Loop\Loop;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Runner\ProcessRunner;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Shutdown;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function Amp\delay;

/**
 * IPC server.
 *
 * @internal
 */
abstract class AbstractServer extends Loop
{
    use InternalLoop {
        __construct as private internalInit;
    }
    private ?BetterGauge $connectionGauge;
    public function __construct(MTProto $API)
    {
        $this->internalInit($API);
        $this->connectionGauge = $API->getPromGauge("MadelineProto", "ipc_server_connections", "Number of IPC server connections");
        $this->connectionGauge?->set(0);
    }
    /**
     * Server version.
     */
    public const VERSION = 1;
    /**
     * Shutdown server.
     */
    public const SHUTDOWN = 0;
    /**
     * Boolean to shut down worker, if started.
     */
    private static bool $shutdown = false;
    /**
     * Deferred to shut down worker, if started.
     */
    private static ?DeferredFuture $shutdownDeferred = null;
    /**
     * Boolean whether to shut down worker, if started.
     */
    private static bool $shutdownNow = false;
    /**
     * IPC server.
     */
    protected IpcServer $server;
    /**
     * Callback IPC server.
     */
    private ServerCallback $callback;
    /**
     * Set IPC path.
     *
     * @param SessionPaths $session Session
     */
    public function setIpcPath(SessionPaths $session): void
    {
        self::$shutdownDeferred ??= new DeferredFuture;
        $this->server = new IpcServer($session->getIpcPath());
        $this->callback = new ServerCallback($this->API);
        $this->callback->setIpcPath($session);
    }
    public function start(): bool
    {
        return $this instanceof ServerCallback ? parent::start() : $this->callback->start() && parent::start();
    }
    /**
     * Start IPC server in background.
     *
     * @param SessionPaths $session Session path
     */
    public static function startMe(SessionPaths $session): Future
    {
        $id = Tools::randomInt(2000000000);
        $started = false;
        $e = null;
        try {
            Logger::log("Starting IPC server $session (process)");
            ProcessRunner::start((string) $session, $id);
            $started = true;
            WebRunner::start((string) $session, $id);
            return async(self::monitor(...), $session, $id, $started, null);
        } catch (Throwable $e) {
            Logger::log($e);
        }
        try {
            Logger::log("Starting IPC server $session (web)");
            if (WebRunner::start((string) $session, $id)) {
                $started = true;
            }
        } catch (Throwable $e2) {
            Logger::log($e2);
            if ($e) {
                $e = new CompositeException([$e, $e2]);
            } else {
                $e = $e2;
            }
        }
        return async(self::monitor(...), $session, $id, $started, $e);
    }
    /**
     * Monitor session.
     */
    private static function monitor(SessionPaths $session, int $id, bool $started, ?\Throwable $e): bool|Throwable
    {
        if (!$started) {
            Logger::log("It looks like the server couldn't be started, trying to connect anyway...");
        }
        $count = 0;
        while (true) {
            $state = $session->getIpcState();
            if ($state && $state->getStartupId() === $id) {
                if ($e = $state->getException()) {
                    Logger::log("IPC server got exception $e");
                    return $e;
                }
                Logger::log('IPC server started successfully!');
                return true;
            } elseif (!$started && $count > 0 && $count > 2*($state ? 3 : 1)) {
                return new Exception("We couldn't start the IPC server, please check the logs!", previous: $e);
            }
            delay(0.5);
            $count++;
        }
        return false;
    }
    /**
     * Wait for shutdown.
     */
    public static function waitShutdown(): void
    {
        if (self::$shutdownNow) {
            return;
        }
        self::$shutdownDeferred ??= new DeferredFuture;
        self::$shutdownDeferred->getFuture()->await();
    }
    /**
     * Shutdown.
     */
    final public function stop(): bool
    {
        $this->server->close();
        if (!$this instanceof ServerCallback) {
            $this->callback->server->close();
        }
        if (self::$shutdownDeferred) {
            self::$shutdownNow = true;
            $deferred = self::$shutdownDeferred;
            self::$shutdownDeferred = null;
            $deferred->complete();
        }
        return true;
    }
    /**
     * Main loop.
     */
    protected function loop(): ?float
    {
        while ($socket = $this->server->accept()) {
            EventLoop::queue($this->clientLoop(...), $socket);
        }
        $this->server->close();
        if (isset($this->callback)) {
            $this->callback->server->close();
        }
        return self::STOP;
    }
    /**
     * Client handler loop.
     *
     * @param ChannelledSocket $socket Client
     */
    protected function clientLoop(ChannelledSocket $socket): void
    {
        $this->API->waitForInit();
        $this->API->logger('Accepted IPC client connection!');
        $this->connectionGauge?->inc();

        $id = 0;
        $payload = null;
        try {
            while ($payload = $socket->receive()) {
                EventLoop::queue($this->clientRequest(...), $socket, $id++, $payload);
            }
        } catch (Throwable $e) {
            Logger::log("Exception in IPC connection: $e");
        } finally {
            $this->connectionGauge?->dec();
            EventLoop::queue(function () use ($socket, $payload): void {
                try {
                    $socket->disconnect();
                } catch (Throwable $e) {
                    Logger::log("Exception during shutdown in IPC connection: $e");
                }
                if ($payload === self::SHUTDOWN) {
                    Shutdown::removeCallback('restarter');
                    $this->stop();
                }
            });
        }
    }
    /**
     * Handle client request.
     *
     * @param ChannelledSocket                   $socket  Socket
     * @param array{0: string, 1: array|Wrapper} $payload Payload
     */
    private function clientRequest(ChannelledSocket $socket, int $id, array $payload): void
    {
        try {
            if ($payload[1] instanceof Wrapper) {
                $wrapper = $payload[1];
                $payload[1] = $this->callback->unwrap($wrapper);
            }
            $result = $this->API->{$payload[0]}(...$payload[1]);
        } catch (Throwable $e) {
            $this->API->logger("Got error while calling IPC method: $e", Logger::ERROR);
            $result = new ExitFailure($e);
        } finally {
            if (isset($wrapper)) {
                EventLoop::queue(static function () use ($wrapper): void {
                    try {
                        $wrapper->disconnect();
                    } catch (Throwable $e) {
                        Logger::log("Exception during shutdown in IPC connection: $e");
                    }
                });
            }
        }
        try {
            $socket->send([$id, $result]);
        } catch (Throwable $e) {
            $this->API->logger("Got error while trying to send result of {$payload[0]}: $e", Logger::ERROR);
            try {
                $socket->send([$id, new ExitFailure($e)]);
            } catch (Throwable $e) {
                $this->API->logger("Got error while trying to send error of error of {$payload[0]}: $e", Logger::ERROR);
            }
        }
    }
    /**
     * Get the name of the loop.
     */
    public function __toString(): string
    {
        return 'IPC server';
    }
}
