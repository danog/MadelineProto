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

use danog\Loop\ResumableSignalLoop;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\AuthLoop;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\PTSException;
use danog\MadelineProto\RPCErrorException;

use function Amp\async;

/**
 * Update loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class UpdateLoop extends ResumableSignalLoop
{
    use InternalLoop {
        __construct as private init;
    }
    use AuthLoop;
    /**
     * Main loop ID.
     */
    const GENERIC = 0;

    private ?int $toPts = null;
    /**
     * Loop name.
     */
    private int $channelId;
    /**
     * Feed loop.
     */
    private ?FeedLoop $feeder = null;
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
    public function loop(): void
    {
        $API = $this->API;
        $feeder = $this->feeder = $API->feeders[$this->channelId];
        if ($this->waitForAuthOrSignal()) {
            return;
        }
        $state = $this->channelId === self::GENERIC ? $API->loadUpdateState() : $API->loadChannelState($this->channelId);
        $first = true;
        while (true) {
            if ($this->waitForAuthOrSignal(false)) {
                return;
            }
            $result = [];
            $toPts = $this->toPts;
            $this->toPts = null;
            while (true) {
                if ($this->channelId) {
                    $API->logger->logger('Resumed and fetching '.$this->channelId.' difference...', Logger::ULTRA_VERBOSE);
                    if ($state->pts() <= 1) {
                        $limit = 10;
                    } elseif ($API->authorization['user']['bot']) {
                        $limit = 100000;
                    } else {
                        $limit = 100;
                    }
                    $request_pts = $state->pts();
                    try {
                        $difference = $API->methodCallAsyncRead('updates.getChannelDifference', ['channel' => $this->API->toSupergroup($this->channelId), 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $request_pts, 'limit' => $limit, 'force' => true], ['datacenter' => $API->datacenter->currentDatacenter, 'postpone' => $first]);
                    } catch (RPCErrorException $e) {
                        if (\in_array($e->rpc, ['CHANNEL_PRIVATE', 'CHAT_FORBIDDEN', 'CHANNEL_INVALID', 'USER_BANNED_IN_CHANNEL'])) {
                            $feeder->signal(true);
                            unset($API->updaters[$this->channelId], $API->feeders[$this->channelId]);
                            $API->getChannelStates()->remove($this->channelId);
                            $API->logger->logger("Channel private, exiting {$this}");
                            return;
                        }
                        throw $e;
                    } catch (Exception $e) {
                        if (\in_array($e->getMessage(), ['This peer is not present in the internal peer database'])) {
                            $feeder->signal(true);
                            $API->getChannelStates()->remove($this->channelId);
                            unset($API->updaters[$this->channelId], $API->feeders[$this->channelId]);
                            $API->logger->logger("Channel private, exiting {$this}");
                            return;
                        }
                        throw $e;
                    } catch (PTSException $e) {
                        $feeder->signal(true);
                        $API->getChannelStates()->remove($this->channelId);
                        unset($API->updaters[$this->channelId], $API->feeders[$this->channelId]);
                        $API->logger->logger("Got PTS exception, exiting update loop for $this: $e", Logger::FATAL_ERROR);
                        return;
                    }
                    $timeout = min(1, $difference['timeout'] ?? 1);
                    $API->logger->logger('Got '.$difference['_'], Logger::ULTRA_VERBOSE);
                    switch ($difference['_']) {
                        case 'updates.channelDifferenceEmpty':
                            $state->update($difference);
                            unset($difference);
                            break 2;
                        case 'updates.channelDifference':
                            if ($request_pts >= $difference['pts'] && $request_pts > 1) {
                                $API->logger->logger("The PTS ({$difference['pts']}) I got with getDifference is smaller than the PTS I requested ".$state->pts().', using '.($state->pts() + 1), Logger::VERBOSE);
                                $difference['pts'] = $request_pts + 1;
                            }
                            $result += ($feeder->feed($difference['other_updates']));
                            $state->update($difference);
                            $feeder->saveMessages($difference['new_messages']);
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
                            $feeder->saveMessages($difference['messages']);
                            unset($difference);
                            break;
                        default:
                            throw new Exception('Unrecognized update difference received: '.\var_export($difference, true));
                    }
                } else {
                    $API->logger->logger('Resumed and fetching normal difference...', Logger::ULTRA_VERBOSE);
                    $difference = $API->methodCallAsyncRead('updates.getDifference', ['pts' => $state->pts(), 'date' => $state->date(), 'qts' => $state->qts()], ['datacenter' => $API->authorized_dc]);
                    $API->logger->logger('Got '.$difference['_'], Logger::ULTRA_VERBOSE);
                    $timeout = 1;
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
                            $result += ($feeder->feed($difference['other_updates']));
                            $result += ($feeder->feed($difference['new_encrypted_messages']));
                            $state->update($difference['state']);
                            $feeder->saveMessages($difference['new_messages']);
                            unset($difference);
                            break 2;
                        case 'updates.differenceSlice':
                            $state->qts($difference['intermediate_state']['qts']);
                            foreach ($difference['new_encrypted_messages'] as &$encrypted) {
                                $encrypted = ['_' => 'updateNewEncryptedMessage', 'message' => $encrypted];
                            }
                            $result += ($feeder->feed($difference['other_updates']));
                            $result += ($feeder->feed($difference['new_encrypted_messages']));
                            $state->update($difference['intermediate_state']);
                            $feeder->saveMessages($difference['new_messages']);
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
            $API->logger->logger("Finished parsing updates in {$this}, now resuming feeders", Logger::ULTRA_VERBOSE);
            foreach ($result as $channelId => $_) {
                $API->feeders[$channelId]?->resumeDefer();
            }
            $API->logger->logger("Finished parsing updates in {$this}, pausing for $timeout seconds", Logger::ULTRA_VERBOSE);
            $first = false;
            if ($this->waitSignal(async($this->pause(...), (int) ($timeout*1000)))) {
                $API->logger->logger("Exiting {$this} due to signal");
                return;
            }
        }
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
