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

use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use PHPUnit\Framework\TestCase;

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
