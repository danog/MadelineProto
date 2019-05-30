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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Update;

use Amp\Loop;
use Amp\Success;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

/**
 * update feed loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class FeedLoop extends ResumableSignalLoop
{
    use \danog\MadelineProto\Tools;
    private $incomingUpdates = [];
    private $parsedUpdates = [];
    private $channelId;
    private $updater;

    public function __construct($API, $channelId = false)
    {
        $this->API = $API;
        $this->channelId = $channelId;
    }
    public function loop()
    {
        $API = $this->API;
        $updater = $this->updater = $API->updaters[$this->channelId];

        if (!$this->API->settings['updates']['handle_updates']) {
            yield new Success(0);

            return false;
        }

        $this->startedLoop();
        $API->logger->logger("Entered $this", Logger::ULTRA_VERBOSE);
        while (!$this->API->settings['updates']['handle_updates'] || !$this->has_all_auth()) {
            if (yield $this->waitSignal($this->pause())) {
                $API->logger->logger("Exiting $this");
                $this->exitedLoop();

                return;
            }
        }
        $this->state = $this->channelId === false ? (yield $API->load_update_state_async()) : $API->loadChannelState($this->channelId);

        while (true) {
            while (!$this->API->settings['updates']['handle_updates'] || !$this->has_all_auth()) {
                if (yield $this->waitSignal($this->pause())) {
                    $API->logger->logger("Exiting $this");
                    $this->exitedLoop();

                    return;
                }
            }
            if (yield $this->waitSignal($this->pause())) {
                $API->logger->logger("Exiting $this");
                $this->exitedLoop();

                return;
            }
            if (!$this->API->settings['updates']['handle_updates']) {
                $API->logger->logger("Exiting $this");
                $this->exitedLoop();
                return;
            }
            $API->logger->logger("Resumed $this");
            while ($this->incomingUpdates) {
                $updates = $this->incomingUpdates;
                $this->incomingUpdates = [];
                yield $this->parse($updates);
                $updates = null;
            }
            if ($this->parsedUpdates) {
                foreach ($this->parsedUpdates as $update) {
                    yield $API->save_update_async($update);
                }
                $this->parsedUpdates = [];
                if ($API->update_deferred) {
                    $API->logger->logger("Resuming deferred in $this", Logger::VERBOSE);
                    $API->update_deferred->resolve();
                    $API->logger->logger("Done resuming deferred in $this", Logger::VERBOSE);
                }
            }

        }
    }
    public function parse($updates)
    {
        reset($updates);
        while ($updates) {
            $key = key($updates);
            $update = $updates[$key];
            unset($updates[$key]);
            if ($update['_'] === 'updateChannelTooLong') {
                $this->API->logger->logger('Got channel too long update, getting difference...', \danog\MadelineProto\Logger::VERBOSE);
                $this->API->updaters[$this->channelId]->resume();

                continue;
            }
            if (isset($update['pts'])) {
                $logger = function ($msg) use ($update) {
                    $pts_count = $update['pts_count'];
                    $this->API->logger->logger($update);
                    $double = isset($update['message']['id']) ? $update['message']['id'] * 2 : '-';
                    $mid = isset($update['message']['id']) ? $update['message']['id'] : '-';
                    $mypts = $this->state->pts();
                    $computed = $mypts + $pts_count;
                    $this->API->logger->logger("$msg. My pts: {$mypts}, remote pts: {$update['pts']}, computed pts: $computed, msg id: {$mid} (*2=$double), channel id: {$this->channelId}", \danog\MadelineProto\Logger::ERROR);
                };
                $result = $this->state->checkPts($update);
                if ($result < 0) {
                    $logger("PTS duplicate");

                    continue;
                }
                if ($result > 0) {
                    $logger("PTS hole");
                    $this->updater->setLimit($this->state->pts() + $result);
                    yield $this->updater->resume();
                    $updates = array_merge($this->incomingUpdates, $updates);
                    $this->incomingUpdates = [];
                    continue;
                }
                if (isset($update['message']['id'], $update['message']['to_id']) && !in_array($update['_'], ['updateEditMessage', 'updateEditChannelMessage'])) {
                    if (!$this->API->check_msg_id($update['message'])) {
                        $logger("MSGID duplicate");

                        continue;
                    }
                }
                $logger("PTS OK");

                $this->state->pts($update['pts']);

            }

            $this->save($update);
        }
    }
    public function feed($updates)
    {
        $result = [];
        foreach ($updates as $update) {
            $res = $this->feedSingle($update);
            if ($res instanceof \Generator) {
                $res = yield $res;
            }
            $result[$res] = true;
        }
        return $result;
    }
    public function feedSingle($update)
    {
        $channelId = false;
        switch ($update['_']) {
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                $channelId = $update['message']['to_id']['channel_id'];
                break;
            case 'updateChannelWebPage':
            case 'updateDeleteChannelMessages':
                $channelId = $update['channel_id'];
                break;
            case 'updateChannelTooLong':
                $channelId = $update['channel_id'];
                if (!isset($update['pts'])) {
                    $update['pts'] = 1;
                }
                break;
        }

        if ($channelId && !$this->API->getChannelStates()->has($channelId)) {
            $this->API->loadChannelState($channelId, $update);
            if (!isset($this->API->feeders[$channelId])) {
                $this->API->feeders[$channelId] = new FeedLoop($this, $channelId);
            }
            if (!isset($this->API->updaters[$channelId])) {
                $this->API->updaters[$channelId] = new UpdateLoop($this, $channelId);
            }
            $this->API->feeders[$channelId]->start();
            $this->API->updaters[$channelId]->start();
        }

        switch ($update['_']) {
            case 'updateNewMessage':
            case 'updateEditMessage':
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                $to = false;
                $from = false;
                $via_bot = false;
                $entities = false;
                if (($from = isset($update['message']['from_id']) && !yield $this->API->peer_isset_async($update['message']['from_id'])) ||
                    ($to = !yield $this->API->peer_isset_async($update['message']['to_id'])) ||
                    ($via_bot = isset($update['message']['via_bot_id']) && !yield $this->API->peer_isset_async($update['message']['via_bot_id'])) ||
                    ($entities = isset($update['message']['entities']) && !yield $this->API->entities_peer_isset_async($update['message']['entities'])) // ||
                    //isset($update['message']['fwd_from']) && !yield $this->fwd_peer_isset_async($update['message']['fwd_from'])
                ) {
                    $log = '';
                    if ($from) {
                        $log .= "from_id {$update['message']['from_id']}, ";
                    }

                    if ($to) {
                        $log .= "to_id ".json_encode($update['message']['to_id']).", ";
                    }

                    if ($via_bot) {
                        $log .= "via_bot {$update['message']['via_bot_id']}, ";
                    }

                    if ($entities) {
                        $log .= "entities ".json_encode($update['message']['entities']).", ";
                    }

                    $this->API->logger->logger("Not enough data: for message update $log, getting difference...", \danog\MadelineProto\Logger::VERBOSE);
                    $update = ['_' => 'updateChannelTooLong'];
                }
                break;
            default:
                if ($channelId !== false && !yield $this->API->peer_isset_async($this->API->to_supergroup($channelId))) {
                    $this->API->logger->logger('Skipping update, I do not have the channel id '.$channelId, \danog\MadelineProto\Logger::ERROR);

                    return;
                }
                break;
        }
        if ($channelId !== $this->channelId) {
            return yield $this->API->feeders[$channelId]->feedSingle($update);
        }

        $this->API->logger->logger('Was fed an update of type '.$update['_']." in $this...", \danog\MadelineProto\Logger::VERBOSE);
        $this->incomingUpdates[] = $update;
        return $this->channelId;
    }
    public function save($update)
    {
        $this->parsedUpdates[] = $update;
    }
    public function saveMessages($messages)
    {
        foreach ($messages as $message) {
            if (!$this->API->check_msg_id($message)) {
                $this->API->logger->logger("MSGID duplicate ({$message['id']}) in $this");

                continue;
            }

            $this->parsedUpdates[] = ['_' => $this->channelId === false ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => -1, 'pts_count' => -1];
        }
    }

    public function has_all_auth()
    {
        if ($this->API->isInitingAuthorization()) {
            return false;
        }

        foreach ($this->API->datacenter->sockets as $dc) {
            if (!$dc->authorized || $dc->temp_auth_key === null) {
                return false;
            }
        }

        return true;
    }

    public function __toString(): string
    {
        return !$this->channelId ? "update feed loop generic" : "update feed loop channel {$this->channelId}";
    }
}
