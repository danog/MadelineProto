<?php

declare(strict_types=1);

/**
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

namespace danog\MadelineProto\Test;

use Amp\Ipc\Sync\ChannelException;
use ArrayObject;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use danog\MadelineProto\Tools;
use PHPUnit\Framework\TestCase;
use Throwable;

use function Amp\async;
use function Amp\delay;
use function Amp\Ipc\connect;
use function Amp\Ipc\listen;

/**
 * An IPC server that is shutting down can still have its socket bound: it accepts connections, and drops them.
 *
 * A client that lost its connection reconnected, and resent its pending calls, as soon as the socket accepted a
 * connection, starting a new IPC server (a worker process, and the requests of the web runner) every time: it spun
 * on the dying server, leaking the file descriptors of every startup, until stream_select hit FD_SETSIZE
 * (https://github.com/danog/MadelineProto/issues/1811).
 *
 * @internal
 */
final class IpcReconnectTest extends TestCase
{
    private string $dir;
    private string $session;

    public function setUp(): void
    {
        if (!is_dir('/proc/self/fd')) {
            self::markTestSkipped('Needs /proc/self/fd');
        }
        Magic::start(light: true);
        $this->dir = sys_get_temp_dir().'/madeline-ipc-reconnect-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
        // As the workers see it (/tmp is /private/tmp on macOS)
        $this->dir = realpath($this->dir);
        $this->session = "{$this->dir}/session.madeline";
    }

    public function tearDown(): void
    {
        if (isset($this->dir)) {
            $this->killWorkers();
            exec('rm -rf '.escapeshellarg($this->dir));
        }
    }

    public function testReconnectingToADyingServerDoesNotSpin(): void
    {
        $fds = self::countFds();
        $paths = new SessionPaths($this->session);
        $server = listen($paths->getIpcPath());
        $accepted = 0;
        $acceptor = async(static function () use ($server, &$accepted): void {
            while ($socket = $server->accept()) {
                $accepted++;
                $socket->disconnect();
            }
        });

        $logger = new Logger((new SettingsLogger)->setType(Logger::CALLABLE_LOGGER)->setExtra(static function (): void {
        })->setLevel(Logger::ULTRA_VERBOSE));
        $client = new Client(connect($paths->getIpcPath()), $paths, $logger);
        $call = async($client->__call(...), 'getSelf', []);
        $call->ignore();

        delay(1.0);
        $reconnects = $accepted;
        delay(3.0);
        $reconnects = $accepted - $reconnects;

        $client->disconnect();
        $server->close();
        $acceptor->await();

        // At most one reconnection (and one server startup) per second while the dying server is still bound:
        // there used to be dozens.
        self::assertLessThanOrEqual(4, $reconnects);

        // The pipes of the workers are closed once they exit: they used to stay open forever.
        $this->killWorkers();
        $end = microtime(true) + 10;
        while (self::countFds() > $fds && microtime(true) < $end) {
            delay(0.1);
        }
        self::assertLessThanOrEqual($fds, self::countFds(), 'Leaking file descriptors');
    }

    /**
     * A server that accepts connections and drops them before answering (the session is served by an instance
     * that can't handle the calls of the client, for example) made the client reconnect and resend its calls
     * forever, every second, starting a new IPC server every time: a worker process, and requests to the web
     * server, all of them waiting forever for the lock of the instance.
     */
    public function testGivesUpOnAServerThatDropsTheCalls(): void
    {
        $paths = new SessionPaths($this->session);
        $server = listen($paths->getIpcPath());
        $accepted = 0;
        $acceptor = async(static function () use ($server, &$accepted): void {
            while ($socket = $server->accept()) {
                $accepted++;
                $socket->disconnect();
            }
        });
        $logs = $this->captureLogs();

        $client = new Client(connect($paths->getIpcPath()), $paths, Logger::$default);
        $start = microtime(true);
        try {
            async($client->__call(...), 'getSelf', [])->await(Tools::getTimeoutCancellation(30.0));
            self::fail('The call should fail');
        } catch (ChannelException $e) {
            $previous = $e->getPrevious();
            self::assertInstanceOf(ChannelException::class, $previous);
            self::assertStringContainsString('without answering getSelf, giving up', $previous->getMessage());
        }
        $elapsed = microtime(true) - $start;
        // Sent on 5 connections, with a growing delay between them (none, 1, 2, 4 seconds)
        self::assertSame(5, $accepted);
        self::assertGreaterThan(6.0, $elapsed);
        self::assertLessThan(15.0, $elapsed);
        // The server listens: no new server was started
        self::assertSame([], array_values(array_filter($logs->getArrayCopy(), static fn (string $log): bool => str_contains($log, 'Starting IPC server'))));

        // And the client doesn't reconnect anymore: its calls fail right away
        delay(2.0);
        self::assertSame(5, $accepted);
        $this->expectException(ChannelException::class);
        try {
            $client->getSelf();
        } finally {
            $server->close();
            $acceptor->await();
        }
    }

    /**
     * The process holding the session lock serves the session once it has loaded it: a client that lost its
     * connection waits for it, instead of starting a new server that would wait for the lock.
     */
    public function testWaitsForTheLockHolder(): void
    {
        $paths = new SessionPaths($this->session);
        touch($paths->getLockPath());
        $lock = fopen($paths->getLockPath(), 'c');
        self::assertTrue(flock($lock, LOCK_EX|LOCK_NB));
        $logs = $this->captureLogs();

        $server = listen($paths->getIpcPath());
        $client = new Client(connect($paths->getIpcPath()), $paths, Logger::$default);
        $socket = $server->accept();
        // The holder restarts its server
        $server->close();
        $socket->disconnect();

        $call = async($client->__call(...), 'getSelf', []);
        delay(3.0);
        self::assertFalse($call->isComplete());
        self::assertSame([], array_values(array_filter($logs->getArrayCopy(), static fn (string $log): bool => str_contains($log, 'Starting IPC server'))));

        $server = listen($paths->getIpcPath());
        $socket = $server->accept();
        [$id, [$function]] = [0, $socket->receive()];
        self::assertSame('getSelf', $function);
        $socket->send([$id, 'ok']);
        self::assertSame('ok', $call->await(Tools::getTimeoutCancellation(5.0)));

        $client->disconnect();
        $socket->disconnect();
        $server->close();
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /**
     * A client that disconnects stops reconnecting, even while it waits for the lock holder.
     */
    public function testDisconnectStopsReconnecting(): void
    {
        $paths = new SessionPaths($this->session);
        touch($paths->getLockPath());
        $lock = fopen($paths->getLockPath(), 'c');
        self::assertTrue(flock($lock, LOCK_EX|LOCK_NB));
        $this->captureLogs();

        $server = listen($paths->getIpcPath());
        $client = new Client(connect($paths->getIpcPath()), $paths, Logger::$default);
        $server->accept()->disconnect();
        $server->close();
        $call = async($client->__call(...), 'getSelf', []);
        delay(1.0);
        $client->disconnect();
        try {
            $call->await(Tools::getTimeoutCancellation(2.0));
            self::fail('The call should fail');
        } catch (ChannelException) {
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /**
     * Only one runner starts a worker: the web runner is a fallback for a worker process that dies without reporting
     * its state (in the CLI there's no web runner, so the startup fails as soon as the worker exits).
     */
    public function testStartupFailsOnceTheWorkerDies(): void
    {
        $this->captureLogs();
        // The worker exits right away, without reporting its state: the session doesn't exist
        $paths = new SessionPaths($this->session);
        exec('rm -rf '.escapeshellarg($this->session));
        self::assertFileDoesNotExist($this->session);
        $start = microtime(true);
        $result = Server::startMe($paths)->await(Tools::getTimeoutCancellation(30.0));
        self::assertInstanceOf(Throwable::class, $result);
        self::assertStringContainsString("We couldn't start the IPC server", $result->getMessage());
        // It used to wait for STARTUP_TIMEOUT (30 seconds)
        self::assertLessThan(10.0, microtime(true) - $start);
    }

    /**
     * @return ArrayObject<int, string>
     */
    private function captureLogs(): ArrayObject
    {
        $logs = new ArrayObject;
        Logger::constructorFromSettings((new SettingsLogger)->setType(Logger::CALLABLE_LOGGER)->setExtra(static function (mixed $param) use ($logs): void {
            $logs[] = (string) $param;
        })->setLevel(Logger::ULTRA_VERBOSE));
        return $logs;
    }

    private function killWorkers(): void
    {
        // Before and after they rename themselves
        foreach (['[m]adeline-ipc '.$this->session, '[M]adelineProto worker '.$this->session.'$'] as $pattern) {
            exec('pkill -9 -f '.escapeshellarg($pattern));
        }
    }

    private static function countFds(): int
    {
        return \count(scandir('/proc/self/fd')) - 2;
    }
}
