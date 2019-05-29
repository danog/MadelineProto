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

use Amp\Success;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;
use Amp\Loop;

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
        $API->logger->logger("Entered update feed loop in channel {$this->channelId}", Logger::ULTRA_VERBOSE);
        while (!$this->API->settings['updates']['handle_updates'] || !$this->has_all_auth()) {
            if (yield $this->waitSignal($this->pause())) {
                $API->logger->logger("Exiting update feed loop in channel {$this->channelId}");
                $this->exitedLoop();

                return;
            }
        }
        $this->state = $this->channelId === false ? (yield $API->load_update_state_async()) : $API->loadChannelState($this->channelId);

        while (true) {
            while (!$this->API->settings['updates']['handle_updates'] || !$this->has_all_auth()) {
                if (yield $this->waitSignal($this->pause())) {
                    $API->logger->logger("Exiting update feed loop channel {$this->channelId}");
                    $this->exitedLoop();

                    return;
                }
            }
            if (yield $this->waitSignal($this->pause())) {
                $API->logger->logger("Exiting update feed loop channel {$this->channelId}");
                $this->exitedLoop();

                return;
            }
            if (!$this->API->settings['updates']['handle_updates']) {
                $API->logger->logger("Exiting update feed loop channel {$this->channelId}");
                $this->exitedLoop();
                return;
            }
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
            }
            if ($API->update_deferred) Loop::defer([$API->update_deferred, 'resolve']);
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
                $this->API->updaters[$this->channelId]->resumeDefer();

                continue;
            }
            if (isset($update['pts'])) {
                $logger = function ($msg) use ($update) {
                    $pts_count = isset($update['pts_count']) ? $update['pts_count'] : 0;
                    $this->API->logger->logger($update);
                    $double = isset($update['message']['id']) ? $update['message']['id'] * 2 : '-';
                    $mid = isset($update['message']['id']) ? $update['message']['id'] : '-';
                    $mypts = $this->state->pts();
                    $this->API->logger->logger("$msg. My pts: {$mypts}, remote pts: {$update['pts']}, remote pts count: {$pts_count}, msg id: {$mid} (*2=$double), channel id: {$this->channelId}", \danog\MadelineProto\Logger::ERROR);
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
                    $this->incomingUpdates = null;
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
        $this->incomingUpdates = array_merge($this->incomingUpdates, $updates);
    }
    public function feedSingle($update)
    {
        $this->incomingUpdates []= $update;
    }
    public function save($update)
    {
        $this->parsedUpdates []= $update;
    }
    public function saveMessages($messages)
    {
        foreach ($messages as $message) {
            $this->parsedUpdates []= ['_' => $this->channelId === false ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => -1, 'pts_count' => -1];
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
}
