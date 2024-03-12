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
use danog\MadelineProto\AsyncTools;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\UpdatesState;

/**
 * Update feed loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class FeedLoop extends Loop
{
    use InternalLoop {
        __construct as private init;
    }
    /**
     * Main loop ID.
     */
    public const GENERIC = 0;
    /**
     * Incoming updates array.
     */
    private array $incomingUpdates = [];
    /**
     * Parsed updates array.
     */
    private array $parsedUpdates = [];
    /**
     * Update loop.
     */
    private ?UpdateLoop $updater = null;
    /**
     * Update state.
     */
    private ?UpdatesState $state = null;
    /**
     * Constructor.
     */
    public function __construct(MTProto $API, private int $channelId = 0)
    {
        $this->init($API);
    }
    public function __sleep(): array
    {
        return ['incomingUpdates', 'parsedUpdates', 'updater', 'API', 'state', 'channelId'];
    }
    /**
     * Main loop.
     */
    public function loop(): ?float
    {
        if (!$this->isLoggedIn()) {
            return self::PAUSE;
        }
        $this->updater = $this->API->updaters[$this->channelId];
        $this->state = $this->channelId === self::GENERIC ? $this->API->loadUpdateState() : $this->API->loadChannelState($this->channelId);

        $this->API->logger("Resumed {$this}");
        while ($this->incomingUpdates) {
            $updates = $this->incomingUpdates;
            $this->incomingUpdates = [];
            $this->parse($updates);
            $updates = null;
        }
        while ($this->parsedUpdates) {
            $parsedUpdates = $this->parsedUpdates;
            $this->parsedUpdates = [];
            foreach ($parsedUpdates as $update) {
                try {
                    $this->API->saveUpdate($update);
                } catch (\Throwable $e) {
                    AsyncTools::rethrow($e);
                }
            }
            $parsedUpdates = null;
        }
        return self::PAUSE;
    }
    public function parse(array $updates): void
    {
        reset($updates);
        while ($updates) {
            $key = key($updates);
            $update = $updates[$key];
            unset($updates[$key]);
            if ($update['_'] === 'updateChannelTooLong') {
                $this->API->logger('Got channel too long update, getting difference...', Logger::VERBOSE);
                $this->updater->resume();
                continue;
            }
            if (isset($update['pts'], $update['pts_count'])) {
                $logger = function ($msg) use ($update): void {
                    $pts_count = $update['pts_count'];
                    $mid = $update['message']['id'] ?? '-';
                    $mypts = $this->state->pts();
                    $computed = $mypts + $pts_count;
                    $this->API->logger("{$msg}. My pts: {$mypts}, remote pts: {$update['pts']}, computed pts: {$computed}, msg id: {$mid}, channel id: {$this->channelId}", Logger::ULTRA_VERBOSE);
                };
                $result = $this->state->checkPts($update);
                if ($result < 0) {
                    $logger('PTS duplicate');
                    continue;
                }
                if ($result > 0) {
                    $logger('PTS hole');
                    //$this->updater->setLimit($this->state->pts() + $result);
                    $this->updater->resume();
                    //$updates = array_merge($this->incomingUpdates, $updates);
                    //$this->incomingUpdates = [];
                    continue;
                }
                if (isset($update['message']['id'], $update['message']['peer_id']) && !\in_array($update['_'], ['updateEditMessage', 'updateEditChannelMessage', 'updateMessageID'], true)) {
                    if (!$this->API->checkMsgId($update['message'])) {
                        $logger('MSGID duplicate');
                        continue;
                    }
                }
                $logger('PTS OK');
                $this->state->pts($update['pts']);
            }

            $this->parsedUpdates[] = $update;
        }
    }
    public function feed(array $updates)
    {
        $result = [];
        foreach ($updates as $update) {
            $result[$this->feedSingle($update)] = true;
        }
        return $result;
    }
    public function feedSingle(array $update)
    {
        $channelId = self::GENERIC;
        switch ($update['_']) {
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                $channelId = $update['message']['peer_id'];
                break;
            case 'updateChannelWebPage':
            case 'updateDeleteChannelMessages':
                $channelId = $update['channel_id'];
                break;
            case 'updateChannelTooLong':
            case 'updateChannel':
                $channelId = $update['channel_id'] ?? self::GENERIC;
                if (!isset($update['pts'])) {
                    $update['pts'] = 1;
                }
                break;
        }
        if ($channelId && !$this->API->getChannelStates()->has($channelId)) {
            $this->API->loadChannelState($channelId, $update);
            if (!isset($this->API->feeders[$channelId])) {
                $this->API->feeders[$channelId] = new self($this->API, $channelId);
            }
            if (!isset($this->API->updaters[$channelId])) {
                $this->API->updaters[$channelId] = new UpdateLoop($this->API, $channelId);
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
                if ($update['message']['_'] !== 'messageEmpty' && (
                    (
                        $from = isset($update['message']['from_id'])
                        && !($this->API->peerIsset($update['message']['from_id']))
                    ) || (
                        $to = !($this->API->peerIsset($update['message']['peer_id']))
                    )
                        || (
                            $via_bot = isset($update['message']['via_bot_id'])
                            && !($this->API->peerIsset($update['message']['via_bot_id']))
                        ) || (
                            $entities = isset($update['message']['entities'])
                            && !($this->API->entitiesPeerIsset($update['message']['entities']))
                        )
                )
                ) {
                    $log = '';
                    if ($from) {
                        $from_id = $this->API->getIdInternal($update['message']['from_id']);
                        $log .= "from_id {$from_id}, ";
                    }
                    if ($to) {
                        $log .= 'peer_id '.json_encode($update['message']['peer_id']).', ';
                    }
                    if ($via_bot) {
                        $log .= "via_bot {$update['message']['via_bot_id']}, ";
                    }
                    if ($entities) {
                        $log .= 'entities '.json_encode($update['message']['entities']).', ';
                    }
                    $this->API->logger("Not enough data: for message update {$log}, getting difference...", Logger::VERBOSE);
                    $update = ['_' => 'updateChannelTooLong'];
                    if ($channelId && $to) {
                        $channelId = self::GENERIC;
                    }
                }
                break;
            default:
                if ($channelId && !$this->API->peerIsset($channelId)) {
                    $this->API->logger('Skipping update, I do not have the channel id '.$channelId, Logger::ERROR);
                    return false;
                }
                break;
        }
        if ($channelId !== $this->channelId) {
            if (isset($this->API->feeders[$channelId])) {
                return $this->API->feeders[$channelId]->feedSingle($update);
            } elseif ($this->channelId) {
                return $this->API->feeders[self::GENERIC]->feedSingle($update);
            }
        }
        $this->API->logger('Was fed an update of type '.$update['_']." in {$this}...", Logger::ULTRA_VERBOSE);
        if ($update['_'] === 'updateLoginToken') {
            $this->API->saveUpdate($update);
            return $this->channelId;
        }
        $this->incomingUpdates[] = $update;
        return $this->channelId;
    }
    public function saveMessages($messages): void
    {
        foreach ($messages as $message) {
            if (!$this->API->checkMsgId($message)) {
                $this->API->logger("MSGID duplicate ({$message['id']}) in {$this}");
                continue;
            }
            if ($message['_'] !== 'messageEmpty') {
                $this->API->logger('Getdiff fed me message of type '.$message['_']." in {$this}...", Logger::VERBOSE);
            }
            $this->parsedUpdates[] = ['_' => $this->channelId === self::GENERIC ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => -1, 'pts_count' => -1];
        }
    }
    public function __toString(): string
    {
        return !$this->channelId ? 'update feed loop generic' : "update feed loop channel {$this->channelId}";
    }
}
