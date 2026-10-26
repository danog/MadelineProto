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
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\delay;

/**
 * The chunks of an RTMP livestream were fetched as soon as the server accepted them: those of its live edge, cut short
 * (storage.filePartial) while being written ("Unexpected end of MP4 file"). As tgcalls, the receiver follows the stream
 * in real time, 2 seconds behind its edge, and takes a chunk cut short for good as it is.
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
     * Follow an RTMP stream whose live edge is at 10.5 s for $seconds, the chunks being cut short if $partial says so.
     *
     * @param \Closure(int): bool $partial
     */
    private function follow(float $seconds, \Closure $partial): void
    {
        BypassFinals::enable();
        $start = hrtime(true);
        $API = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['getGroupCallStreamChannels', 'downloadGroupCallStreamChunk'])->getMock();
        $API->method('getGroupCallStreamChannels')->willReturn([['channel' => 1, 'scale' => 0, 'last_timestamp_ms' => 10_500]]);
        $API->method('downloadGroupCallStreamChunk')->willReturnCallback(function (int $id, int $timeMs) use ($start, $partial): array {
            $this->fetched[] = [$timeMs, intdiv(hrtime(true) - $start, 1_000_000)];
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

    public function testAChunkCutShortForGoodIsTakenAsItIs(): void
    {
        $this->follow(1.8, static fn (int $timeMs): bool => $timeMs === 8000);
        $chunks = array_count_values(array_column($this->fetched, 0));
        // Fetched again for a second, in case it was still being written, then recorded as it is.
        $this->assertGreaterThanOrEqual(8, $chunks[8000]);
        $this->assertLessThanOrEqual(11, $chunks[8000]);
        $this->assertSame(1, $chunks[9000] ?? null);
        $this->assertNotEmpty(preg_grep('~^Recorded the chunk 8000 of the call up to where it was cut short~', $this->logs));
    }
}
