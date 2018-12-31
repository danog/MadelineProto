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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\Coroutine;
use Amp\Success;
use danog\MadelineProto\Connection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Tools;

/**
 * Socket write loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class WriteLoop extends ResumableSignalLoop
{
    use Crypt;
    use Tools;

    public function loop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        $this->startedLoop();
        $API->logger->logger("Entered write loop in DC {$datacenter}", Logger::ULTRA_VERBOSE);

        while (true) {
            if (empty($connection->pending_outgoing)) {
                if (yield $this->waitSignal($this->pause())) {
                    $API->logger->logger('Exiting write loop');
                    $this->exitedLoop();
                    yield new Success(0);

                    return;
                }
            }

            try {
                if ($connection->temp_auth_key === null) {
                    $res = $this->unencryptedWriteLoopAsync();
                } else {
                    $res = $this->encryptedWriteLoopAsync();
                }
                yield $res;
            } finally {
                $this->exitedLoop();
            }
            $this->startedLoop();

            //$connection->waiter->resume();
        }
    }

    public function unencryptedWriteLoopAsync(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        while ($connection->pending_outgoing) {
            $skipped_all = true;
            foreach ($connection->pending_outgoing as $k => $message) {
                if ($connection->temp_auth_key !== null) {
                    return;
                }
                if (!$message['unencrypted']) {
                    continue;
                }
                $skipped_all = false;

                $body = $message['serialized_body'];

                $API->logger->logger("Sending {$message['_']} as unencrypted message to DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $message_id = isset($message['msg_id']) ? $message['msg_id'] : $connection->generate_message_id();
                $length = strlen($body);

                $pad_length = -$length & 15;
                $pad_length += 16 * $this->random_int($modulus = 16);

                $pad = $this->random($pad_length);
                $buffer = yield $connection->stream->getWriteBuffer(8 + 8 + 4 + $pad_length + $length);

                yield $buffer->bufferWrite("\0\0\0\0\0\0\0\0".$message_id.$this->pack_unsigned_int($length).$body.$pad);

                //var_dump("plain ".bin2hex($message_id));
                $connection->http_req_count++;
                $connection->outgoing_messages[$message_id] = $message;
                $connection->outgoing_messages[$message_id]['sent'] = time();
                $connection->outgoing_messages[$message_id]['tries'] = 0;
                $connection->outgoing_messages[$message_id]['unencrypted'] = true;
                $connection->new_outgoing[$message_id] = $message_id;

                unset($connection->pending_outgoing[$k]);

                $API->logger->logger("Sent {$message['_']} as unencrypted message to DC {$datacenter}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $message['send_promise']->resolve(isset($message['promise']) ? $message['promise'] : true);
                unset($message['send_promise']);
            }
            if ($skipped_all) {
                break;
            }
        }
    }

    public function encryptedWriteLoopAsync(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        do {
            if ($connection->temp_auth_key === null) {
                return;
            }
            if ($this->API->is_http($datacenter) && empty($connection->pending_outgoing)) {
                return;
            }
            if (count($to_ack = $connection->ack_queue)) {
                $connection->pending_outgoing[$connection->pending_outgoing_key++] = ['_' => 'msgs_ack', 'serialized_body' => yield $this->API->serialize_object_async(['type' => 'msgs_ack'], ['msg_ids' => $connection->ack_queue], 'msgs_ack'), 'content_related' => false, 'unencrypted' => false, 'method' => false];
                $connection->pending_outgoing_key %= Connection::PENDING_MAX;
            }

            $has_http_wait = false;
            $messages = [];
            $keys = [];

            foreach ($connection->pending_outgoing as $message) {
                if ($message['_'] === 'http_wait') {
                    $has_http_wait = true;
                    break;
                }
            }

            if ($API->is_http($datacenter) && !$has_http_wait) {
                $dc_config_number = isset($API->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';

                //$connection->pending_outgoing[$connection->pending_outgoing_key++] = ['_' => 'http_wait', 'serialized_body' => $this->API->serialize_object(['type' => ''], ['_' => 'http_wait', 'max_wait' => $API->settings['connection_settings'][$dc_config_number]['timeout'] * 1000 - 100, 'wait_after' => 0, 'max_delay' => 0], 'http_wait'), 'content_related' => true, 'unencrypted' => false, 'method' => true];
                $connection->pending_outgoing[$connection->pending_outgoing_key++] = ['_' => 'http_wait', 'serialized_body' => yield $this->API->serialize_object_async(['type' => ''], ['_' => 'http_wait', 'max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 1], 'http_wait'), 'content_related' => true, 'unencrypted' => false, 'method' => true];
                $connection->pending_outgoing_key %= Connection::PENDING_MAX;

                $has_http_wait = true;
            }

            $total_length = 0;
            $count = 0;
            ksort($connection->pending_outgoing);
            foreach ($connection->pending_outgoing as $k => $message) {
                if ($message['unencrypted']) {
                    continue;
                }
                if (isset($message['container'])) {
                    unset($connection->pending_outgoing[$k]);
                    continue;
                }
                $body = $message['serialized_body'];

                $message_id = isset($message['msg_id']) ? $message['msg_id'] : $connection->generate_message_id($datacenter);

                $API->logger->logger("Sending {$message['_']} as encrypted message to DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $MTmessage = ['_' => 'MTmessage', 'msg_id' => $message_id, 'body' => $body, 'seqno' => $connection->generate_out_seq_no($message['content_related'])];

                if (isset($message['method']) && $message['method'] && $message['_'] !== 'http_wait') {
                    if ((!isset($connection->temp_auth_key['connection_inited']) || $connection->temp_auth_key['connection_inited'] === false) && $message['_'] !== 'auth.bindTempAuthKey') {
                        $API->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['write_client_info'], $message['_']), \danog\MadelineProto\Logger::NOTICE);
                        $MTmessage['body'] = yield $API->serialize_method_async(
                            'invokeWithLayer',
                            [
                                'layer' => $API->settings['tl_schema']['layer'],
                                'query' => yield $API->serialize_method_async(
                                    'initConnection',
                                    [
                                        'api_id'           => $API->settings['app_info']['api_id'],
                                        'api_hash'         => $API->settings['app_info']['api_hash'],
                                        'device_model'     => strpos($datacenter, 'cdn') === false ? $API->settings['app_info']['device_model'] : 'n/a',
                                        'system_version'   => strpos($datacenter, 'cdn') === false ? $API->settings['app_info']['system_version'] : 'n/a',
                                        'app_version'      => $API->settings['app_info']['app_version'],
                                        'system_lang_code' => $API->settings['app_info']['lang_code'],
                                        'lang_code'        => $API->settings['app_info']['lang_code'],
                                        'lang_pack'        => $API->settings['app_info']['lang_pack'],
                                        'query'            => $MTmessage['body'],
                                    ]
                                ),
                            ]
                        );
                    } else {
                        if (isset($message['queue'])) {
                            if (!isset($connection->call_queue[$message['queue']])) {
                                $connection->call_queue[$message['queue']] = [];
                            }
                            $MTmessage['body'] = yield $API->serialize_method_async('invokeAfterMsgs', ['msg_ids' => $connection->call_queue[$message['queue']], 'query' => $MTmessage['body']]);

                            $connection->call_queue[$message['queue']][$message_id] = $message_id;
                            if (count($connection->call_queue[$message['queue']]) > $API->settings['msg_array_limit']['call_queue']) {
                                reset($connection->call_queue[$message['queue']]);
                                $key = key($connection->call_queue[$message['queue']]);
                                unset($connection->call_queue[$message['queue']][$key]);
                            }
                        }

                        /*                        if ($API->settings['requests']['gzip_encode_if_gt'] !== -1 && ($l = strlen($MTmessage['body'])) > $API->settings['requests']['gzip_encode_if_gt']) {
                        if (($g = strlen($gzipped = gzencode($MTmessage['body']))) < $l) {
                        $MTmessage['body'] = yield $API->serialize_object_async(['type' => 'gzip_packed'], ['packed_data' => $gzipped], 'gzipped data');
                        $API->logger->logger('Using GZIP compression for ' . $message['_'] . ', saved ' . ($l - $g) . ' bytes of data, reduced call size by ' . $g * 100 / $l . '%', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        }
                        unset($gzipped);
                        }*/
                    }
                }
                $body_length = strlen($MTmessage['body']);
                if ($total_length && $total_length + $body_length + 32 > 655360) {
                    $API->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::NOTICE);
                    break;
                }
                $count++;
                $total_length += $body_length + 32;

                $MTmessage['bytes'] = $body_length;
                $messages[] = $MTmessage;
                $keys[$k] = $message_id;

                if ($total_length && $total_length + 32 > 655360) {
                    $API->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::NOTICE);
                    break;
                }
            }

            if (count($messages) > 1) {
                $API->logger->logger("Wrapping in msg_container as encrypted message for DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $message_id = $connection->generate_message_id($datacenter);
                $connection->pending_outgoing[$connection->pending_outgoing_key] = ['_' => 'msg_container', 'container' => array_values($keys), 'content_related' => false, 'method' => false];

                //var_dumP("container ".bin2hex($message_id));
                $keys[$connection->pending_outgoing_key++] = $message_id;
                $connection->pending_outgoing_key %= Connection::PENDING_MAX;

                $message_data = yield $API->serialize_object_async(['type' => ''], ['_' => 'msg_container', 'messages' => $messages], 'container');

                $message_data_length = strlen($message_data);
                $seq_no = $connection->generate_out_seq_no(false);
            } elseif (count($messages)) {
                $message = $messages[0];
                $message_data = $message['body'];
                $message_data_length = $message['bytes'];
                $message_id = $message['msg_id'];
                $seq_no = $message['seqno'];
            } else {
                $API->logger->logger('NO MESSAGE SENT', \danog\MadelineProto\Logger::WARNING);

                return;
            }

            unset($messages);

            $plaintext = $connection->temp_auth_key['server_salt'].$connection->session_id.$message_id.pack('VV', $seq_no, $message_data_length).$message_data;
            $padding = $this->posmod(-strlen($plaintext), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $padding = $this->random($padding);
            $message_key = substr(hash('sha256', substr($connection->temp_auth_key['auth_key'], 88, 32).$plaintext.$padding, true), 8, 16);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $connection->temp_auth_key['auth_key']);
            $message = $connection->temp_auth_key['id'].$message_key.$this->ige_encrypt($plaintext.$padding, $aes_key, $aes_iv);

            $buffer = yield $connection->stream->getWriteBuffer($len = strlen($message));

            $t = microtime(true);
            yield $buffer->bufferWrite($message);

            $connection->http_req_count++;

            $API->logger->logger("Sent encrypted payload to DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            $sent = time();

            if ($to_ack) {
                $connection->ack_queue = [];
            }

            if ($has_http_wait) {
                $connection->last_http_wait = $sent;
            } elseif ($API->isAltervista()) {
                $connection->last_http_wait = PHP_INT_MAX;
            }

            foreach ($keys as $key => $message_id) {
                $connection->outgoing_messages[$message_id] = &$connection->pending_outgoing[$key];
                if (isset($connection->outgoing_messages[$message_id]['promise'])) {
                    $connection->new_outgoing[$message_id] = $message_id;
                    $connection->outgoing_messages[$message_id]['sent'] = $sent;
                    $connection->outgoing_messages[$message_id]['tries'] = 0;
                }
                if (isset($connection->outgoing_messages[$message_id]['send_promise'])) {
                    $connection->outgoing_messages[$message_id]['send_promise']->resolve(isset($connection->outgoing_messages[$message_id]['promise']) ? $connection->outgoing_messages[$message_id]['promise'] : true);
                    unset($connection->outgoing_messages[$message_id]['send_promise']);
                }
                //var_dumP("encrypted ".bin2hex($message_id)." ".$connection->outgoing_messages[$message_id]['_']);
                unset($connection->pending_outgoing[$key]);
            }

            //if (!empty($connection->pending_outgoing)) $connection->select();
        } while (!empty($connection->pending_outgoing));

        $connection->pending_outgoing_key = 0;
    }
}
