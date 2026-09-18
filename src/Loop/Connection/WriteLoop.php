<?php

declare(strict_types=1);

/**
 * Socket write loop.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\ByteStream\StreamException;
use Amp\DeferredFuture;
use danog\Loop\Loop;
use danog\MadelineProto\Connection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\ConnectionState;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\LinkedList;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Reactive\EphemeralSubscriber;
use danog\MadelineProto\Reactive\Subscriber;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use WeakMap;

/**
 * Socket write loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements Subscriber<ConnectionState>
 */
final class WriteLoop extends Loop implements Subscriber, EphemeralSubscriber
{
    private const MAX_COUNT = 1020;
    private const MAX_SIZE = 1 << 15;
    private const LONG_POLL_TIMEOUT = 30.0;
    private const LONG_POLL_TIMEOUT_MS = 30_000;
    public const MAX_IDS = 8192;

    use Common {
        __construct as init2;
    }

    private readonly bool $isHttp;

    private int $resendTimeout;

    private LinkedList $queue;
    private ?ConnectionState $pendingState = null;

    /**
     * Constructor function.
     */
    public function __construct(Connection $connection)
    {
        $this->init2($connection);
        $this->resendTimeout = $this->API->getSettings()->getRpc()->getRpcResendTimeout();
        $connection->getState()->subscribe($this);
    }

    /**
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function onAttach($initState): void
    {
        $this->pendingState = $initState;
    }

    #[\Override]
    public function onStateChange($prevState, $state): void
    {
        $this->pendingState = $state;
        $this->resume();
    }
    /**
     * Main loop.
     */
    #[\Override]
    public function loop(): ?float
    {
        while (true) {
            if ($this->connection->shouldReconnect()) {
                $this->API->logger("Exiting $this because connection is old");
                return self::STOP;
            }
            if ($this->pendingState !== null) {
                $this->queue = match ($this->pendingState) {
                    ConnectionState::UNENCRYPTED,
                    ConnectionState::UNENCRYPTED_NO_PERMANENT,
                    ConnectionState::UNENCRYPTED_MEDIA_WAITING_MAIN => $this->connection->unencryptedPendingOutgoing,
                    ConnectionState::ENCRYPTED,
                    ConnectionState::ENCRYPTED_NOT_AUTHED_NO_LOGIN => $this->connection->mainPendingOutgoing,
                    default => $this->connection->uninitedPendingOutgoing
                };
                $this->pendingState = null;
            }
            $this->connection->writing(true);
            try {
                if ($this->queue === $this->connection->unencryptedPendingOutgoing) {
                    $this->unencryptedWriteLoop();
                } else {
                    $this->encryptedWriteLoop();
                }
                if ($this->pendingState === null) {
                    $this->API->logger("No messages, pausing in $this...", Logger::ULTRA_VERBOSE);
                    return self::LONG_POLL_TIMEOUT;
                }
            } catch (StreamException $e) {
                if ($this->connection->shouldReconnect()) {
                    $this->API->logger("Stopping $this because we have to reconnect");
                    return self::STOP;
                }
                EventLoop::queue(function () use ($e): void {
                    $this->API->logger($e);
                    $this->API->logger("Got nothing in the socket in DC {$this->datacenter}, reconnecting...", Logger::ERROR);
                    $this->connection->reconnect();
                });
                $this->API->logger("Stopping $this");
                return self::STOP;
            } catch (\Throwable $e) {
                $this->API->logger("Exiting $this due to $e", Logger::FATAL_ERROR);
                return self::STOP;
            } finally {
                $this->connection->writing(false);
            }
        }
    }
    public function unencryptedWriteLoop(): void
    {
        if (0 !== ($queue = $this->queue->check_queue)->count()) {
            $this->queue->check_queue = new WeakMap;
            foreach ($queue as $msg => $_) {
                // TODO: wait for actual re-queueing
                $this->connection->methodRecall($msg);
            }
        }
        while ($message = $this->queue->peek()) {
            $this->API->logger("Sending $message as unencrypted message to DC $this->datacenter", Logger::ULTRA_VERBOSE);
            $serialized = $message->getSerializedBody();

            $message_id = $message->getMsgId() ?? $this->connection->msgIdHandler->generateMessageId();
            $length = \strlen($serialized);
            $pad_length = -$length & 15;
            $pad_length += 16 * Tools::randomInt(modulus: 16);
            $pad = Tools::random($pad_length);
            $buffer = $this->connection->stream->getWriteBuffer($total_len = 8 + 8 + 4 + $pad_length + $length);
            $buffer->bufferWrite("\0\0\0\0\0\0\0\0".Tools::packSignedLong($message_id).Tools::packUnsignedInt($length).$serialized.$pad);
            $this->connection->httpSent();
            $this->connection->outgoingBytesCtr?->incBy($total_len);

            $this->API->logger("Sent $message as unencrypted message to DC $this->datacenter!", Logger::ULTRA_VERBOSE);

            $message->setMsgId($message_id);
            $message->sent();
        }
    }
    public function encryptedWriteLoop(): void
    {
        while ($this->pendingState === null &&
            (
                !$this->queue->isEmpty()
                || $this->connection->ack_queue
                || $this->queue->check_queue->count()
            )
        ) {
            $this->API->logger("Resuming write loop in DC $this->datacenter", Logger::ULTRA_VERBOSE);
            if (0 !== ($check = $this->queue->check_queue)->count()) {
                $this->queue->check_queue = new WeakMap;
                $deferred = new DeferredFuture();
                $deferred->getFuture()->catch(function (\Throwable $e): void {
                    $this->API->logger("Got exception in check loop for DC {$this->datacenter}");
                    $this->API->logger((string) $e);
                });

                $list = '';
                $msgIds = [];
                $arr = [];
                foreach ($check as $msg => $_) {
                    if ($msg->hasReply()) {
                        continue;
                    }
                    $id = $msg->getMsgId();
                    if ($id === null) {
                        $this->API->logger("$msg has no ID, cannot request status!", Logger::ERROR);
                        continue;
                    }
                    $msgIds[] = $id;
                    $list .= $msg.', ';
                    $arr[] = $msg;
                }
                if ($msgIds) {
                    $this->API->logger("Still missing {$list} on DC {$this->datacenter}, sending state request", Logger::ERROR);
                    $this->connection->objectCallAsync('msgs_state_req', [
                        'msg_ids' => $msgIds,
                        'cancellation' => new \Amp\TimeoutCancellation(
                            self::LONG_POLL_TIMEOUT,
                            "Timeout while waiting for msgs_state_req response on DC {$this->datacenter}"
                        ),
                    ], $deferred);
                    $deferred->getFuture()->map(function (array|\Closure $result) use ($arr): void {
                        try {
                            if (\is_callable($result)) {
                                throw $result();
                            }
                            foreach (str_split($result['info']) as $key => $chr) {
                                $message = $arr[$key];
                                if ($message->hasReply()) {
                                    $this->API->logger("Already got response for and forgot about message $message");
                                    $this->connection->ack_queue[] = $message->getMsgId();
                                    continue;
                                }
                                $chr = \ord($chr);
                                switch ($chr & 7) {
                                    case 0:
                                        $this->API->logger("Wrong message status 0 for $message", Logger::FATAL_ERROR);
                                        break;
                                    case 1:
                                    case 2:
                                    case 3:
                                        if ($message->constructor === 'msgs_state_req') {
                                            $message->reply(null);
                                            break;
                                        }
                                        $this->API->logger("Message $message not received by server, resending...", Logger::ERROR);
                                        $this->connection->methodRecall($message);
                                        break;
                                    case 4:
                                        if ($chr & 128) {
                                            $this->API->logger("Message $message received by server and was already sent.", Logger::ERROR);
                                        } elseif ($chr & 64) {
                                            $this->API->logger("Message $message received by server and was already processed.", Logger::ERROR);
                                        } elseif ($chr & 32) {
                                            if ($message->getSent() + $this->resendTimeout < hrtime(true)) {
                                                if (!$message->cancellation?->isRequested()) {
                                                    $this->API->logger("Message $message received by server and is being processed for way too long, resending request...", Logger::ERROR);
                                                    $this->connection->methodRecall($message);
                                                }
                                            } else {
                                                $this->API->logger("Message $message received by server and is being processed, waiting...", Logger::ERROR);
                                            }
                                        } else {
                                            $this->API->logger("Message $message received by server, waiting...", Logger::ERROR);
                                        }
                                        break;
                                }
                            }
                        } catch (\Throwable $e) {
                            $this->API->logger("Got exception in check loop for DC {$this->datacenter}");
                            $this->API->logger((string) $e);
                        }
                    });
                }
            }

            $messages = [];
            $MTmessages = [];

            $total_length = 0;
            $count = 0;

            $has_seq = false;

            $has_state = false;
            $has_resend = false;
            $has_content_related = false;

            $message = $this->queue;
            while (($message = $message->prev) instanceof MTProtoOutgoingMessage) {
                $constructor = $message->constructor;
                if ($constructor === 'msgs_state_req') {
                    if ($has_state) {
                        $this->API->logger("Already have a state request queued for the current container in DC {$this->datacenter}");
                        continue;
                    }
                    $has_state = true;
                }
                if ($constructor === 'msg_resend_req') {
                    if ($has_resend) {
                        continue;
                    }
                    $has_resend = true;
                }

                $body_length = \strlen($message->getSerializedBody());
                $actual_length = $body_length + 32;
                if ($total_length && $total_length + $actual_length > 32760 || $count >= self::MAX_COUNT) {
                    $this->API->logger('Length overflow, postponing part of payload', Logger::ULTRA_VERBOSE);
                    break;
                }
                if ($message->hasSeqNo()) {
                    $has_seq = true;
                }

                $message_id = $message->getMsgId() ?? $this->connection->msgIdHandler->generateMessageId();
                $this->API->logger("Sending $message as encrypted message with id $message_id to DC $this->datacenter", Logger::ULTRA_VERBOSE);
                $MTmessage = [
                    '_' => 'MTmessage',
                    'msg_id' => $message_id,
                    'body' => $message->getSerializedBody(),
                    'seqno' => $message->getSeqNo() ?? $this->connection->generateOutSeqNo($message->contentRelated),
                ];

                $body_length = \strlen($MTmessage['body']);
                $actual_length = $body_length + 32;
                if ($total_length && $total_length + $actual_length > 32760) {
                    $this->API->logger('Length overflow, postponing part of payload', Logger::ULTRA_VERBOSE);
                    break;
                }
                $count++;
                $total_length += $actual_length;
                $MTmessage['bytes'] = $body_length;
                $MTmessages[] = $MTmessage;
                $messages[] = $message;

                $has_content_related = $has_content_related || $message->hasPromise();

                $message->setSeqNo($MTmessage['seqno'])
                        ->setMsgId($MTmessage['msg_id']);
            }
            $MTmessage = null;

            $acks = \array_slice($this->connection->ack_queue, 0, self::MAX_COUNT);
            if ($ackCount = \count($acks)) {
                $this->API->logger('Adding msgs_ack', Logger::ULTRA_VERBOSE);

                $body = $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'msgs_ack', 'msg_ids' => $acks], 'msgs_ack');
                $MTmessages[]= [
                    '_' => 'MTmessage',
                    'msg_id' => $this->connection->msgIdHandler->generateMessageId(),
                    'body' => $body,
                    'seqno' => $this->connection->generateOutSeqNo(false),
                    'bytes' => \strlen($body),
                ];
                $count++;
                unset($body);
            }
            if ($this->connection->isHttp()) {
                $this->API->logger('Adding http_wait', Logger::ULTRA_VERBOSE);
                $body = $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'http_wait', 'max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 0], 'http_wait');
                $MTmessages []= [
                    '_' => 'MTmessage',
                    'msg_id' => $this->connection->msgIdHandler->generateMessageId(),
                    'body' => $body,
                    'seqno' => $this->connection->generateOutSeqNo(true),
                    'bytes' => \strlen($body),
                ];
                $count++;
                unset($body);
            }

            if ($count > 1 || $has_seq) {
                $message_id = $this->connection->msgIdHandler->generateMessageId();
                $this->API->logger("Wrapping in msg_container ({$count} messages of total size {$total_length}, id $message_id) as encrypted message for DC {$this->datacenter}", Logger::ULTRA_VERBOSE);
                $ct = new Container(
                    $this->connection,
                    $messages,
                    $acks,
                );
                if ($has_content_related) {
                    $messages []= $ct;
                }
                $this->connection->outgoingCtr?->inc();
                $message_data = $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'msg_container', 'messages' => $MTmessages], 'container');
                $message_data_length = \strlen($message_data);
                $seq_no = $this->connection->generateOutSeqNo(false);
                $ct->setMsgId($message_id);
                $ct->setSeqNo($seq_no);
            } elseif ($count) {
                $message = $MTmessages[0];
                $message_data = $message['body'];
                $message_data_length = $message['bytes'];
                $message_id = $message['msg_id'];
                $seq_no = $message['seqno'];
            } else {
                $msg = "NO MESSAGE SENT in $this, ";
                $msg .= $this->queue->isEmpty() ? "messages: true, " : "messages: false, ";
                $msg .= $this->connection->ack_queue ? "ack: true, " : "ack: false, ";
                $msg .= $this->queue->check_queue->count() ? "queue: true" : "check queue: false";
                $this->API->logger($msg, Logger::ULTRA_VERBOSE);
                continue;
            }
            unset($MTmessages);

            $auth = $this->shared->auth;

            $plaintext = $auth->getServerSalt().$this->connection->session_id.Tools::packSignedLong($message_id).pack('VV', $seq_no, $message_data_length).$message_data;
            $padding = Tools::posmod(-\strlen($plaintext), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $padding = Tools::random($padding);
            $message_key_large = hash('sha256', $auth->getTempAuthKeyForHash().$plaintext.$padding, true);
            $message_key = substr($message_key_large, 8, 16);
            //$ack = unpack('V', substr($message_key_large, 0, 4))[1] | (1 << 31);
            [$aes_key, $aes_iv] = Crypt::kdf($message_key, $auth->getTempAuthKey());
            $message = $auth->getTempID().$message_key.Crypt::igeEncrypt($plaintext.$padding, $aes_key, $aes_iv);
            $buffer = $this->connection->stream->getWriteBuffer($total_len = \strlen($message));
            $buffer->bufferWrite($message);
            $this->connection->httpSent();
            $this->connection->outgoingBytesCtr?->incBy($total_len);
            $this->API->logger("Sent encrypted payload to DC {$this->datacenter}", Logger::ULTRA_VERBOSE);

            if ($ackCount) {
                $this->connection->ack_queue = \array_slice($this->connection->ack_queue, $ackCount);
            }

            foreach ($messages as $message) {
                $message->sent();
            }
        }
    }
    /**
     * Get loop name.
     *
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return "write loop in DC {$this->datacenter}";
    }
}
