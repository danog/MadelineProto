<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Update;

use danog\Loop\ResumableSignalLoop;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProtoTools\UpdatesState;

/**
 * update feed loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class SeqLoop extends ResumableSignalLoop
{
    use InternalLoop;
    /**
     * Incoming updates.
     */
    private array $incomingUpdates = [];
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
     * Main loop.
     *
     * @return \Generator
     */
    public function loop(): \Generator
    {
        $API = $this->API;
        $this->feeder = $API->feeders[FeedLoop::GENERIC];
        while (!$API->hasAllAuth()) {
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
        }
        $this->state = (yield from $API->loadUpdateState());
        while (true) {
            while (!$API->hasAllAuth()) {
                if (yield $this->waitSignal($this->pause())) {
                    return;
                }
            }
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
            while ($this->incomingUpdates) {
                $updates = $this->incomingUpdates;
                $this->incomingUpdates = [];
                yield from $this->parse($updates);
                $updates = null;
            }
            while ($this->pendingWakeups) {
                \reset($this->pendingWakeups);
                $channelId = \key($this->pendingWakeups);
                unset($this->pendingWakeups[$channelId]);
                if (isset($this->API->feeders[$channelId])) {
                    $this->API->feeders[$channelId]->resume();
                }
            }
        }
    }
    public function parse(array $updates): \Generator
    {
        \reset($updates);
        while ($updates) {
            $options = [];
            $key = \key($updates);
            $update = $updates[$key];
            unset($updates[$key]);
            $options = $update['options'];
            $seq_start = $options['seq_start'];
            $seq_end = $options['seq_end'];
            $result = $this->state->checkSeq($seq_start);
            if ($result > 0) {
                $this->API->logger->logger('Seq hole. seq_start: '.$seq_start.' != cur seq: '.($this->state->seq() + 1), \danog\MadelineProto\Logger::ERROR);
                yield $this->pause(1000);
                if (!$this->incomingUpdates) {
                    yield $this->API->updaters[UpdateLoop::GENERIC]->resume();
                }
                $this->incomingUpdates = \array_merge($this->incomingUpdates, [$update], $updates);
                continue;
            }
            if ($result < 0) {
                $this->API->logger->logger('Seq too old. seq_start: '.$seq_start.' != cur seq: '.($this->state->seq() + 1), \danog\MadelineProto\Logger::ERROR);
                continue;
            }
            $this->state->seq($seq_end);
            if (isset($options['date'])) {
                $this->state->date($options['date']);
            }
            yield from $this->save($update);
        }
    }
    /**
     * @param (array|mixed)[] $updates
     */
    public function feed(array $updates): void
    {
        $this->API->logger->logger('Was fed updates of type '.$updates['_'].'...', \danog\MadelineProto\Logger::VERBOSE);
        $this->incomingUpdates[] = $updates;
    }
    /**
     * @var array{updates: array} $updates
     */
    public function save(array $updates): \Generator
    {
        $this->pendingWakeups += (yield from $this->feeder->feed($updates['updates']));
    }
    /**
     * @param true[] $wakeups
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
