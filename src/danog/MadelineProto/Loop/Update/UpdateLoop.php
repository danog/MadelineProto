<?php
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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Update;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;
use Amp\Loop;
use danog\MadelineProto\RPCErrorException;

/**
 * Update loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class UpdateLoop extends ResumableSignalLoop
{
    use \danog\MadelineProto\Tools;

    private $toPts;
    private $channelId;
    private $feeder;

    public function __construct($API, $channelId)
    {
        $this->API = $API;
        $this->channelId = $channelId;
    }
    public function loop()
    {
        $API = $this->API;
        $feeder = $this->feeder = $API->feeders[$this->channelId];

        while (!$this->API->settings['updates']['handle_updates'] || !$this->has_all_auth()) {
            if (yield $this->waitSignal($this->pause())) {
                $API->logger->logger("Exiting $this");
                $this->exitedLoop();

                return;
            }
        }
        $this->state = $state = $this->channelId === false ? (yield $API->load_update_state_async()) : $API->loadChannelState($this->channelId);

        $this->startedLoop();

        $API->logger->logger("Entered $this", Logger::ULTRA_VERBOSE);

        $timeout = $API->settings['updates']['getdifference_interval'];
        while (true) {
            while (!$this->API->settings['updates']['handle_updates'] || !$this->has_all_auth()) {
                if (yield $this->waitSignal($this->pause())) {
                    $API->logger->logger("Exiting $this");
                    $this->exitedLoop();

                    return;
                }
            }
            $result = [];
            $toPts = $this->toPts;
            $this->toPts = null;
            while (true) {
                if ($this->channelId) {
                    $this->API->logger->logger('Resumed and fetching '.$this->channelId.' difference...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    if ($state->pts() <= 1) {
                        $limit = 10;
                    } else if ($API->authorization['user']['bot']) {
                        $limit = 100000;
                    } else {
                        $limit = 100;
                    }
                    $request_pts = $state->pts();
                    try {
                        $difference = yield $this->API->method_call_async_read('updates.getChannelDifference', ['channel' => 'channel#'.$this->channelId, 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $request_pts, 'limit' => $limit, 'force' => true], ['datacenter' => $this->API->datacenter->curdc]);
                    } catch (RPCErrorException $e) {
                        if (in_array($e->rpc, ['CHANNEL_PRIVATE', 'CHAT_FORBIDDEN'])) {
                            $feeder->signal(true);
                            $API->logger->logger("Exiting $this");
                            $this->exitedLoop();            
                            return true;
                        }
                        throw $e;            
                    }
                    if (isset($difference['timeout'])) {
                        $timeout = $difference['timeout'];
                    }

                    $this->API->logger->logger('Got '.$difference['_'], \danog\MadelineProto\Logger::VERBOSE);
                    switch ($difference['_']) {
                        case 'updates.channelDifferenceEmpty':
                            $state->update($difference);
                            unset($difference);
                            break 2;
                        case 'updates.channelDifference':
                            if ($request_pts >= $difference['pts'] && $request_pts > 1) {
                                $this->API->logger->logger("The PTS ({$difference['pts']}) I got with getDifference is smaller than the PTS I requested ".$state->pts().", using ".($state->pts() + 1), \danog\MadelineProto\Logger::VERBOSE);
                                $difference['pts'] = $request_pts + 1;
                            }
                            $state->update($difference);
                            $result += yield $feeder->feed($difference['other_updates']);

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
                            $state->update($difference);
                            $feeder->saveMessages($difference['messages']);
                            unset($difference);
                            break;
                        default:
                            throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                    }
                } else {
                    $this->API->logger->logger('Resumed and fetching normal difference...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    $difference = yield $this->API->method_call_async_read('updates.getDifference', ['pts' => $state->pts(), 'date' => $state->date(), 'qts' => $state->qts()], ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    $this->API->logger->logger('Got '.$difference['_'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    switch ($difference['_']) {
                        case 'updates.differenceEmpty':
                            $state->update($difference);
                            unset($difference);
                            break 2;
                        case 'updates.difference':
                            foreach ($difference['new_encrypted_messages'] as &$encrypted) {
                                $encrypted = ['_' => 'updateNewEncryptedMessage', 'message' => $encrypted];
                            }
                            $result += yield $feeder->feed($difference['other_updates']);
                            $result += yield $feeder->feed($difference['new_encrypted_messages']);
                            $feeder->saveMessages($difference['new_messages']);
                            $state->update($difference['state']);
                            unset($difference);
                            break 2;
                        case 'updates.differenceSlice':
                            foreach ($difference['new_encrypted_messages'] as &$encrypted) {
                                $encrypted = ['_' => 'updateNewEncryptedMessage', 'message' => $encrypted];
                            }
                            $result += yield $feeder->feed($difference['other_updates']);
                            $result += yield $feeder->feed($difference['new_encrypted_messages']);
                            $feeder->saveMessages($difference['new_messages']);
                            $state->update($difference['intermediate_state']);
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
                            throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                    }
                }
            }
            foreach ($result as $channelId => $boh) {
                $this->API->feeders[$channelId]->resumeDefer();
            }
            $this->API->signalUpdate();

            if (yield $this->waitSignal($this->pause($timeout))) {
                $API->logger->logger("Exiting $this");
                $this->exitedLoop();

                return;
            }
        }
    }
    public function setLimit($toPts)
    {
        $this->toPts = $toPts;
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
        return !$this->channelId ? "getUpdate loop generic" : "getUpdate loop channel {$this->channelId}";
    }
}
