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

namespace danog\MadelineProto\Reactive;

use Amp\Cancellation;
use Amp\DeferredFuture;
use WeakMap;
use Webmozart\Assert\Assert;

/**
 * @internal
 *
 * @template T
 */
final class Publisher
{
    /** @var WeakMap<BaseSubscriber<T>, Subscriber<T>> */
    private WeakMap $subscribers;
    private bool $wokeup = false;
    /**
     * Fibers waiting for a state (waitForState()): never serialized, a wait can't survive a restart.
     *
     * @var array<int, list{T, DeferredFuture<null>}>
     */
    private array $waiters = [];
    private int $waiterId = 0;
    /**
     * @param T $state
     *
     * @psalm-mutation-free
     */
    public function __construct(
        private mixed $state
    ) {
        $this->subscribers = new WeakMap;
        $this->wokeup = true;
    }

    /** @return T */
    public function getState(): mixed
    {
        return $this->state;
    }

    public function __serialize(): array
    {
        $subscribers = [];
        foreach ($this->subscribers as $subscriber => $v) {
            if ($subscriber instanceof EphemeralSubscriber) {
                continue;
            }
            $subscribers []= [$subscriber, $v];
        }
        return ['state' => $this->state, 'subscribers' => $subscribers];
    }

    /**
     * @param array{state: T, subscribers: list<{BaseSubscriber<T>, Subscriber<T>}>} $data
     */
    public function __unserialize(array $data): void
    {
        $this->state = $data['state'];
        /** @var WeakMap<BaseSubscriber<T>, Subscriber<T>>  */
        $this->subscribers = new WeakMap;
        foreach ($data['subscribers'] as [$subscriber, $v]) {
            // The waiters (AsyncWaiter) saved by the versions up to 8.8.9, without their state: a wait can't survive a
            // restart.
            if ($subscriber instanceof EphemeralSubscriber) {
                continue;
            }
            $this->subscribers[$subscriber] = $v;
        }
    }

    public function wakeup(): void
    {
        if (!$this->wokeup) {
            $this->wokeup = true;
            foreach ($this->subscribers as $v) {
                $v->onAttach($this->state);
            }
        }
    }

    /** @param BaseSubscriber<T> $subscriber */
    public function subscribe(BaseSubscriber $subscriber): void
    {
        if ($subscriber instanceof SimpleSubscriber) {
            $subscriberK = $subscriber;
            $subscriber = new SimpleSubscriberAdaptor($subscriber);
        } else {
            Assert::isInstanceOf($subscriber, Subscriber::class);
            $subscriberK = $subscriber;
        }
        if (!isset($this->subscribers[$subscriberK])) {
            $subscriber = new Actor($subscriber);
            $this->subscribers[$subscriberK] = $subscriber;
            $subscriber->onAttach($this->state);
        }
    }

    /** @param BaseSubscriber<T> $subscriber */
    public function unsubscribe(BaseSubscriber $subscriber): void
    {
        unset($this->subscribers[$subscriber]);
    }

    /** @param T $state */
    public function publish($state): void
    {
        if ($state !== $this->state) {
            $prev = $this->state;
            $this->state = $state;
            $this->wokeup = true;
            foreach ($this->waiters as $id => [$waitFor, $deferred]) {
                if ($state === $waitFor) {
                    // Completed once: removed even before the waiting fiber resumes.
                    unset($this->waiters[$id]);
                    $deferred->complete();
                }
            }
            foreach ($this->subscribers as $subscriber) {
                $subscriber->onStateChange($prev, $state);
            }
        }
    }

    /** @param T $state */
    public function waitForState($state, ?Cancellation $cancellation = null): void
    {
        if ($state === $this->state) {
            return;
        }
        $id = $this->waiterId++;
        $deferred = new DeferredFuture;
        $this->waiters[$id] = [$state, $deferred];
        try {
            $deferred->getFuture()->await($cancellation);
        } finally {
            unset($this->waiters[$id]);
        }
    }
}
