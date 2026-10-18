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

use Amp\Cancellation;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Broadcast\Action;
use danog\MadelineProto\Broadcast\Filter;
use danog\MadelineProto\Broadcast\InternalState;
use danog\MadelineProto\Broadcast\Status;
use danog\MadelineProto\Broadcast\StatusInternal;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function Amp\delay;

/**
 * Broadcasts resumed from a serialized state, sending to fake peers.
 *
 * @internal
 */
final class BroadcastTest extends TestCase
{
    private static function resumeBroadcast(Action $action, int $peers, ?float $delay): InternalState
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-broadcast-test'));
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::CALLABLE_LOGGER)->setExtra(static function (): void {}));

        $state = (new ReflectionClass(InternalState::class))->newInstanceWithoutConstructor();
        $state->__unserialize([
            'peers' => range(1, $peers),
            'totalCount' => $peers,
            'successCount' => 0,
            'failCount' => 0,
            'status' => StatusInternal::IDLING_BEFORE_BROADCASTING,
            'cancelled' => false,
            'broadcastId' => 1,
            'API' => $API,
            'action' => $action,
            'filter' => Filter::default(),
            'delay' => $delay,
        ]);
        $start = microtime(true);
        $state->resume();
        // Resuming happens while starting up the session: it must not wait for the broadcast
        self::assertLessThan(0.05, microtime(true) - $start);
        return $state;
    }
    private static function waitFinal(InternalState $state, float $timeout): void
    {
        $deadline = microtime(true) + $timeout;
        while ($state->getStatus() !== Status::FINISHED && $state->getStatus() !== Status::CANCELLED) {
            self::assertLessThan($deadline, microtime(true), 'The broadcast did not complete');
            delay(0.01);
        }
    }

    public function testDelayIsBetweenEachPeer(): void
    {
        $action = new class implements Action {
            /** @var array<int, float> */
            public array $sentAt = [];
            public function act(int $broadcastId, int $peer, Cancellation $cancellation): void
            {
                $this->sentAt[$peer] = microtime(true);
                delay(0.01);
            }
        };
        $state = self::resumeBroadcast($action, 5, 0.1);
        self::waitFinal($state, 5);

        self::assertSame(Status::FINISHED, $state->getStatus());
        self::assertSame(5, $state->getProgress()->successCount);
        self::assertSame(0, $state->getProgress()->pendingCount);
        self::assertCount(5, $action->sentAt);
        for ($peer = 2; $peer <= 5; $peer++) {
            self::assertGreaterThanOrEqual(0.1, $action->sentAt[$peer] - $action->sentAt[$peer - 1]);
        }
    }

    public function testCancellingInterruptsTheDelay(): void
    {
        $action = new class implements Action {
            public int $sent = 0;
            public function act(int $broadcastId, int $peer, Cancellation $cancellation): void
            {
                $this->sent++;
            }
        };
        $state = self::resumeBroadcast($action, 5, 10.0);
        delay(0.05);
        $state->cancel();
        self::waitFinal($state, 1);

        self::assertSame(Status::CANCELLED, $state->getStatus());
        self::assertSame(1, $action->sent);
    }

    public function testParallelBatchesAreAllAwaited(): void
    {
        $action = new class implements Action {
            public int $running = 0;
            public int $maxRunning = 0;
            public int $done = 0;
            public function act(int $broadcastId, int $peer, Cancellation $cancellation): void
            {
                $this->running++;
                $this->maxRunning = max($this->maxRunning, $this->running);
                // The peers of the last, partial batch take the longest
                delay($peer > 100 ? 0.2 : 0.05);
                $this->running--;
                $this->done++;
            }
        };
        $state = self::resumeBroadcast($action, 120, null);
        self::waitFinal($state, 5);

        self::assertSame(Status::FINISHED, $state->getStatus());
        self::assertSame(120, $action->done);
        self::assertSame(50, $action->maxRunning);
        self::assertSame(120, $state->getProgress()->successCount);
    }
}
