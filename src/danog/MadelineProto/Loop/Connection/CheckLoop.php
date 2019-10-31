<?php
/**
 * RPC call status check loop.
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

namespace danog\MadelineProto\Loop\Connection;

use Amp\Deferred;
use Amp\Loop;
use danog\MadelineProto\Connection;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;
use danog\MadelineProto\Tools;

/**
 * RPC call status check loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class CheckLoop extends ResumableSignalLoop
{
    /**
     * Connection instance.
     *
     * @var \danog\MadelineProto\Connection
     */
    protected $connection;
    /**
     * DC ID.
     *
     * @var string
     */
    protected $datacenter;

    /**
     * DataCenterConnection instance.
     *
     * @var \danog\MadelineProto\DataCenterConnection
     */
    protected $datacenterConnection;
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->API = $connection->getExtra();
        $this->datacenter = $connection->getDatacenterID();
        $this->datacenterConnection = $connection->getShared();
    }

    public function loop()
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;

        $timeout = $shared->getSettings()['timeout'];
        while (true) {
            while (empty($connection->new_outgoing)) {
                if (yield $this->waitSignal($this->pause())) {
                    return;
                }
            }

            if ($connection->hasPendingCalls()) {
                $last_msgid = $connection->getMaxId(true);
                $last_chunk = $connection->getLastChunk();

                if ($shared->hasTempAuthKey()) {
                    $full_message_ids = $connection->getPendingCalls(); //array_values($connection->new_outgoing);
                    foreach (\array_chunk($full_message_ids, 8192) as $message_ids) {
                        $deferred = new Deferred();
                        $deferred->promise()->onResolve(
                            function ($e, $result) use ($message_ids, $API, $connection, $datacenter) {
                                if ($e) {
                                    $API->logger("Got exception in check loop for DC $datacenter");
                                    $API->logger((string) $e);

                                    return;
                                }
                                $reply = [];
                                foreach (\str_split($result['info']) as $key => $chr) {
                                    $message_id = $message_ids[$key];
                                    if (!isset($connection->outgoing_messages[$message_id])) {
                                        $API->logger->logger('Already got response for and forgot about message ID '.($message_id));
                                        continue;
                                    }
                                    if (!isset($connection->new_outgoing[$message_id])) {
                                        $API->logger->logger('Already got response for '.$connection->outgoing_messages[$message_id]['_'].' with message ID '.($message_id));
                                        continue;
                                    }
                                    $chr = \ord($chr);
                                    switch ($chr & 7) {
                                        case 0:
                                            $API->logger->logger('Wrong message status 0 for '.$connection->outgoing_messages[$message_id]['_'], \danog\MadelineProto\Logger::FATAL_ERROR);
                                            break;
                                        case 1:
                                        case 2:
                                        case 3:
                                            if ($connection->outgoing_messages[$message_id]['_'] === 'msgs_state_req') {
                                                $connection->gotResponseForOutgoingMessageId($message_id);
                                                break;
                                            }
                                            $API->logger->logger('Message '.$connection->outgoing_messages[$message_id]['_'].' with message ID '.($message_id).' not received by server, resending...', \danog\MadelineProto\Logger::ERROR);
                                            $connection->methodRecall('watcherId', ['message_id' => $message_id, 'postpone' => true]);
                                            break;
                                        case 4:
                                            if ($chr & 32) {
                                                $API->logger->logger('Message '.$connection->outgoing_messages[$message_id]['_'].' with message ID '.($message_id).' received by server and is being processed, waiting...', \danog\MadelineProto\Logger::ERROR);
                                            } elseif ($chr & 64) {
                                                $API->logger->logger('Message '.$connection->outgoing_messages[$message_id]['_'].' with message ID '.($message_id).' received by server and was already processed, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                                $reply[] = $message_id;
                                            } elseif ($chr & 128) {
                                                $API->logger->logger('Message '.$connection->outgoing_messages[$message_id]['_'].' with message ID '.($message_id).' received by server and was already sent, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                                $reply[] = $message_id;
                                            } else {
                                                $API->logger->logger('Message '.$connection->outgoing_messages[$message_id]['_'].' with message ID '.($message_id).' received by server, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                                $reply[] = $message_id;
                                            }
                                    }
                                }
                                if ($reply) {
                                    \danog\MadelineProto\Tools::callFork($connection->objectCall('msg_resend_ans_req', ['msg_ids' => $reply], ['postpone' => true]));
                                }
                                $connection->flush();
                            }
                        );
                        $list = '';
                        // Don't edit this here pls
                        foreach ($message_ids as $message_id) {
                            $list .= $connection->outgoing_messages[$message_id]['_'].', ';
                        }
                        $API->logger->logger("Still missing $list on DC $datacenter, sending state request", \danog\MadelineProto\Logger::ERROR);
                        yield $connection->objectCall('msgs_state_req', ['msg_ids' => $message_ids], ['promise' => $deferred]);
                    }
                } else {
                    foreach ($connection->new_outgoing as $message_id) {
                        if (isset($connection->outgoing_messages[$message_id]['sent'])
                            && $connection->outgoing_messages[$message_id]['sent'] + $timeout < \time()
                            && $connection->outgoing_messages[$message_id]['unencrypted']
                        ) {
                            $API->logger->logger('Still missing '.$connection->outgoing_messages[$message_id]['_'].' with message id '.($message_id)." on DC $datacenter, resending", \danog\MadelineProto\Logger::ERROR);
                            $connection->methodRecall('', ['message_id' => $message_id, 'postpone' => true]);
                        }
                    }
                    $connection->flush();
                }
                if (yield $this->waitSignal($this->pause($timeout))) {
                    return;
                }

                if ($connection->getMaxId(true) === $last_msgid && $connection->getLastChunk() === $last_chunk) {
                    $API->logger->logger("We did not receive a response for $timeout seconds: reconnecting and exiting check loop on DC $datacenter");
                    //$this->exitedLoop();
                    Tools::callForkDefer($connection->reconnect());

                    return;
                }
            } else {
                if (yield $this->waitSignal($this->pause($timeout))) {
                    return;
                }
            }
        }
    }

    public function __toString(): string
    {
        return "check loop in DC {$this->datacenter}";
    }
}
