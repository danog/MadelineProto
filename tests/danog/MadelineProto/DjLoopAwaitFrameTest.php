<?php declare(strict_types=1);

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

use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Settings\VoIP;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/**
 * DjLoop::awaitFrame(), what a playback track whose queue ran dry waits on: until a frame is queued, or the
 * timeout, whichever comes first.
 */
final class DjLoopAwaitFrameTest extends TestCase
{
    private static function dj(): DjLoop
    {
        return new DjLoop(new class implements CallControllerInterface {
            public function log(string $message, int $level = Logger::NOTICE): void
            {
            }

            public function isCallEnded(): bool
            {
                return false;
            }

            public function getVoIPSettings(): VoIP
            {
                return new VoIP;
            }

            public function __toString(): string
            {
                return 'test call';
            }
        });
    }

    /** What every path that queues a frame calls. */
    private static function frameQueued(DjLoop $dj): void
    {
        (new \ReflectionMethod($dj, 'wakePacketWaiters'))->invoke($dj);
    }

    public function testItReturnsAtTheTimeoutWithoutAnException(): void
    {
        $dj = self::dj();
        $start = hrtime(true);
        $dj->awaitFrame(0.05);
        $elapsedMs = (hrtime(true) - $start) / 1e6;
        $this->assertGreaterThanOrEqual(45, $elapsedMs);
        $this->assertLessThan(500, $elapsedMs);
    }

    public function testAQueuedFrameWakesEveryWaiterEarly(): void
    {
        $dj = self::dj();
        $start = hrtime(true);
        $waiters = [async($dj->awaitFrame(...), 5.0), async($dj->awaitFrame(...), 5.0)];
        EventLoop::delay(0.02, static fn () => self::frameQueued($dj));
        await($waiters);
        $this->assertLessThan(1000, (hrtime(true) - $start) / 1e6);
    }

    public function testAFrameQueuedAsTheTimeoutFiresResumesOnce(): void
    {
        $dj = self::dj();
        // Both in the same tick: the waiter must be resumed once (a second resume() would throw).
        $waiter = async($dj->awaitFrame(...), 0.01);
        EventLoop::delay(0.01, static fn () => self::frameQueued($dj));
        $waiter->await();
        // And nothing stays registered or scheduled afterwards.
        delay(0.05);
        $this->assertSame([], (new \ReflectionProperty($dj, 'frameWaiters'))->getValue($dj));
    }
}
