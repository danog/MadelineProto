<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\ByteStream\StreamException;
use Amp\Loop;
use danog\Loop\ResumableSignalLoop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\OutgoingMessage;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Tools;

/**
 * Socket write loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class WriteLoop extends ResumableSignalLoop
{
    const MAX_COUNT = 1020;
    private const MAX_SIZE = 1 << 15;
    const MAX_IDS = 8192;

    use Common;
    /**
     * Main loop.
     *
     * @return \Generator
     */
    public function loop(): \Generator
    {
        $API = $this->API;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        $datacenter = $this->datacenter;
        $please_wait = false;
        while (true) {
            while (empty($connection->pendingOutgoing) || $please_wait) {
                if ($connection->shouldReconnect()) {
                    $API->logger->logger('Not writing because connection is old');
                    return;
                }
                $please_wait = false;
                $API->logger->logger("Waiting in {$this}", Logger::ULTRA_VERBOSE);
                if (yield $this->waitSignal($this->pause())) {
                    $API->logger->logger("Exiting {$this}", Logger::ULTRA_VERBOSE);
                    return;
                }
                $API->logger->logger("Done waiting in {$this}", Logger::ULTRA_VERBOSE);
                if ($connection->shouldReconnect()) {
                    $API->logger->logger('Not writing because connection is old');
                    return;
                }
            }
            $connection->writing(true);
            try {
                $please_wait = yield from $this->{$shared->hasTempAuthKey() ? 'encryptedWriteLoop' : 'unencryptedWriteLoop'}();
            } catch (StreamException $e) {
                if ($connection->shouldReconnect()) {
                    return;
                }
                Tools::callForkDefer((function () use ($API, $connection, $datacenter, $e): \Generator {
                    $API->logger->logger($e);
                    $API->logger->logger("Got nothing in the socket in DC {$datacenter}, reconnecting...", Logger::ERROR);
                    yield from $connection->reconnect();
                })());
                return;
            } finally {
                $connection->writing(false);
            }
            //$connection->waiter->resume();
        }
    }
    public function unencryptedWriteLoop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        while ($connection->pendingOutgoing) {
            $skipped_all = true;
            foreach ($connection->pendingOutgoing as $k => $message) {
                if ($shared->hasTempAuthKey()) {
                    return;
                }
                if ($message->isEncrypted()) {
                    continue;
                }
                if ($message->getState() & OutgoingMessage::STATE_REPLIED) {
                    unset($connection->pendingOutgoing[$k]);
                    continue;
                }
                $skipped_all = false;
                $API->logger->logger("Sending $message as unencrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $message_id = $message->getMsgId() ?? $connection->msgIdHandler->generateMessageId();
                $length = \strlen($message->getSerializedBody());
                $pad_length = -$length & 15;
                $pad_length += 16 * \danog\MadelineProto\Tools::randomInt($modulus = 16);
                $pad = \danog\MadelineProto\Tools::random($pad_length);
                $buffer = yield $connection->stream->getWriteBuffer(8 + 8 + 4 + $pad_length + $length);
                yield $buffer->bufferWrite("\0\0\0\0\0\0\0\0".$message_id.\danog\MadelineProto\Tools::packUnsignedInt($length).$message->getSerializedBody().$pad);
                $connection->httpSent();

                $API->logger->logger("Sent $message as unencrypted message to DC $datacenter!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                unset($connection->pendingOutgoing[$k]);
                $message->setMsgId($message_id);
                $connection->outgoing_messages[$message_id] = $message;
                $connection->new_outgoing[$message_id] = $message;

                $message->sent();
            }
            if ($skipped_all) {
                return true;
            }
        }
    }
    public function encryptedWriteLoop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        do {
            if (!$shared->hasTempAuthKey()) {
                return;
            }
            if ($shared->isHttp() && empty($connection->pendingOutgoing)) {
                return;
            }

            \ksort($connection->pendingOutgoing);

            $messages = [];
            $keys = [];

            $total_length = 0;
            $count = 0;
            $skipped = false;
            $inited = false;

            $has_seq = false;

            $has_state = false;
            $has_resend = false;
            $has_http_wait = false;
            foreach ($connection->pendingOutgoing as $k => $message) {
                if ($message->isUnencrypted()) {
                    continue;
                }
                if ($message->getState() & OutgoingMessage::STATE_REPLIED) {
                    unset($connection->pendingOutgoing[$k]);
                    $API->logger->logger("Skipping resending of $message, we already got a reply in DC $datacenter");
                    continue;
                }
                if ($message instanceof Container) {
                    unset($connection->pendingOutgoing[$k]);
                    continue;
                }
                $constructor = $message->getConstructor();
                if ($shared->getGenericSettings()->getAuth()->getPfs() && !$shared->isBound() && !$connection->isCDN() && $message->isMethod() && !\in_array($constructor, ['http_wait', 'auth.bindTempAuthKey'])) {
                    $API->logger->logger("Skipping $message due to unbound keys in DC $datacenter");
                    $skipped = true;
                    continue;
                }
                if ($constructor === 'http_wait') {
                    $has_http_wait = true;
                }
                if ($constructor === 'msgs_state_req') {
                    if ($has_state) {
                        $API->logger->logger("Already have a state request queued for the current container in DC {$datacenter}");
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
                    $API->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    break;
                }
                if ($message->hasSeqNo()) {
                    $has_seq = true;
                }

                $message_id = $message->getMsgId() ?? $connection->msgIdHandler->generateMessageId();
                $API->logger->logger("Sending $message as encrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $MTmessage = [
                    '_' => 'MTmessage',
                    'msg_id' => $message_id,
                    'body' => $message->getSerializedBody(),
                    'seqno' => $message->getSeqNo() ?? $connection->generateOutSeqNo($message->isContentRelated())
                ];
                if ($message->isMethod() && $constructor !== 'http_wait') {
                    if (!$shared->getTempAuthKey()->isInited() && $constructor !== 'auth.bindTempAuthKey' && !$inited) {
                        $inited = true;
                        $API->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['write_client_info'], $constructor), \danog\MadelineProto\Logger::NOTICE);
                        $MTmessage['body'] = (yield from $API->getTL()->serializeMethod('invokeWithLayer', ['layer' => $API->settings->getSchema()->getLayer(), 'query' => yield from $API->getTL()->serializeMethod('initConnection', ['api_id' => $API->settings->getAppInfo()->getApiId(), 'api_hash' => $API->settings->getAppInfo()->getApiHash(), 'device_model' => !$connection->isCDN() ? $API->settings->getAppInfo()->getDeviceModel() : 'n/a', 'system_version' => !$connection->isCDN() ? $API->settings->getAppInfo()->getSystemVersion() : 'n/a', 'app_version' => $API->settings->getAppInfo()->getAppVersion(), 'system_lang_code' => $API->settings->getAppInfo()->getLangCode(), 'lang_code' => $API->settings->getAppInfo()->getLangCode(), 'lang_pack' => $API->settings->getAppInfo()->getLangPack(), 'proxy' => $connection->getCtx()->getInputClientProxy(), 'query' => $MTmessage['body']])]));
                    } else {
                        if ($message->hasQueue()) {
                            $queueId = $message->getQueueId();
                            if (!isset($connection->call_queue[$queueId])) {
                                $connection->call_queue[$queueId] = [];
                            }
                            $MTmessage['body'] = (yield from $API->getTL()->serializeMethod('invokeAfterMsgs', ['msg_ids' => $connection->call_queue[$queueId], 'query' => $MTmessage['body']]));
                            $connection->call_queue[$queueId][$message_id] = $message_id;
                            if (\count($connection->call_queue[$queueId]) > $API->settings->getRpc()->getLimitCallQueue()) {
                                \reset($connection->call_queue[$queueId]);
                                $key = \key($connection->call_queue[$queueId]);
                                unset($connection->call_queue[$queueId][$key]);
                            }
                        }
                        // TODO
                        /*
                        if ($API->settings['requests']['gzip_encode_if_gt'] !== -1 && ($l = strlen($MTmessage['body'])) > $API->settings['requests']['gzip_encode_if_gt']) {
                            if (($g = strlen($gzipped = gzencode($MTmessage['body']))) < $l) {
                                $MTmessage['body'] = yield $API->getTL()->serializeObject(['type' => ''], ['_' => 'gzip_packed', 'packed_data' => $gzipped], 'gzipped data');
                                $API->logger->logger('Using GZIP compression for ' . $constructor . ', saved ' . ($l - $g) . ' bytes of data, reduced call size by ' . $g * 100 / $l . '%', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                            }
                            unset($gzipped);
                        }*/
                    }
                }
                $body_length = \strlen($MTmessage['body']);
                $actual_length = $body_length + 32;
                if ($total_length && $total_length + $actual_length > 32760) {
                    $API->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
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

            $acks = \array_slice($connection->ack_queue, 0, self::MAX_COUNT);
            if ($ackCount = \count($acks)) {
                $API->logger->logger("Adding msgs_ack", Logger::ULTRA_VERBOSE);

                $body = yield from $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'msgs_ack', 'msg_ids' => $acks], 'msgs_ack');
                $messages []= [
                    '_' => 'MTmessage',
                    'msg_id' => $connection->msgIdHandler->generateMessageId(),
                    'body' => $body,
                    'seqno' => $connection->generateOutSeqNo(false),
                    'bytes' => \strlen($body)
                ];
                $count++;
                unset($acks, $body);
            }
            if ($shared->isHttp() && !$has_http_wait) {
                $API->logger->logger("Adding http_wait", Logger::ULTRA_VERBOSE);
                $body = yield from $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'http_wait', 'max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 0], 'http_wait');
                $messages []= [
                    '_' => 'MTmessage',
                    'msg_id' => $connection->msgIdHandler->generateMessageId(),
                    'body' => $body,
                    'seqno' => $connection->generateOutSeqNo(true),
                    'bytes' => \strlen($body)
                ];
                $count++;
                unset($body);
            }

            if ($count > 1 || $has_seq) {
                $API->logger->logger("Wrapping in msg_container ({$count} messages of total size {$total_length}) as encrypted message for DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $message_id = $connection->msgIdHandler->generateMessageId();
                $connection->pendingOutgoing[$connection->pendingOutgoingKey] = new Container(\array_values($keys));
                $keys[$connection->pendingOutgoingKey++] = $message_id;
                $message_data = yield from $API->getTL()->serializeObject(['type' => ''], ['_' => 'msg_container', 'messages' => $messages], 'container');
                $message_data_length = \strlen($message_data);
                $seq_no = $connection->generateOutSeqNo(false);
            } elseif ($count) {
                $message = $messages[0];
                $message_data = $message['body'];
                $message_data_length = $message['bytes'];
                $message_id = $message['msg_id'];
                $seq_no = $message['seqno'];
            } else {
                $API->logger->logger("NO MESSAGE SENT in DC {$datacenter}", \danog\MadelineProto\Logger::WARNING);
                return true;
            }
            unset($messages);
            $plaintext = $shared->getTempAuthKey()->getServerSalt().$connection->session_id.$message_id.\pack('VV', $seq_no, $message_data_length).$message_data;
            $padding = \danog\MadelineProto\Tools::posmod(-\strlen($plaintext), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $padding = \danog\MadelineProto\Tools::random($padding);
            $message_key = \substr(\hash('sha256', \substr($shared->getTempAuthKey()->getAuthKey(), 88, 32).$plaintext.$padding, true), 8, 16);
            list($aes_key, $aes_iv) = Crypt::aesCalculate($message_key, $shared->getTempAuthKey()->getAuthKey());
            $message = $shared->getTempAuthKey()->getID().$message_key.Crypt::igeEncrypt($plaintext.$padding, $aes_key, $aes_iv);
            $buffer = yield $connection->stream->getWriteBuffer(\strlen($message));
            yield $buffer->bufferWrite($message);
            $connection->httpSent();
            $API->logger->logger("Sent encrypted payload to DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            if ($ackCount) {
                $connection->ack_queue = \array_slice($connection->ack_queue, $ackCount);
            }

            foreach ($keys as $key => $message_id) {
                $message = $connection->pendingOutgoing[$key];
                unset($connection->pendingOutgoing[$key]);
                $connection->outgoing_messages[$message_id] = $message;
                if ($message->hasPromise()) {
                    $connection->new_outgoing[$message_id] = $message;
                }
                $message->sent();
            }
        } while ($connection->pendingOutgoing && !$skipped);
        if (empty($connection->pendingOutgoing)) {
            $connection->pendingOutgoing = [];
            $connection->pendingOutgoingKey = 'a';
        }
        return $skipped;
    }
    /**
     * Get loop name.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "write loop in DC {$this->datacenter}";
    }
}
