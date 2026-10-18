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

use danog\MadelineProto\Reactive\Publisher;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\delay;

/**
 * Waiting for a state doesn't leave anything in the publisher: a session saved after a wait (without a GC cycle in
 * between) used to keep the waiter, saved empty, which threw when the publisher was woken up after loading it.
 */
final class PublisherTest extends TestCase
{
    public function testAWaitLeavesNothingToSerialize(): void
    {
        $publisher = new Publisher(0);
        $wait = async($publisher->waitForState(...), 1);
        delay(0);
        $publisher->publish(1);
        $wait->await();

        gc_disable();
        try {
            $loaded = unserialize(serialize($publisher));
            $loaded->wakeup();
            $loaded->publish(2);
            delay(0.01);
            $this->assertSame(2, $loaded->getState());
            $this->assertSame([], (fn () => $this->waiters)->call($loaded));
            $this->assertCount(0, (fn () => $this->subscribers)->call($loaded));
        } finally {
            gc_enable();
        }
    }

    public function testTheAwaitedStateReachedTwiceBeforeTheWaiterResumes(): void
    {
        $publisher = new Publisher(0);
        $wait = async($publisher->waitForState(...), 1);
        delay(0);
        // Queued in the actor of the waiter, processed at once.
        $publisher->publish(1);
        $publisher->publish(2);
        $publisher->publish(1);
        $wait->await();
        delay(0.01);
        $this->assertSame(1, $publisher->getState());
    }

    public function testWaitersSavedByOlderVersionsAreDropped(): void
    {
        // As saved by the versions that serialized the waiters: an empty AsyncWaiter, in its actor.
        $waiter = (new \ReflectionClass(\danog\MadelineProto\Reactive\AsyncWaiter::class))->newInstanceWithoutConstructor();
        $actor = new \danog\MadelineProto\Reactive\Actor($waiter);
        $publisher = (new \ReflectionClass(Publisher::class))->newInstanceWithoutConstructor();
        $publisher->__unserialize(['state' => 0, 'subscribers' => [[$waiter, $actor]]]);
        $publisher->wakeup();
        $publisher->publish(1);
        delay(0.01);
        $this->assertSame(1, $publisher->getState());
    }

    public function testACancelledWaitLeavesNothing(): void
    {
        $publisher = new Publisher(0);
        $cancellation = new \Amp\DeferredCancellation;
        $wait = async($publisher->waitForState(...), 1, $cancellation->getCancellation());
        delay(0);
        $cancellation->cancel();
        try {
            $wait->await();
            $this->fail('The wait was not cancelled');
        } catch (\Amp\CancelledException) {
        }
        gc_disable();
        try {
            $this->assertSame([], (fn () => $this->waiters)->call($publisher));
        } finally {
            gc_enable();
        }
    }
}
