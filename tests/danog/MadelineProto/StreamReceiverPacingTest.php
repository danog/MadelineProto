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

use danog\MadelineProto\EventHandler\Calls\GroupCall;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\GroupCall\StreamReceiver;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCError\RateLimitError;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\delay;

/**
 * The chunks of an RTMP livestream were fetched as soon as the server accepted them: those of its live edge, cut short
 * (storage.filePartial) while being written ("Unexpected end of MP4 file"). As tgcalls, the receiver follows the stream
 * in real time, 2 seconds behind its edge, and takes the chunks as they are, whatever their flag (all of them are
 * flagged partial, even behind the edge: fetched again, it fell behind until TIME_TOO_SMALL).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class StreamReceiverPacingTest extends TestCase
{
    /** @var list<array{int, int}> The chunks fetched, and when (ms since the start). */
    private array $fetched = [];
    /** @var list<string> */
    private array $logs = [];

    /**
     * Follow an RTMP stream whose live edge is at 10.5 s for $seconds, the chunks being cut short if $partial says so,
     * or failing with what $error returns.
     *
     * @param \Closure(int): bool $partial
     * @param ?\Closure(int): ?\Throwable $error
     */
    private function follow(float $seconds, \Closure $partial, ?\Closure $error = null): void
    {
        BypassFinals::enable();
        $start = hrtime(true);
        $API = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['getGroupCallStreamChannels', 'downloadGroupCallStreamChunk'])->getMock();
        $API->method('getGroupCallStreamChannels')->willReturn([['channel' => 1, 'scale' => 0, 'last_timestamp_ms' => 10_500]]);
        $API->method('downloadGroupCallStreamChunk')->willReturnCallback(function (int $id, int $timeMs) use ($start, $partial, $error): array {
            $this->fetched[] = [$timeMs, intdiv(hrtime(true) - $start, 1_000_000)];
            if ($error !== null && ($e = $error($timeMs)) !== null) {
                throw $e;
            }
            return ['bytes' => 'not a chunk', 'partial' => $partial($timeMs)];
        });
        $public = (new ReflectionClass(GroupCall::class))->newInstanceWithoutConstructor();
        $public->id = 1;
        $call = $this->getMockBuilder(GroupCallController::class)->disableOriginalConstructor()->onlyMethods(['log', '__toString'])->getMock();
        $call->method('log')->willReturnCallback(function (string $message): void {
            $this->logs[] = $message;
        });
        $call->method('__toString')->willReturn('the call');
        (new ReflectionProperty(GroupCallController::class, 'API'))->setValue($call, $API);
        (new ReflectionProperty(GroupCallController::class, 'public'))->setValue($call, $public);

        $receiver = new StreamReceiver($call);
        $receiver->start(true);
        delay($seconds);
        $receiver->stop();
        // Its loop exits once its last wait (a second at most) is over.
        delay(1.1);
    }

    public function testTheChunksAreFetchedInRealTimeBehindTheEdge(): void
    {
        $this->follow(2.5, static fn (int $timeMs): bool => false);
        $this->assertSame([8000, 9000, 10000], array_column($this->fetched, 0));
        foreach ($this->fetched as [$timeMs, $at]) {
            // Each chunk once it's due: a second after the previous one.
            $this->assertGreaterThanOrEqual($timeMs - 8000 - 50, $at, "Chunk $timeMs fetched too early");
            $this->assertLessThan($timeMs - 8000 + 500, $at, "Chunk $timeMs fetched too late");
        }
    }

    public function testTheChunksCutShortAreTakenAsTheyAre(): void
    {
        $this->follow(1.5, static fn (int $timeMs): bool => true);
        // Each fetched once, on time, and recorded up to the cut.
        $this->assertSame([8000, 9000], array_column($this->fetched, 0));
        $this->assertLessThan(500, $this->fetched[1][1] - 1000);
        $this->assertNotEmpty(preg_grep('~^Recorded the chunk 8000 of the call up to where it was cut short~', $this->logs));
    }

    public function testAFloodWaitIsWaitedOut(): void
    {
        $flooded = false;
        $this->follow(1.5, static fn (int $timeMs): bool => false, static function (int $timeMs) use (&$flooded): ?\Throwable {
            if ($flooded) {
                return null;
            }
            $flooded = true;
            return new RateLimitError('FLOOD_WAIT_1', 1, 420, 'upload.getFile');
        });
        // Fetched again a second later, not every 100 ms.
        $this->assertSame([8000, 8000], array_column(\array_slice($this->fetched, 0, 2), 0));
        $this->assertGreaterThanOrEqual(950, $this->fetched[1][1] - $this->fetched[0][1]);
    }
}
