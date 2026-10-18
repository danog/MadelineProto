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

use Amp\CompositeException;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\BetterPrometheus\BetterGauge;
use danog\Loop\Loop;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Remote\EncryptedChannel;
use danog\MadelineProto\Ipc\Remote\Handshake;
use danog\MadelineProto\Ipc\Remote\Listener;
use danog\MadelineProto\Ipc\Runner\ProcessRunner;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Serialization;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Shutdown;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function Amp\delay;
use function Amp\Ipc\connect;

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
     * Maximum time to wait for a started IPC server to report its state, in seconds.
     */
    private const STARTUP_TIMEOUT = 30;
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
     * Listener of remote IPC connections.
     */
    private ?Listener $listener = null;
    /**
     * Secret of the listener of remote IPC connections.
     */
    private ?string $listenerSecret = null;
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
    #[\Override]
    public function start(): bool
    {
        return $this instanceof ServerCallback ? parent::start() : $this->callback->start() && parent::start();
    }
    /**
     * Start, restart or stop accepting remote IPC connections, according to the settings.
     *
     * Not done by start(): the IPC server is started before the settings passed to the constructor are applied.
     */
    public function configureRemote(Ipc $settings): void
    {
        \assert(!$this instanceof ServerCallback);
        $endpoints = $settings->getListen();
        $secret = $settings->getSecret();
        $old = $this->listener;
        $oldSecret = $this->listenerSecret;
        if (($old?->endpoints ?? []) === $endpoints && $oldSecret === $secret) {
            return;
        }
        if ($endpoints && $secret === null) {
            throw new Exception('An IPC secret is required to accept remote IPC connections!');
        }
        // Before suspending (stopping an HTTP server does), so that concurrent calls don't close it again.
        $this->listener = null;
        $this->listenerSecret = null;
        // Clients authenticated with a secret that was changed lose access.
        $old?->close(disconnect: $oldSecret !== $secret);
        if (!$endpoints) {
            return;
        }
        try {
            $this->listener = new Listener($secret, $endpoints, $this->acceptRemote(...), $this->API->getPsrLogger());
            $this->listenerSecret = $secret;
        } catch (Throwable $e) {
            // Keep accepting connections where it worked, unless the secret was changed (maybe because it leaked).
            if ($old !== null && $oldSecret !== null && $oldSecret === $secret) {
                try {
                    $this->listener = new Listener($oldSecret, $old->endpoints, $this->acceptRemote(...), $this->API->getPsrLogger());
                    $this->listenerSecret = $oldSecret;
                } catch (Throwable $e2) {
                    Logger::log("Could not accept remote IPC connections on the previous endpoints, either: $e2", Logger::ERROR);
                }
            }
            throw $e;
        }
    }
    /**
     * Get the endpoints and secret on which remote IPC connections are currently accepted.
     *
     * @return array{list<string>, ?string}
     */
    public function getRemoteConfiguration(): array
    {
        return [$this->listener?->endpoints ?? [], $this->listenerSecret];
    }
    /**
     * Handle an authenticated remote IPC connection.
     *
     * @param Handshake::TYPE_* $type
     */
    private function acceptRemote(EncryptedChannel $channel, int $type): void
    {
        if ($type === Handshake::TYPE_CALLBACK) {
            $this->callback->clientLoop($channel);
        } else {
            $this->clientLoop($channel);
        }
    }
    /**
     * Start IPC server in background.
     *
     * @param SessionPaths $session Session path
     *
     * @return Future<bool|Throwable>
     */
    public static function startMe(SessionPaths $session): Future
    {
        $id = Tools::randomInt(2000000000);
        $startTime = microtime(true);
        $started = false;
        $e = null;
        try {
            Logger::log("Starting IPC server $session (process)");
            ProcessRunner::start((string) $session, $id);
            $started = true;
            WebRunner::start((string) $session, $id);
            return async(self::monitor(...), $session, $id, $startTime, $started, null);
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
        return async(self::monitor(...), $session, $id, $startTime, $started, $e);
    }
    /**
     * Monitor session.
     *
     * @param float $startTime When the server was started, to recognize servers started concurrently by other processes
     */
    private static function monitor(SessionPaths $session, int $id, float $startTime, bool $started, ?\Throwable $e): bool|Throwable
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
            } elseif ($state && $state->getStartupTime() >= $startTime && !$state->getException()) {
                // Another process started the server concurrently, overwriting our startup ID
                Logger::log('IPC server started successfully by another process!');
                return true;
            } elseif (!$started && $count > 0 && $count > 2*($state ? 3 : 1)) {
                return new Exception("We couldn't start the IPC server, please check the logs!", previous: $e);
            } elseif ($count > 0 && $count % 2 === 0 && self::isListening($session)) {
                // Served by a process that started before us (our workers exit, finding it)
                Logger::log('IPC server already running!');
                return true;
            } elseif ($count >= self::STARTUP_TIMEOUT*2 && !Serialization::isLockedByAnotherProcess($session)) {
                // The server died before reporting its state.
                // While the session is locked, a server is still loading it (or it's held by another process
                // that will release it soon): that can take longer than any fixed timeout.
                return new Exception("The IPC server didn't start within ".self::STARTUP_TIMEOUT." seconds, please check the logs!", previous: $e);
            }
            delay(0.5);
            $count++;
        }
        return false;
    }
    /**
     * Whether an IPC server listens on the socket of the session.
     */
    private static function isListening(SessionPaths $session): bool
    {
        try {
            clearstatcache(true, $session->getIpcPath());
            connect($session->getIpcPath())->disconnect();
            return true;
        } catch (Throwable) {
            return false;
        }
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
    #[\Override]
    final public function stop(): bool
    {
        $this->listener?->close();
        $this->listener = null;
        $this->listenerSecret = null;
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
    #[\Override]
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
     * @param ChannelledSocket|EncryptedChannel $socket Client
     */
    protected function clientLoop(ChannelledSocket|EncryptedChannel $socket): void
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
     * @param ChannelledSocket|EncryptedChannel  $socket  Socket
     * @param array{0: string, 1: array|Wrapper} $payload Payload
     */
    private function clientRequest(ChannelledSocket|EncryptedChannel $socket, int $id, array $payload): void
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
     *
     * @psalm-pure
     */
    public function __toString(): string
    {
        return 'IPC server';
    }
}
