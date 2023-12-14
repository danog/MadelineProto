<?php

declare(strict_types=1);

/**
 * Update feeder loop.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Update;

use danog\Loop\Loop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use SplQueue;

/**
 * update feed loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class SeqLoop extends Loop
{
    use InternalLoop {
        __construct as private init;
    }
    /**
     * Incoming updates.
     */
    private SplQueue $incomingUpdates;
    /**
     * Update feeder.
     */
    private ?FeedLoop $feeder = null;
    /**
     * Pending updates.
     */
    private array $pendingWakeups = [];
    /**
     * State.
     */
    private ?UpdatesState $state = null;
    /**
     * Constructor.
     */
    public function __construct(MTProto $API)
    {
        $this->incomingUpdates = new SplQueue;
        $this->incomingUpdates->setIteratorMode(SplQueue::IT_MODE_DELETE);
        $this->init($API);
    }
    /**
     * Main loop.
     */
    public function loop(): ?float
    {
        if (!$this->isLoggedIn()) {
            return self::PAUSE;
        }
        $this->feeder = $this->API->feeders[FeedLoop::GENERIC];
        $this->state = $this->API->loadUpdateState();
        $this->API->logger("Resumed $this!", Logger::LEVEL_ULTRA_VERBOSE);

        while (!$this->incomingUpdates->isEmpty()) {
            $this->parse($this->incomingUpdates);
        }

        foreach ($this->pendingWakeups as $channelId => $_) {
            if (isset($this->API->feeders[$channelId])) {
                $this->API->feeders[$channelId]->resume();
            }
        }
        $this->pendingWakeups = [];

        return self::PAUSE;
    }
    public function parse(SplQueue $updates): void
    {
        foreach ($updates as ['updates' => $updates, 'options' => $options]) {
            ['seq_start' => $seq_start, 'seq_end' => $seq_end] = $options;
            $result = $this->state->checkSeq($seq_start);
            if ($result > 0) {
                $this->API->logger('Seq hole. seq_start: '.$seq_start.' != cur seq: '.($this->state->seq() + 1), Logger::ERROR);
                $this->incomingUpdates = new SplQueue;
                $this->incomingUpdates->setIteratorMode(SplQueue::IT_MODE_DELETE);
                $this->API->updaters[UpdateLoop::GENERIC]->resumeAndWait();
                foreach ($updates as $update) {
                    $this->incomingUpdates->enqueue($update);
                }
                return;
            }
            if ($result < 0) {
                $this->API->logger('Seq too old. seq_start: '.$seq_start.' != cur seq: '.($this->state->seq() + 1), Logger::ERROR);
                continue;
            }
            $this->state->seq($seq_end);
            if (isset($options['date'])) {
                $this->state->date($options['date']);
            }

            $this->pendingWakeups += $this->feeder->feed($updates);
        }
    }
    /**
     * @param array<(array|mixed)> $updates
     */
    public function feed(array $updates): void
    {
        $this->API->logger('Was fed updates of type '.$updates['_'].'...', Logger::VERBOSE);
        $this->incomingUpdates->enqueue($updates);
    }
    /**
     * @param array<true> $wakeups
     */
    public function addPendingWakeups(array $wakeups): void
    {
        $this->pendingWakeups += $wakeups;
    }
    public function __toString(): string
    {
        return 'update seq loop';
    }
}
