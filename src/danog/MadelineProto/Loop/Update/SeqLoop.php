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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Update;

use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

/**
 * update feed loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class SeqLoop extends ResumableSignalLoop
{
    use \danog\MadelineProto\Tools;
    private $incomingUpdates = [];
    private $feeder;
    private $pendingWakeups = [];

    public function __construct($API)
    {
        $this->API = $API;
    }

    public function loop()
    {
        $API = $this->API;
        $this->feeder = $API->feeders[false];

        if (!$this->API->settings['updates']['handle_updates']) {
            return false;
        }

        while (!$this->API->settings['updates']['handle_updates'] || !$API->hasAllAuth()) {
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
        }
        $this->state = yield $API->loadUpdateState();

        while (true) {
            while (!$this->API->settings['updates']['handle_updates'] || !$API->hasAllAuth()) {
                if (yield $this->waitSignal($this->pause())) {
                    return;
                }
            }
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
            if (!$this->API->settings['updates']['handle_updates']) {
                return;
            }
            while ($this->incomingUpdates) {
                $updates = $this->incomingUpdates;
                $this->incomingUpdates = [];
                yield $this->parse($updates);
                $updates = null;
            }
            while ($this->pendingWakeups) {
                \reset($this->pendingWakeups);
                $channelId = \key($this->pendingWakeups);
                unset($this->pendingWakeups[$channelId]);
                $this->API->feeders[$channelId]->resume();
            }
        }
    }

    public function parse($updates)
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
                yield $this->pause(1.0);
                if (!$this->incomingUpdates) {
                    yield $this->API->updaters[false]->resume();
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

            yield $this->save($update);
        }
    }

    public function feed($updates)
    {
        $this->API->logger->logger('Was fed updates of type '.$updates['_'].'...', \danog\MadelineProto\Logger::VERBOSE);

        $this->incomingUpdates[] = $updates;
    }

    public function save($updates)
    {
        $this->pendingWakeups += yield $this->feeder->feed($updates['updates']);
    }

    public function addPendingWakeups($wakeups)
    {
        $this->pendingWakeups += $wakeups;
    }


    public function __toString(): string
    {
        return 'update seq loop';
    }
}
