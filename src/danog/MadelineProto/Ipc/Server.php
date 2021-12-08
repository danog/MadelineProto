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
use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Promise;
use Amp\Success;
use danog\Loop\SignalLoop;
use danog\MadelineProto\Exception as Exception;
use danog\MadelineProto\Ipc\Runner\ProcessRunner;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Tools;

use function Amp\Promise\first;

/**
 * IPC server.
 */
class Server extends SignalLoop
{
    use InternalLoop;
    /**
     * Server version.
     */
    const VERSION = 1;
    /**
     * Shutdown server.
     */
    const SHUTDOWN = 0;
    /**
     * Boolean to shut down worker, if started.
     */
    private static bool $shutdown = false;
    /**
     * Deferred to shut down worker, if started.
     */
    private static ?Deferred $shutdownDeferred = null;
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
     * IPC settings.
     */
    private Ipc $settings;
    /**
     * Set IPC path.
     *
     * @param SessionPaths $session Session
     *
     * @return void
     */
    public function setIpcPath(SessionPaths $session): void
    {
        self::$shutdownDeferred ??= new Deferred;
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
     * @param SessionPaths $session   Session path
     *
     * @return Promise
     */
    public static function startMe(SessionPaths $session): Promise
    {
        $id = Tools::randomInt(2000000000);
        $started = false;
        $promises = [];
        try {
            Logger::log("Starting IPC server $session (process)");
            $promises []= ProcessRunner::start($session, $id);
            $started = true;
            $promises []= WebRunner::start($session, $id);
            return Tools::call(self::monitor($session, $id, $started, first($promises)));
        } catch (\Throwable $e) {
            Logger::log($e);
        }
        try {
            Logger::log("Starting IPC server $session (web)");
            $promises []= WebRunner::start($session, $id);
            $started = true;
        } catch (\Throwable $e) {
            Logger::log($e);
        }
        return Tools::call(self::monitor($session, $id, $started, $promises ? first($promises) : (new Deferred)->promise()));
    }
    /**
     * Monitor session.
     *
     * @param SessionPaths  $session
     * @param int           $id
     * @param bool          $started
     * @param Promise<bool> $cancelConnect
     *
     * @return \Generator
     */
    private static function monitor(SessionPaths $session, int $id, bool $started, Promise $cancelConnect): \Generator
    {
        if (!$started) {
            Logger::log("It looks like the server couldn't be started, trying to connect anyway...");
        }
        $count = 0;
        while (true) {
            $state = yield $session->getIpcState();
            if ($state && $state->getStartupId() === $id) {
                if ($e = $state->getException()) {
                    Logger::log("IPC server got exception $e");
                    return $e;
                }
                Logger::log("IPC server started successfully!");
                return true;
            } elseif (!$started && $count > 0 && $count > 2*($state ? 3 : 1)) {
                return new Exception("We couldn't start the IPC server, please check the logs!");
            }
            try {
                yield Tools::timeoutWithDefault($cancelConnect, 500, null);
                $cancelConnect = (new Deferred)->promise();
            } catch (\Throwable $e) {
                Logger::log("$e");
                Logger::log("Could not start IPC server, please check the logs for more details!");
                return $e;
            }
            $count++;
        }
        return false;
    }
    /**
     * Wait for shutdown.
     *
     * @return Promise
     */
    public static function waitShutdown(): Promise
    {
        if (self::$shutdownNow) {
            return new Success;
        }
        self::$shutdownDeferred ??= new Deferred;
        return self::$shutdownDeferred->promise();
    }
    /**
     * Shutdown
     *
     * @return void
     */
    final public function shutdown(): void
    {
        $this->signal(null);
        if (self::$shutdownDeferred) {
            self::$shutdownNow = true;
            $deferred = self::$shutdownDeferred;
            self::$shutdownDeferred = null;
            $deferred->resolve();
        }
    }
    /**
     * Main loop.
     *
     * @return \Generator
     */
    public function loop(): \Generator
    {
        while ($socket = yield $this->waitSignal($this->server->accept())) {
            Tools::callFork($this->clientLoop($socket));
        }
        $this->server->close();
        if (isset($this->callback)) {
            $this->callback->signal(null);
        }
    }
    /**
     * Client handler loop.
     *
     * @param ChannelledSocket $socket Client
     *
     * @return \Generator|Promise
     */
    protected function clientLoop(ChannelledSocket $socket)
    {
        $this->API->logger("Accepted IPC client connection!");

        $id = 0;
        $payload = null;
        try {
            while ($payload = yield $socket->receive()) {
                Tools::callFork($this->clientRequest($socket, $id++, $payload));
            }
        } catch (\Throwable $e) {
            Logger::log("Exception in IPC connection: $e");
        } finally {
            try {
                yield $socket->disconnect();
            } catch (\Throwable $e) {
            }
            if ($payload === self::SHUTDOWN) {
                $this->shutdown();
            }
        }
    }
    /**
     * Handle client request.
     *
     * @param ChannelledSocket                   $socket  Socket
     * @param array{0: string, 1: array|Wrapper} $payload Payload
     *
     * @return \Generator
     */
    private function clientRequest(ChannelledSocket $socket, int $id, $payload): \Generator
    {
        try {
            yield from $this->API->initAsynchronously();
            if ($payload[1] instanceof Wrapper) {
                $wrapper = $payload[1];
                $payload[1] = $this->callback->unwrap($wrapper);
            }
            $result = $this->API->{$payload[0]}(...$payload[1]);
            $result = $result instanceof \Generator
                ? yield from $result
                : ($result instanceof Promise
                    ? yield $result
                    : $result);
        } catch (\Throwable $e) {
            $this->API->logger("Got error while calling IPC method: $e", Logger::ERROR);
            $result = new ExitFailure($e);
        } finally {
            if (isset($wrapper)) {
                try {
                    yield $wrapper->disconnect();
                } catch (\Throwable $e) {
                }
            }
        }
        try {
            yield $socket->send([$id, $result]);
        } catch (\Throwable $e) {
            $this->API->logger("Got error while trying to send result of ${payload[0]}: $e", Logger::ERROR);
            try {
                yield $socket->send([$id, new ExitFailure($e)]);
            } catch (\Throwable $e) {
                $this->API->logger("Got error while trying to send error of error of ${payload[0]}: $e", Logger::ERROR);
            }
        }
    }
    /**
     * Get the name of the loop.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "IPC server";
    }

    /**
     * Set IPC settings.
     *
     * @param Ipc $settings IPC settings
     *
     * @return self
     */
    public function setSettings(Ipc $settings): self
    {
        $this->settings = $settings;

        return $this;
    }
}
