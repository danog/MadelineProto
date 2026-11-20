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
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\delay;

final class BroadcastDelayTest extends TestCase
{
    /**
     * A broadcast about to send to $peers (as restored from a session), with an MTProto instance that only
     * has what the broadcast's progress updates need.
     */
    private static function broadcast(array $peers, Action $action, ?float $delay): InternalState
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::CALLABLE_LOGGER)->setExtra(static fn () => null));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/broadcast-delay-test.madeline'));
        $state = (new ReflectionClass(InternalState::class))->newInstanceWithoutConstructor();
        $state->__unserialize([
            'broadcastId' => 1,
            'API' => $API,
            'action' => $action,
            'filter' => Filter::default(),
            'delay' => $delay,
            'peers' => $peers,
            'totalCount' => \count($peers),
            'status' => (new ReflectionProperty(InternalState::class, 'status'))->getType()->getName()::IDLING_BEFORE_BROADCASTING,
        ]);
        return $state;
    }

    private static function recorder(float $duration = 0.0): Action
    {
        return new class($duration) implements Action {
            /** @var array<int, float> */
            public array $sent = [];
            public function __construct(private float $duration)
            {
            }
            public function act(int $broadcastId, int $peer, Cancellation $cancellation): void
            {
                $this->sent[$peer] = microtime(true);
                if ($this->duration) {
                    delay($this->duration, cancellation: $cancellation);
                }
            }
        };
    }

    private static function waitFor(InternalState $state, Status $status, float $timeout): void
    {
        $deadline = microtime(true) + $timeout;
        while ($state->getStatus() !== $status && microtime(true) < $deadline) {
            delay(0.01);
        }
    }

    public function testTheDelayIsWaitedBetweenEachPeer(): void
    {
        $action = self::recorder();
        $state = self::broadcast([1, 2, 3, 4], $action, 0.2);
        $state->resume();
        self::waitFor($state, Status::FINISHED, 5);

        $this->assertSame(Status::FINISHED, $state->getStatus());
        $this->assertSame([1, 2, 3, 4], array_keys($action->sent));
        $sent = array_values($action->sent);
        for ($i = 1; $i < \count($sent); $i++) {
            $this->assertGreaterThanOrEqual(0.19, $sent[$i] - $sent[$i-1]);
        }
        $this->assertSame(4, $state->getProgress()->successCount);
    }

    public function testResumingDoesNotWaitForTheBroadcast(): void
    {
        // MTProto::wakeup() resumes the broadcasts of the session: it must not wait for them to be sent.
        $action = self::recorder(0.3);
        $state = self::broadcast(range(1, 120), $action, null);
        $start = microtime(true);
        $state->resume();
        $this->assertLessThan(0.1, microtime(true) - $start);
        self::waitFor($state, Status::FINISHED, 5);
        $this->assertSame(Status::FINISHED, $state->getStatus());
        $this->assertCount(120, $action->sent);
    }

    public function testCancellingInterruptsTheDelay(): void
    {
        $action = self::recorder();
        $state = self::broadcast([1, 2, 3], $action, 10.0);
        $state->resume();
        delay(0.1);
        $start = microtime(true);
        $state->cancel();
        self::waitFor($state, Status::CANCELLED, 1);
        $this->assertSame(Status::CANCELLED, $state->getStatus());
        $this->assertLessThan(0.5, microtime(true) - $start);
        $this->assertSame([1], array_keys($action->sent));
    }
}
