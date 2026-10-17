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

use danog\Loop\GenericLoop;
use SplQueue;

/**
 * @template T
 *
 * @internal
 *
 * @implements Subscriber<T>
 */
final class Actor implements Subscriber
{
    /** @var SplQueue<list{T}|list{T, T}> */
    private readonly SplQueue $queue;
    private GenericLoop $loop;

    public function __construct(
        /** @var Subscriber<T> $subscriber */
        private readonly Subscriber $subscriber
    ) {
        /** @var SplQueue<list{T}|list{T, T}> */
        $this->queue = new SplQueue;
        $this->queue->setIteratorMode(SplQueue::IT_MODE_DELETE);
        $this->__wakeup();
    }

    /**
     * @psalm-pure
     */
    public function __sleep()
    {
        return ['queue', 'subscriber'];
    }

    public function __wakeup(): void
    {
        $this->loop = new GenericLoop(function (): ?float {
            foreach ($this->queue as $item) {
                if (\count($item) === 1) {
                    $this->subscriber->onAttach($item[0]);
                } else {
                    $this->subscriber->onStateChange($item[0], $item[1]);
                }
            }
            return GenericLoop::PAUSE;
        }, '');
    }

    #[\Override]
    public function onAttach($initState): void
    {
        $this->queue->enqueue([$initState]);
        if ($this->loop->isRunning()) {
            $this->loop->resume(true);
        } else {
            $this->loop->start();
        }
    }

    #[\Override]
    public function onStateChange($prevState, $state): void
    {
        $this->queue->enqueue([$prevState, $state]);
        if ($this->loop->isRunning()) {
            $this->loop->resume(true);
        } else {
            $this->loop->start();
        }
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function __toString(): string
    {
        return 'Actor<' . $this->subscriber::class . '>';
    }
}
