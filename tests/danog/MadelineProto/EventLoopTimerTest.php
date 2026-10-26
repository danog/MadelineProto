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

use PHPUnit\Framework\TestCase;

/**
 * Short timers fire while a signal handler is registered, as MadelineProto's always are.
 *
 * With Revolt's UvDriver on libuv < 1.45 (bookworm's 1.44, in the image before), uv_run(UV_RUN_ONCE) ran the timers
 * already due before polling, then polled with no timeout, as the active signal handle kept the loop alive: a timer
 * due by the time the UvDriver entered its blocking run (often, for a delay of a millisecond) left the event loop
 * asleep until the next I/O event, forever without one. It hung ReferenceDatabaseTest (whose mocked calls wait 1 ms) in
 * the CI. Excluded from phpunit-light: the pull requests run in the image of the main branch, built before the fix.
 *
 * The loop runs in a child process, which the test kills if it hangs.
 *
 * @internal
 */
final class EventLoopTimerTest extends TestCase
{
    private const DEADLINE = 30.0;

    public function testShortTimersFireWithASignalHandler(): void
    {
        $code = <<<'PHP'
            require $argv[1];
            Revolt\EventLoop::onSignal(SIGUSR2, static fn () => null);
            echo get_class(Revolt\EventLoop::getDriver()), "\n";
            for ($i = 0; $i < 3000; $i++) {
                Amp\delay(0.001);
            }
            echo "done\n";
            PHP;
        $process = proc_open(
            [PHP_BINARY, '-r', $code, '--', __DIR__.'/../../../vendor/autoload.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        $start = microtime(true);
        while (proc_get_status($process)['running'] && microtime(true) - $start < self::DEADLINE) {
            usleep(50_000);
        }
        $running = proc_get_status($process)['running'];
        if ($running) {
            proc_terminate($process, SIGKILL);
        }
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        proc_close($process);
        $this->assertFalse($running, "3000 delays of 1 ms didn't complete in ".self::DEADLINE." seconds: $output");
        $this->assertStringEndsWith("done\n", $output);
    }
}
