<?php

declare(strict_types=1);

/**
 * Update loop.
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
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\PTSException;
use danog\MadelineProto\RPCErrorException;

use function Amp\delay;

/**
 * Update loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class UpdateLoop extends Loop
{
    use InternalLoop {
        __construct as private init;
    }
    /**
     * Main loop ID.
     */
    const GENERIC = 0;

    private const DEFAULT_TIMEOUT = 10.0;

    private ?int $toPts = null;
    /**
     * Loop name.
     */
    private int $channelId;
    /**
     * Feed loop.
     */
    private FeedLoop $feeder;
    private bool $first = true;
    /**
     * Constructor.
     */
    public function __construct(MTProto $API, int $channelId)
    {
        $this->init($API);
        $this->channelId = $channelId;
    }
    /**
     * Main loop.
     */
    public function loop(): ?float
    {
        if (!$this->API->hasAllAuth()) {
            return self::PAUSE;
        }
        $this->feeder = $this->API->feeders[$this->channelId];
        $state = $this->channelId === self::GENERIC ? $this->API->loadUpdateState() : $this->API->loadChannelState($this->channelId);

        $result = [];
        $toPts = $this->toPts;
        $this->toPts = null;
        while (true) {
            if ($this->channelId) {
                $this->logger->logger('Resumed and fetching '.$this->channelId.' difference...', Logger::ULTRA_VERBOSE);
                if ($state->pts() <= 1) {
                    $limit = 10;
                } elseif ($this->API->authorization['user']['bot']) {
                    $limit = 100000;
                } else {
                    $limit = 100;
                }
                $request_pts = $state->pts();
                try {
                    $difference = $this->API->methodCallAsyncRead('updates.getChannelDifference', ['channel' => $this->API->toSupergroup($this->channelId), 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $request_pts, 'limit' => $limit, 'force' => true], ['postpone' => $this->first, 'FloodWaitLimit' => 86400]);
                } catch (RPCErrorException $e) {
                    if ($e->rpc === '-503') {
                        delay(1.0);
                        continue;
                    }
                    if (\in_array($e->rpc, ['CHANNEL_PRIVATE', 'CHAT_FORBIDDEN', 'CHANNEL_INVALID', 'USER_BANNED_IN_CHANNEL'], true)) {
                        $this->feeder->stop();
                        unset($this->API->updaters[$this->channelId], $this->API->feeders[$this->channelId]);
                        $this->API->getChannelStates()->remove($this->channelId);
                        $this->logger->logger("Channel private, exiting {$this}");
                        return self::STOP;
                    }
                    throw $e;
                } catch (PeerNotInDbException) {
                    $this->feeder->stop();
                    $this->API->getChannelStates()->remove($this->channelId);
                    unset($this->API->updaters[$this->channelId], $this->API->feeders[$this->channelId]);
                    $this->logger->logger("Channel private, exiting {$this}");
                    return self::STOP;
                } catch (PTSException $e) {
                    $this->feeder->stop();
                    $this->API->getChannelStates()->remove($this->channelId);
                    unset($this->API->updaters[$this->channelId], $this->API->feeders[$this->channelId]);
                    $this->logger->logger("Got PTS exception, exiting update loop for $this: $e", Logger::FATAL_ERROR);
                    return self::STOP;
                }
                $timeout = \min(self::DEFAULT_TIMEOUT, $difference['timeout'] ?? self::DEFAULT_TIMEOUT);
                $this->logger->logger('Got '.$difference['_'], Logger::ULTRA_VERBOSE);
                switch ($difference['_']) {
                    case 'updates.channelDifferenceEmpty':
                        $state->update($difference);
                        unset($difference);
                        break 2;
                    case 'updates.channelDifference':
                        if ($request_pts >= $difference['pts'] && $request_pts > 1) {
                            $this->logger->logger("The PTS ({$difference['pts']}) I got with getDifference is smaller than the PTS I requested ".$state->pts().', using '.($state->pts() + 1), Logger::VERBOSE);
                            $difference['pts'] = $request_pts + 1;
                        }
                        $result += ($this->feeder->feed($difference['other_updates']));
                        $state->update($difference);
                        $this->feeder->saveMessages($difference['new_messages']);
                        if (!$difference['final']) {
                            if ($difference['pts'] >= $toPts) {
                                unset($difference);
                                break 2;
                            }
                            unset($difference);
                            break;
                        }
                        unset($difference);
                        break 2;
                    case 'updates.channelDifferenceTooLong':
                        if (isset($difference['dialog']['pts'])) {
                            $difference['pts'] = $difference['dialog']['pts'];
                        }
                        $state->update($difference);
                        $this->feeder->saveMessages($difference['messages']);
                        unset($difference);
                        break;
                    default:
                        throw new Exception('Unrecognized update difference received: '.\var_export($difference, true));
                }
            } else {
                $this->logger->logger('Resumed and fetching normal difference...', Logger::ULTRA_VERBOSE);
                do {
                    try {
                        $difference = $this->API->methodCallAsyncRead('updates.getDifference', ['pts' => $state->pts(), 'date' => $state->date(), 'qts' => $state->qts()], ['datacenter' => $this->API->authorized_dc]);
                        break;
                    } catch (RPCErrorException $e) {
                        if ($e->rpc !== '-503') {
                            throw $e;
                        }
                    }
                } while (true);
                $this->logger->logger('Got '.$difference['_'], Logger::ULTRA_VERBOSE);
                $timeout = self::DEFAULT_TIMEOUT;
                switch ($difference['_']) {
                    case 'updates.differenceEmpty':
                        $state->update($difference);
                        unset($difference);
                        break 2;
                    case 'updates.difference':
                        $state->qts($difference['state']['qts']);
                        foreach ($difference['new_encrypted_messages'] as &$encrypted) {
                            $encrypted = ['_' => 'updateNewEncryptedMessage', 'message' => $encrypted];
                        }
                        $result += ($this->feeder->feed($difference['other_updates']));
                        $result += ($this->feeder->feed($difference['new_encrypted_messages']));
                        $state->update($difference['state']);
                        $this->feeder->saveMessages($difference['new_messages']);
                        unset($difference);
                        break 2;
                    case 'updates.differenceSlice':
                        $state->qts($difference['intermediate_state']['qts']);
                        foreach ($difference['new_encrypted_messages'] as &$encrypted) {
                            $encrypted = ['_' => 'updateNewEncryptedMessage', 'message' => $encrypted];
                        }
                        $result += ($this->feeder->feed($difference['other_updates']));
                        $result += ($this->feeder->feed($difference['new_encrypted_messages']));
                        $state->update($difference['intermediate_state']);
                        $this->feeder->saveMessages($difference['new_messages']);
                        if ($difference['intermediate_state']['pts'] >= $toPts) {
                            unset($difference);
                            break 2;
                        }
                        unset($difference);
                        break;
                    case 'updates.differenceTooLong':
                        $state->update($difference);
                        unset($difference);
                        break;
                    default:
                        throw new Exception('Unrecognized update difference received: '.\var_export($difference, true));
                }
            }
        }
        $this->logger->logger("Finished parsing updates in {$this}, now resuming feeders", Logger::ULTRA_VERBOSE);
        foreach ($result as $channelId => $_) {
            $this->API->feeders[$channelId]?->resume();
        }
        $this->logger->logger("Finished parsing updates in {$this}, pausing for $timeout seconds", Logger::ULTRA_VERBOSE);
        $this->first = false;

        return $timeout;
    }
    public function setLimit(int $toPts): void
    {
        $this->toPts = $toPts;
    }
    /**
     * Get loop name.
     */
    public function __toString(): string
    {
        return $this->channelId ?
            "getUpdate loop channel {$this->channelId}" :
            'getUpdate loop generic';
    }
}
