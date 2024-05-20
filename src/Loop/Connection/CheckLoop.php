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

use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\TimeoutCancellation;
use danog\Loop\Loop;
use danog\MadelineProto\Connection;
use danog\MadelineProto\Logger;
use Revolt\EventLoop;

/**
 * RPC call status check loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class CheckLoop extends Loop
{
    use Common {
        __construct as initCommon;
    }

    private int $resendTimeout;
    public function __construct(Connection $connection)
    {
        $this->initCommon($connection);
        $this->resendTimeout = (int) ($this->API->settings->getRpc()->getRpcResendTimeout() * 1_000_000_000.0);
    }
    /**
     * Main loop.
     */
    protected function loop(): ?float
    {
        if (!$this->connection->new_outgoing) {
            return self::PAUSE;
        }
        if (!$this->connection->hasPendingCalls()) {
            return $this->timeoutSeconds;
        }
        if ($this->shared->hasTempAuthKey()) {
            $full_message_ids = $this->connection->getPendingCalls();
            foreach (array_chunk($full_message_ids, WriteLoop::MAX_IDS) as $message_ids) {
                $deferred = new DeferredFuture();
                $list = '';
                // Don't edit this here pls
                foreach ($message_ids as $message_id) {
                    if (!isset($this->connection->outgoing_messages[$message_id])) {
                        continue;
                    }
                    $list .= $this->connection->outgoing_messages[$message_id]->constructor.', ';
                }
                $this->API->logger("Still missing {$list} on DC {$this->datacenter}, sending state request", Logger::ERROR);
                $this->connection->objectCall('msgs_state_req', ['msg_ids' => $message_ids], $deferred);
                EventLoop::queue(function () use ($deferred, $message_ids): void {
                    try {
                        $result = $deferred->getFuture()->await(new TimeoutCancellation($this->timeout));
                        if (\is_callable($result)) {
                            throw $result();
                        }
                        $reply = [];
                        foreach (str_split($result['info']) as $key => $chr) {
                            $message_id = $message_ids[$key];
                            if (!isset($this->connection->outgoing_messages[$message_id])) {
                                $this->API->logger("Already got response for and forgot about message ID $message_id");
                                continue;
                            }
                            if (!isset($this->connection->new_outgoing[$message_id])) {
                                $this->API->logger('Already got response for '.$this->connection->outgoing_messages[$message_id]);
                                continue;
                            }
                            $message = $this->connection->new_outgoing[$message_id];
                            $chr = \ord($chr);
                            switch ($chr & 7) {
                                case 0:
                                    $this->API->logger("Wrong message status 0 for $message", Logger::FATAL_ERROR);
                                    break;
                                case 1:
                                case 2:
                                case 3:
                                    if ($message->constructor === 'msgs_state_req') {
                                        $this->connection->gotResponseForOutgoingMessage($message);
                                        break;
                                    }
                                    $this->API->logger("Message $message not received by server, resending...", Logger::ERROR);
                                    $this->connection->methodRecall($message_id);
                                    break;
                                case 4:
                                    if ($chr & 128) {
                                        $this->API->logger("Message $message received by server and was already sent, requesting reply...", Logger::ERROR);
                                        $reply[] = $message_id;
                                    } elseif ($chr & 64) {
                                        $this->API->logger("Message $message received by server and was already processed, requesting reply...", Logger::ERROR);
                                        $reply[] = $message_id;
                                    } elseif ($chr & 32) {
                                        if ($message->getSent() + $this->resendTimeout < hrtime(true)) {
                                            if ($message->isCancellationRequested()) {
                                                unset($this->connection->new_outgoing[$message_id], $this->connection->outgoing_messages[$message_id]);

                                                $this->API->logger("Cancelling $message...", Logger::ERROR);
                                            } else {
                                                $this->API->logger("Message $message received by server and is being processed for way too long, resending request...", Logger::ERROR);
                                                $this->connection->methodRecall($message_id);
                                            }
                                        } else {
                                            $this->API->logger("Message $message received by server and is being processed, waiting...", Logger::ERROR);
                                        }
                                    } else {
                                        $this->API->logger("Message $message received by server, waiting...", Logger::ERROR);
                                        $reply[] = $message_id;
                                    }
                            }
                        }
                        //} catch (CancelledException) {
                        //$this->API->logger("We did not receive a response for {$this->timeout} seconds: reconnecting and exiting check loop on DC {$this->datacenter}");
                        //EventLoop::queue($this->connection->reconnect(...));
                    } catch (\Throwable $e) {
                        $this->API->logger("Got exception in check loop for DC {$this->datacenter}");
                        $this->API->logger((string) $e);
                    }
                });
            }
        } else {
            foreach ($this->connection->new_outgoing as $message_id => $message) {
                if ($message->wasSent()
                    && $message->getSent() + $this->timeout < hrtime(true)
                    && $message->unencrypted
                ) {
                    $this->API->logger("Still missing $message on DC {$this->datacenter}, resending", Logger::ERROR);
                    $this->connection->methodRecall($message->getMsgId());
                }
            }
        }
        return $this->timeoutSeconds;
    }
    /**
     * Loop name.
     */
    public function __toString(): string
    {
        return "check loop in DC {$this->datacenter}";
    }
}
