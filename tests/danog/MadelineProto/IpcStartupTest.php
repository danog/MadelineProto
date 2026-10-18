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

use Amp\Process\Process;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SessionPaths;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\delay;
use function Amp\Future\await;

/**
 * Starting the IPC server of a session from several clients at once (a web server receiving several requests after
 * a restart, for example), or while the session takes long to load.
 *
 * Every client that gets the session lock starts a worker, which then waits for the lock: only one of them gets it.
 * The others used to time out after 30 seconds, and to report "Could not connect to MadelineProto, please enable
 * proc_open..." as their startup error, which reached the clients waiting for them; and a client gave up after
 * 30 seconds even while the session was still being loaded by its server.
 *
 * Uses a new session (API_ID and API_HASH only).
 *
 * @internal
 */
final class IpcStartupTest extends TestCase
{
    private const CHILD = __DIR__.'/../../ipc_startup_child.php';
    private const CLIENTS = 8;

    private string $dir;
    private string $session;

    public function setUp(): void
    {
        foreach (['API_ID', 'API_HASH'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        Magic::start(light: true);
        $this->dir = sys_get_temp_dir().'/madeline-ipc-startup-'.bin2hex(random_bytes(8));
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

    public function testConcurrentClientsStartOneServer(): void
    {
        $this->runClients(1);
        $this->killWorkers();
        @unlink("{$this->dir}/MadelineProto.log");

        $this->runClients(self::CLIENTS);

        // The redundant workers exit as soon as they find the one that got the lock serving the session:
        // they used to wait for 30 seconds before failing.
        $end = microtime(true) + 20;
        while (\count($this->workers()) > 1 && microtime(true) < $end) {
            delay(0.5);
        }
        self::assertCount(1, $this->workers(), 'The redundant workers are still running');
        $log = (string) @file_get_contents("{$this->dir}/MadelineProto.log");
        self::assertStringNotContainsString('Could not connect to MadelineProto', $log);
        self::assertStringNotContainsString('Got exception in IPC server', $log);
        self::assertStringNotContainsString('Reporting error', $log);
        self::assertNull((new SessionPaths($this->session))->getIpcState()?->getException());

        // And the clients still use the server that won
        $this->runClients(1);
        self::assertCount(1, $this->workers());
    }

    public function testClientWaitsForASessionThatTakesLongToLoad(): void
    {
        $this->runClients(1);
        $this->killWorkers();

        // Locked by a server loading the session for longer than the 30 seconds clients used to wait
        $lock = fopen((new SessionPaths($this->session))->getLockPath(), 'c');
        self::assertTrue(flock($lock, LOCK_EX));
        try {
            $client = async($this->runClients(...), 1);
            delay(40);
            self::assertFalse($client->isComplete(), 'The client did not wait for the session to be loaded');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        $client->await();
        self::assertCount(1, $this->workers());
    }

    /**
     * Run clients concurrently, checking that they all succeed.
     */
    private function runClients(int $count): void
    {
        $futures = [];
        for ($x = 0; $x < $count; $x++) {
            $futures[] = async(function () use ($x): void {
                $process = Process::start(
                    [PHP_BINARY, self::CHILD, $this->session, "{$this->dir}/client$x.log"],
                    $this->dir,
                );
                $output = async(static fn () => buffer($process->getStdout()).buffer($process->getStderr()));
                $code = $process->join();
                $output = $output->await();
                self::assertSame(0, $code, "Client $x failed: $output");
                self::assertSame('OK', $output, "Client $x failed");
            });
        }
        await($futures);
    }

    /**
     * @return list<string> The PIDs of the workers of the session.
     */
    private function workers(): array
    {
        exec('pgrep -f '.$this->workerPattern(), $pids);
        return $pids;
    }

    /**
     * Matches the command line of the workers, not the one of the shell running pgrep.
     */
    private function workerPattern(): string
    {
        return escapeshellarg('[M]adelineProto worker '.$this->session.'$');
    }

    private function killWorkers(): void
    {
        exec('pkill -f '.$this->workerPattern());
        $end = microtime(true) + 30;
        while ($this->workers() && microtime(true) < $end) {
            delay(0.2);
        }
        if ($this->workers()) {
            exec('pkill -9 -f '.$this->workerPattern());
        }
    }
}
