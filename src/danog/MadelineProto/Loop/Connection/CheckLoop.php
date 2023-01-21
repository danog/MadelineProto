<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\DeferredFuture;
use danog\Loop\ResumableSignalLoop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProtoSession\MsgIdHandler;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;

/**
 * RPC call status check loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class CheckLoop extends ResumableSignalLoop
{
    use Common;
    /**
     * Main loop.
     */
    public function loop(): void
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        $timeout = $shared->getSettings()->getTimeout();
        $timeoutMs = (int) ($timeout * 1000);
        $timeoutResend = $timeout * $timeout;
        // Typically 25 seconds, good enough
        while (true) {
            while (empty($connection->new_outgoing)) {
                if ($this->waitSignal(async($this->pause(...)))) {
                    return;
                }
            }
            if (!$connection->hasPendingCalls()) {
                if ($this->waitSignal(async($this->pause(...), $timeoutMs))) {
                    return;
                }
                continue;
            }
            $last_msgid = $connection->msgIdHandler->getMaxId(true);
            if ($shared->hasTempAuthKey()) {
                $full_message_ids = $connection->getPendingCalls();
                foreach (\array_chunk($full_message_ids, 8192) as $message_ids) {
                    $deferred = new DeferredFuture();
                    $deferred->getFuture()->map(function ($result) use ($message_ids, $API, $connection, $timeoutResend): void {
                        $reply = [];
                        foreach (\str_split($result['info']) as $key => $chr) {
                            $message_id = $message_ids[$key];
                            if (!isset($connection->outgoing_messages[$message_id])) {
                                $API->logger->logger('Already got response for and forgot about message ID '.MsgIdHandler::toString($message_id));
                                continue;
                            }
                            if (!isset($connection->new_outgoing[$message_id])) {
                                $API->logger->logger('Already got response for '.$connection->outgoing_messages[$message_id]);
                                continue;
                            }
                            $message = $connection->new_outgoing[$message_id];
                            $chr = \ord($chr);
                            switch ($chr & 7) {
                                case 0:
                                    $API->logger->logger("Wrong message status 0 for $message", Logger::FATAL_ERROR);
                                    break;
                                case 1:
                                case 2:
                                case 3:
                                    if ($message->getConstructor() === 'msgs_state_req') {
                                        $connection->gotResponseForOutgoingMessage($message);
                                        break;
                                    }
                                    $API->logger->logger("Message $message not received by server, resending...", Logger::ERROR);
                                    $connection->methodRecall(['message_id' => $message_id, 'postpone' => true]);
                                    break;
                                case 4:
                                    if ($chr & 32) {
                                        if ($message->getSent() + $timeoutResend < \time()) {
                                            $API->logger->logger("Message $message received by server and is being processed for way too long, resending request...", Logger::ERROR);
                                            $connection->methodRecall(['message_id' => $message_id, 'postpone' => true]);
                                        } else {
                                            $API->logger->logger("Message $message received by server and is being processed, waiting...", Logger::ERROR);
                                        }
                                    } elseif ($chr & 64) {
                                        $API->logger->logger("Message $message received by server and was already processed, requesting reply...", Logger::ERROR);
                                        $reply[] = $message_id;
                                    } elseif ($chr & 128) {
                                        $API->logger->logger("Message $message received by server and was already sent, requesting reply...", Logger::ERROR);
                                        $reply[] = $message_id;
                                    } else {
                                        $API->logger->logger("Message $message received by server, waiting...", Logger::ERROR);
                                        $reply[] = $message_id;
                                    }
                            }
                        }
                        $connection->flush();
                    })->catch(function (Throwable $e) use ($API, $datacenter): void {
                        $API->logger("Got exception in check loop for DC {$datacenter}");
                        $API->logger((string) $e);
                    });
                    $list = '';
                    // Don't edit this here pls
                    foreach ($message_ids as $message_id) {
                        $list .= $connection->outgoing_messages[$message_id]->getConstructor().', ';
                    }
                    $API->logger->logger("Still missing {$list} on DC {$datacenter}, sending state request", Logger::ERROR);
                    $connection->objectCall('msgs_state_req', ['msg_ids' => $message_ids], ['promise' => $deferred]);
                }
            } else {
                foreach ($connection->new_outgoing as $message_id => $message) {
                    if ($message->wasSent()
                        && $message->getSent() + $timeout < \time()
                        && $message->isUnencrypted()
                    ) {
                        $API->logger->logger("Still missing $message on DC $datacenter, resending", Logger::ERROR);
                        $connection->methodRecall(['message_id' => $message->getMsgId(), 'postpone' => true]);
                    }
                }
                $connection->flush();
            }
            if ($this->waitSignal(async($this->pause(...), $timeoutMs))) {
                return;
            }
            if ($connection->msgIdHandler->getMaxId(true) === $last_msgid && !$connection->isReading()) {
                $API->logger->logger("We did not receive a response for {$timeout} seconds: reconnecting and exiting check loop on DC {$datacenter}");
                EventLoop::queue($connection->reconnect(...));
                return;
            }
        }
    }
    /**
     * Loop name.
     */
    public function __toString(): string
    {
        return "check loop in DC {$this->datacenter}";
    }
}
