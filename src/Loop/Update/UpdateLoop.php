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

use Amp\TimeoutException;
use danog\Loop\Loop;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\PTSException;
use danog\MadelineProto\RPCError\ChannelInvalidError;
use danog\MadelineProto\RPCError\ChannelPrivateError;
use danog\MadelineProto\RPCError\ChatForbiddenError;
use danog\MadelineProto\RPCError\TimeoutError;
use danog\MadelineProto\RPCError\UserBannedInChannelError;
use Revolt\EventLoop;

use function Amp\delay;

/**
 * Update loop.
 *
 * @internal
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
    public const GENERIC = 0;

    private const DEFAULT_TIMEOUT = 10.0;

    private ?int $toPts = null;
    /**
     * Feed loop.
     */
    private ?FeedLoop $feeder = null;
    /**
     * Constructor.
     */
    public function __construct(MTProto $API, private int $channelId)
    {
        $this->init($API);
    }
    public function __sleep(): array
    {
        return ['channelId', 'API', 'feeder'];
    }
    /**
     * Main loop.
     */
    public function loop(): ?float
    {
        if (!$this->isLoggedIn()) {
            return self::PAUSE;
        }
        $this->feeder = $this->API->feeders[$this->channelId];
        $state = $this->channelId === self::GENERIC ? $this->API->loadUpdateState() : $this->API->loadChannelState($this->channelId);

        $result = [];
        $toPts = $this->toPts;
        $this->toPts = null;
        while (true) {
            if ($this->channelId) {
                $this->API->logger('Resumed and fetching '.$this->channelId.' difference...', Logger::ULTRA_VERBOSE);
                if ($state->pts() <= 1) {
                    $limit = 10;
                } elseif ($this->API->authorization['user']['bot']) {
                    $limit = 100000;
                } else {
                    $limit = 100;
                }
                $request_pts = $state->pts();
                try {
                    $difference = $this->API->methodCallAsyncRead('updates.getChannelDifference', ['channel' => $this->channelId, 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $request_pts, 'limit' => $limit, 'force' => true, 'floodWaitLimit' => 86400]);
                } catch (ChannelPrivateError|ChatForbiddenError|ChannelInvalidError|UserBannedInChannelError) {
                    $this->feeder->stop();
                    unset($this->API->updaters[$this->channelId], $this->API->feeders[$this->channelId]);
                    $this->API->getChannelStates()->remove($this->channelId);
                    $this->API->logger("Channel private, exiting {$this}");
                    return self::STOP;
                } catch (TimeoutError $e) {
                    delay(1.0);
                    continue;
                } catch (PeerNotInDbException) {
                    $this->feeder->stop();
                    $this->API->getChannelStates()->remove($this->channelId);
                    unset($this->API->updaters[$this->channelId], $this->API->feeders[$this->channelId]);
                    $this->API->logger("Channel private, exiting {$this}");
                    return self::STOP;
                } catch (PTSException $e) {
                    $this->feeder->stop();
                    $this->API->getChannelStates()->remove($this->channelId);
                    unset($this->API->updaters[$this->channelId], $this->API->feeders[$this->channelId]);
                    $this->API->logger("Got PTS exception, exiting update loop for $this: $e", Logger::FATAL_ERROR);
                    return self::STOP;
                } catch (TimeoutException) {
                    EventLoop::queue($this->API->report(...), "Network issues detected, please check logs!");
                    continue;
                }
                $timeout = min(self::DEFAULT_TIMEOUT, $difference['timeout'] ?? self::DEFAULT_TIMEOUT);
                $this->API->logger('Got '.$difference['_'], Logger::ULTRA_VERBOSE);
                switch ($difference['_']) {
                    case 'updates.channelDifferenceEmpty':
                        $state->update($difference);
                        unset($difference);
                        break 2;
                    case 'updates.channelDifference':
                        if ($request_pts >= $difference['pts'] && $request_pts > 1) {
                            $this->API->logger("The PTS ({$difference['pts']}) I got with getDifference is smaller than the PTS I requested ".$state->pts().', using '.($state->pts() + 1), Logger::VERBOSE);
                            $difference['pts'] = $request_pts + 1;
                        }
                        $result += ($this->feeder->feed($difference['other_updates']));
                        $state->update($difference);
                        if ($difference['new_messages']) {
                            $result[$this->channelId] = true;
                        }
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
                        if ($difference['messages']) {
                            $result[$this->channelId] = true;
                        }
                        $this->feeder->saveMessages($difference['messages']);
                        unset($difference);
                        break;
                    default:
                        throw new Exception('Unrecognized update difference received: '.var_export($difference, true));
                }
            } else {
                $this->API->logger('Resumed and fetching normal difference...', Logger::ULTRA_VERBOSE);
                do {
                    try {
                        $difference = $this->API->methodCallAsyncRead('updates.getDifference', ['pts' => $state->pts(), 'date' => $state->date(), 'qts' => $state->qts()], $this->API->authorized_dc);
                        break;
                    } catch (TimeoutError) {
                        delay(1.0);
                    } catch (TimeoutException) {
                        EventLoop::queue($this->API->report(...), "Network issues detected, please check logs!");
                    }
                } while (true);
                $this->API->logger('Got '.$difference['_'], Logger::ULTRA_VERBOSE);
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
                        if ($difference['new_messages']) {
                            $result[$this->channelId] = true;
                        }
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
                        if ($difference['new_messages']) {
                            $result[$this->channelId] = true;
                        }
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
                        throw new Exception('Unrecognized update difference received: '.var_export($difference, true));
                }
            }
        }
        $this->API->logger("Finished parsing updates in {$this}, now resuming feeders", Logger::ULTRA_VERBOSE);
        foreach ($result as $channelId => $_) {
            $this->API->feeders[$channelId]?->resume();
        }
        $this->API->logger("Finished parsing updates in {$this}, pausing for $timeout seconds", Logger::ULTRA_VERBOSE);

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
