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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\ByteStream\StreamException;
use danog\Loop\Loop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;

use function strlen;

/**
 * Socket write loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class WriteLoop extends Loop
{
    const MAX_COUNT = 1020;
    private const MAX_SIZE = 1 << 15;
    const MAX_IDS = 8192;

    use Common;
    /**
     * Main loop.
     */
    public function loop(): ?float
    {
        $please_wait = false;
        while (true) {
            if ($this->connection->shouldReconnect()) {
                $this->logger->logger("Exiting $this because connection is old");
                return self::STOP;
            }
            if (!$this->connection->pendingOutgoing) {
                $this->logger->logger("No messages, pausing in $this...", Logger::ULTRA_VERBOSE);
                return self::PAUSE;
            }
            if ($please_wait) {
                $this->logger->logger("Have to wait for handshake, pausing in $this...", Logger::ULTRA_VERBOSE);
                return self::PAUSE;
            }
            $this->connection->writing(true);
            try {
                $please_wait = $this->shared->hasTempAuthKey()
                    ? $this->encryptedWriteLoop()
                    : $this->unencryptedWriteLoop();
            } catch (StreamException $e) {
                if ($this->connection->shouldReconnect()) {
                    $this->logger->logger("Stopping $this because we have to reconnect");
                    return self::STOP;
                }
                EventLoop::queue(function () use ($e): void {
                    $this->logger->logger($e);
                    $this->logger->logger("Got nothing in the socket in DC {$this->datacenter}, reconnecting...", Logger::ERROR);
                    $this->connection->reconnect();
                });
                $this->logger->logger("Stopping $this");
                return self::STOP;
            } finally {
                $this->connection->writing(false);
            }
        }
    }
    public function unencryptedWriteLoop(): bool
    {
        while ($this->connection->pendingOutgoing) {
            $skipped_all = true;
            foreach ($this->connection->pendingOutgoing as $k => $message) {
                if ($this->shared->hasTempAuthKey()) {
                    return false;
                }
                if ($message->isEncrypted()) {
                    continue;
                }
                if ($message->getState() & MTProtoOutgoingMessage::STATE_REPLIED) {
                    unset($this->connection->pendingOutgoing[$k]);
                    continue;
                }
                $skipped_all = false;
                $this->logger->logger("Sending $message as unencrypted message to DC $this->datacenter", Logger::ULTRA_VERBOSE);
                $message_id = $message->getMsgId() ?? $this->connection->msgIdHandler->generateMessageId();
                $length = \strlen($message->getSerializedBody());
                $pad_length = -$length & 15;
                $pad_length += 16 * Tools::randomInt(modulus: 16);
                $pad = Tools::random($pad_length);
                $buffer = $this->connection->stream->getWriteBuffer(8 + 8 + 4 + $pad_length + $length);
                $buffer->bufferWrite("\0\0\0\0\0\0\0\0".Tools::packSignedLong($message_id).Tools::packUnsignedInt($length).$message->getSerializedBody().$pad);
                $this->connection->httpSent();

                $this->logger->logger("Sent $message as unencrypted message to DC $this->datacenter!", Logger::ULTRA_VERBOSE);

                unset($this->connection->pendingOutgoing[$k]);
                $message->setMsgId($message_id);
                $this->connection->outgoing_messages[$message_id] = $message;
                $this->connection->new_outgoing[$message_id] = $message;

                $message->sent();
            }
            if ($skipped_all) {
                return true;
            }
        }
        return false;
    }
    public function encryptedWriteLoop(): bool
    {
        do {
            if (!$this->shared->hasTempAuthKey()) {
                return false;
            }
            if ($this->connection->isHttp() && empty($this->connection->pendingOutgoing)) {
                return false;
            }

            \ksort($this->connection->pendingOutgoing);

            $messages = [];
            $keys = [];

            $total_length = 0;
            $count = 0;
            $skipped = false;

            $has_seq = false;

            $has_state = false;
            $has_resend = false;
            $has_http_wait = false;
            foreach ($this->connection->pendingOutgoing as $k => $message) {
                if ($message->isUnencrypted()) {
                    continue;
                }
                if ($message->getState() & MTProtoOutgoingMessage::STATE_REPLIED) {
                    unset($this->connection->pendingOutgoing[$k]);
                    $this->logger->logger("Skipping resending of $message, we already got a reply in DC $this->datacenter");
                    continue;
                }
                if ($message instanceof Container) {
                    unset($this->connection->pendingOutgoing[$k]);
                    continue;
                }
                $constructor = $message->getConstructor();
                if ($this->shared->getGenericSettings()->getAuth()->getPfs() && !$this->shared->isBound() && !$this->connection->isCDN() && $message->isMethod() && !\in_array($constructor, ['http_wait', 'auth.bindTempAuthKey'], true)) {
                    $this->logger->logger("Skipping $message due to unbound keys in DC $this->datacenter");
                    $skipped = true;
                    continue;
                }
                if ($constructor === 'http_wait') {
                    $has_http_wait = true;
                }
                if ($constructor === 'msgs_state_req') {
                    if ($has_state) {
                        $this->logger->logger("Already have a state request queued for the current container in DC {$this->datacenter}");
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
                if ($total_length && $total_length + $actual_length > 32760 || $count >= 1020) {
                    $this->logger->logger('Length overflow, postponing part of payload', Logger::ULTRA_VERBOSE);
                    break;
                }
                if ($message->hasSeqNo()) {
                    $has_seq = true;
                }

                $message_id = $message->getMsgId() ?? $this->connection->msgIdHandler->generateMessageId();
                $this->logger->logger("Sending $message as encrypted message to DC $this->datacenter", Logger::ULTRA_VERBOSE);
                $MTmessage = [
                    '_' => 'MTmessage',
                    'msg_id' => $message_id,
                    'body' => $message->getSerializedBody(),
                    'seqno' => $message->getSeqNo() ?? $this->connection->generateOutSeqNo($message->isContentRelated()),
                ];
                if ($message->isMethod() && $constructor !== 'http_wait' && $constructor !== 'ping_delay_disconnect' && $constructor !== 'auth.bindTempAuthKey') {
                    if (!$this->shared->getTempAuthKey()->isInited()) {
                        if ($constructor === 'help.getConfig' || $constructor === 'upload.getCdnFile') {
                            $this->logger->logger(\sprintf('Writing client info (also executing %s)...', $constructor), Logger::NOTICE);
                            $MTmessage['body'] = ($this->API->getTL()->serializeMethod('invokeWithLayer', ['layer' => $this->API->settings->getSchema()->getLayer(), 'query' => $this->API->getTL()->serializeMethod('initConnection', ['api_id' => $this->API->settings->getAppInfo()->getApiId(), 'api_hash' => $this->API->settings->getAppInfo()->getApiHash(), 'device_model' => !$this->connection->isCDN() ? $this->API->settings->getAppInfo()->getDeviceModel() : 'n/a', 'system_version' => !$this->connection->isCDN() ? $this->API->settings->getAppInfo()->getSystemVersion() : 'n/a', 'app_version' => $this->API->settings->getAppInfo()->getAppVersion(), 'system_lang_code' => $this->API->settings->getAppInfo()->getLangCode(), 'lang_code' => $this->API->settings->getAppInfo()->getLangCode(), 'lang_pack' => $this->API->settings->getAppInfo()->getLangPack(), 'proxy' => $this->connection->getInputClientProxy(), 'query' => $MTmessage['body']])]));
                        } else {
                            $this->logger->logger("Skipping $message due to uninited connection in DC $this->datacenter");
                            $skipped = true;
                            continue;
                        }
                    } elseif ($message->hasQueue()) {
                        $queueId = $message->getQueueId();
                        $this->connection->call_queue[$queueId] ??= [];
                        $MTmessage['body'] = ($this->API->getTL()->serializeMethod('invokeAfterMsgs', ['msg_ids' => $this->connection->call_queue[$queueId], 'query' => $MTmessage['body']]));
                        $this->connection->call_queue[$queueId][$message_id] = $message_id;
                        if (\count($this->connection->call_queue[$queueId]) > $this->API->settings->getRpc()->getLimitCallQueue()) {
                            \reset($this->connection->call_queue[$queueId]);
                            $key = \key($this->connection->call_queue[$queueId]);
                            unset($this->connection->call_queue[$queueId][$key]);
                        }
                    }
                    // TODO
                    /*
                    if ($this->API->settings['requests']['gzip_encode_if_gt'] !== -1 && ($l = strlen($MTmessage['body'])) > $this->API->settings['requests']['gzip_encode_if_gt']) {
                        if (($g = strlen($gzipped = gzencode($MTmessage['body']))) < $l) {
                            $MTmessage['body'] = $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'gzip_packed', 'packed_data' => $gzipped], 'gzipped data');
                            $this->logger->logger('Using GZIP compression for ' . $constructor . ', saved ' . ($l - $g) . ' bytes of data, reduced call size by ' . $g * 100 / $l . '%', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        }
                        unset($gzipped);
                    }*/
                }
                $body_length = \strlen($MTmessage['body']);
                $actual_length = $body_length + 32;
                if ($total_length && $total_length + $actual_length > 32760) {
                    $this->logger->logger('Length overflow, postponing part of payload', Logger::ULTRA_VERBOSE);
                    break;
                }
                $count++;
                $total_length += $actual_length;
                $MTmessage['bytes'] = $body_length;
                $messages[] = $MTmessage;
                $keys[$k] = $message_id;

                $message->setSeqNo($MTmessage['seqno'])
                        ->setMsgId($MTmessage['msg_id']);
            }
            $MTmessage = null;

            $acks = \array_slice($this->connection->ack_queue, 0, self::MAX_COUNT);
            if ($ackCount = \count($acks)) {
                $this->logger->logger('Adding msgs_ack', Logger::ULTRA_VERBOSE);

                $body = $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'msgs_ack', 'msg_ids' => $acks], 'msgs_ack');
                $messages []= [
                    '_' => 'MTmessage',
                    'msg_id' => $this->connection->msgIdHandler->generateMessageId(),
                    'body' => $body,
                    'seqno' => $this->connection->generateOutSeqNo(false),
                    'bytes' => \strlen($body),
                ];
                $count++;
                unset($acks, $body);
            }
            if ($this->connection->isHttp() && !$has_http_wait) {
                $this->logger->logger('Adding http_wait', Logger::ULTRA_VERBOSE);
                $body = $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'http_wait', 'max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 0], 'http_wait');
                $messages []= [
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
                $this->logger->logger("Wrapping in msg_container ({$count} messages of total size {$total_length}) as encrypted message for DC {$this->datacenter}", Logger::ULTRA_VERBOSE);
                $message_id = $this->connection->msgIdHandler->generateMessageId();
                $this->connection->pendingOutgoing[$this->connection->pendingOutgoingKey] = new Container(\array_values($keys));
                $keys[$this->connection->pendingOutgoingKey++] = $message_id;
                $message_data = $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'msg_container', 'messages' => $messages], 'container');
                $message_data_length = \strlen($message_data);
                $seq_no = $this->connection->generateOutSeqNo(false);
            } elseif ($count) {
                $message = $messages[0];
                $message_data = $message['body'];
                $message_data_length = $message['bytes'];
                $message_id = $message['msg_id'];
                $seq_no = $message['seqno'];
            } else {
                $this->logger->logger("NO MESSAGE SENT in $this, pending ".\implode(', ', \array_map('strval', $this->connection->pendingOutgoing)), Logger::WARNING);
                return true;
            }
            unset($messages);
            $plaintext = $this->shared->getTempAuthKey()->getServerSalt().$this->connection->session_id.Tools::packSignedLong($message_id).\pack('VV', $seq_no, $message_data_length).$message_data;
            $padding = Tools::posmod(-\strlen($plaintext), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $padding = Tools::random($padding);
            $message_key = \substr(\hash('sha256', \substr($this->shared->getTempAuthKey()->getAuthKey(), 88, 32).$plaintext.$padding, true), 8, 16);
            [$aes_key, $aes_iv] = Crypt::kdf($message_key, $this->shared->getTempAuthKey()->getAuthKey());
            $message = $this->shared->getTempAuthKey()->getID().$message_key.Crypt::igeEncrypt($plaintext.$padding, $aes_key, $aes_iv);
            $buffer = $this->connection->stream->getWriteBuffer(\strlen($message));
            $buffer->bufferWrite($message);
            $this->connection->httpSent();
            $this->logger->logger("Sent encrypted payload to DC {$this->datacenter}", Logger::ULTRA_VERBOSE);

            if ($ackCount) {
                $this->connection->ack_queue = \array_slice($this->connection->ack_queue, $ackCount);
            }

            foreach ($keys as $key => $message_id) {
                $message = $this->connection->pendingOutgoing[$key];
                unset($this->connection->pendingOutgoing[$key]);
                $this->connection->outgoing_messages[$message_id] = $message;
                if ($message->hasPromise()) {
                    $this->connection->new_outgoing[$message_id] = $message;
                }
                $message->sent();
            }
        } while ($this->connection->pendingOutgoing && !$skipped);
        if (empty($this->connection->pendingOutgoing)) {
            $this->connection->pendingOutgoing = [];
            $this->connection->pendingOutgoingKey = 0;
        }
        return $skipped;
    }
    /**
     * Get loop name.
     */
    public function __toString(): string
    {
        return "write loop in DC {$this->datacenter}";
    }
}
