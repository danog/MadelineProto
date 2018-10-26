<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
 */

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 */
trait MessageHandler
{
    public function send_unencrypted_message($message, $datacenter)
    {
        $body = is_callable($message['body']) ? $message['body']() : $message['body'];
        if ($body === null) {
            $this->logger->logger("Postponing {$message['_']} as unencrypted message to DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return;
        }

        $this->logger->logger("Sending {$message['_']} as unencrypted message to DC {$datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        $message_id = isset($message['msg_id']) ? $message['msg_id'] : $this->generate_message_id($datacenter);

        $this->datacenter->sockets[$datacenter]->send_message("\0\0\0\0\0\0\0\0".$message_id.$this->pack_unsigned_int(strlen($body)).$body);
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id] = $message;
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['sent'] = time();
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['tries'] = 0;
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['unencrypted'] = true;
        $this->datacenter->sockets[$datacenter]->new_outgoing[$message_id] = $message_id;
    }

    public function send_messages($datacenter)
    {
        if ($this->datacenter->sockets[$datacenter]->temp_auth_key === null) {
            $this->check_pending_calls_dc($datacenter);
            return;
        }
        if (empty($this->datacenter->sockets[$datacenter]->pending_outgoing)) {
            $dc_config_number = isset($this->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';

            if (!$this->is_http($datacenter)) {
                if ($this->altervista) {
                    if ($this->datacenter->sockets[$datacenter]->last_http_wait + $this->settings['connection_settings'][$dc_config_number]['timeout'] > time()) {
                        return;
                    }
                    $this->method_call_async('ping', ['ping_id' => $this->random(8)]);
                }
            } elseif ($this->datacenter->sockets[$datacenter]->last_http_wait + $this->settings['connection_settings'][$dc_config_number]['timeout'] > time()) {
                return;
            }
        }

        $this->check_pending_calls_dc($datacenter);
        do {
            if (count($to_ack = $this->datacenter->sockets[$datacenter]->ack_queue)) {
                $this->datacenter->sockets[$datacenter]->pending_outgoing[] = ['_' => 'msgs_ack', 'body' => $this->serialize_object(['type' => 'msgs_ack'], ['msg_ids' => $this->datacenter->sockets[$datacenter]->ack_queue], 'msgs_ack'), 'content_related' => false];
            }

            $has_http_wait = false;
            $messages = [];
            $keys = [];

            foreach ($this->datacenter->sockets[$datacenter]->pending_outgoing as $message) {
                if ($message['_'] === 'http_wait') {
                    $has_http_wait = true;
                    break;
                }
            }

            if ($this->is_http($datacenter) && !$has_http_wait) {
                $dc_config_number = isset($this->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';

                $this->datacenter->sockets[$datacenter]->pending_outgoing[$this->datacenter->sockets[$datacenter]->pending_outgoing_key++] = ['_' => 'http_wait', 'body' => $this->serialize_method('http_wait', ['max_wait' => $this->settings['connection_settings'][$dc_config_number]['timeout'] * 1000 - 100, 'wait_after' => 0, 'max_delay' => 0]), 'content_related' => false];
                $has_http_wait = true;
            }

            $total_length = 0;
            $count = 0;
            ksort($this->datacenter->sockets[$datacenter]->pending_outgoing);
            foreach ($this->datacenter->sockets[$datacenter]->pending_outgoing as $k => $message) {
                if (isset($message['container'])) {
                    unset($this->datacenter->sockets[$datacenter]->pending_outgoing[$k]);
                    continue;
                }

                if ($count > 1020 || $total_length + 32 > 512*1024) {
                    $this->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::NOTICE);
                    break;
                }

                $body = is_callable($message['body']) ? $message['body']() : $message['body'];
                if ($body === null) {
                    $this->logger->logger("Postponing {$message['_']} as encrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    continue;
                }

                $message_id = isset($message['msg_id']) ? $message['msg_id'] : $this->generate_message_id($datacenter);

                $this->logger->logger("Sending {$message['_']} as encrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $MTmessage = ['_' => 'MTmessage', 'msg_id' => $message_id, 'body' => $body, 'seqno' => $this->generate_out_seq_no($datacenter, $message['content_related'])];

                if (isset($message['method']) && $message['method']) {
                    if (!isset($this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) || $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] === false) {
                        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['write_client_info'], $message['_']), \danog\MadelineProto\Logger::NOTICE);
                        $MTmessage['body'] = $this->serialize_method('invokeWithLayer', ['layer' => $this->settings['tl_schema']['layer'], 'query' => $this->serialize_method('initConnection', ['api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash'], 'device_model' => strpos($datacenter, 'cdn') === false ? $this->settings['app_info']['device_model'] : 'n/a', 'system_version' => strpos($datacenter, 'cdn') === false ? $this->settings['app_info']['system_version'] : 'n/a', 'app_version' => $this->settings['app_info']['app_version'], 'system_lang_code' => $this->settings['app_info']['lang_code'], 'lang_code' => $this->settings['app_info']['lang_code'], 'lang_pack' => '', 'query' => $MTmessage['body']])]);
                    } else {
                        if (isset($message['queue'])) {
                            if (!isset($this->datacenter->sockets[$datacenter]->call_queue[$message['queue']])) {
                                $this->datacenter->sockets[$datacenter]->call_queue[$message['queue']] = [];
                            }
                            $MTmessage['body'] = $this->serialize_method('invokeAfterMsgs', ['msg_ids' => $this->datacenter->sockets[$datacenter]->call_queue[$message['queue']], 'query' => $MTmessage['body']]);

                            $this->datacenter->sockets[$datacenter]->call_queue[$message['queue']][$message_id] = $message_id;
                            if (count($this->datacenter->sockets[$datacenter]->call_queue[$message['queue']]) > $this->settings['msg_array_limit']['call_queue']) {
                                reset($this->datacenter->sockets[$datacenter]->call_queue[$message['queue']]);
                                $key = key($this->datacenter->sockets[$datacenter]->call_queue[$message['queue']]);
                                unset($this->datacenter->sockets[$datacenter]->call_queue[$message['queue']][$key]);
                            }
                        }

                        if ($this->settings['requests']['gzip_encode_if_gt'] !== -1 && ($l = strlen($MTmessage['body'])) > $this->settings['requests']['gzip_encode_if_gt']) {
                            if (($g = strlen($gzipped = gzencode($MTmessage['body']))) < $l) {
                                $MTmessage['body'] = $this->serialize_object(['type' => 'gzip_packed'], ['packed_data' => $gzipped], 'gzipped data');
                                $this->logger->logger('Using GZIP compression for '.$message['_'].', saved '.($l - $g).' bytes of data, reduced call size by '.$g * 100 / $l.'%', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                            }
                            unset($gzipped);
                        }
                    }
                }
                $body_length = strlen($MTmessage['body']);
                if ($total_length && $total_length + $body_length + 32 > 655360) {
                    $this->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::NOTICE);
                    break;
                }
                $count++;
                $total_length += $body_length + 32;

                $MTmessage['bytes'] = $body_length;
                $messages[] = $MTmessage;
                $keys[$k] = $message_id;
            }

            if (count($messages) > 1) {
                $this->logger->logger("Wrapping in msg_container as encrypted message for DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $message_id = $this->generate_message_id($datacenter);
                $this->datacenter->sockets[$datacenter]->pending_outgoing[$this->datacenter->sockets[$datacenter]->pending_outgoing_key] = ['_' => 'msg_container', 'container' => array_values($keys), 'content_related' => false];

                $keys[$this->datacenter->sockets[$datacenter]->pending_outgoing_key++] = $message_id;

                $message_data = $this->serialize_object(['type' => ''], ['_' => 'msg_container', 'messages' => $messages], 'container');

                $message_data_length = strlen($message_data);
                $seq_no = $this->generate_out_seq_no($datacenter, false);
            } elseif (count($messages)) {
                $message = $messages[0];
                $message_data = $message['body'];
                $message_data_length = $message['bytes'];
                $message_id = $message['msg_id'];
                $seq_no = $message['seqno'];
            } else {
                $this->logger->logger('NO MESSAGE SENT', \danog\MadelineProto\Logger::WARNING);

                return;
            }

            unset($messages);

            $plaintext = $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'].$this->datacenter->sockets[$datacenter]->session_id.$message_id.pack('VV', $seq_no, $message_data_length).$message_data;
            $padding = $this->posmod(-strlen($plaintext), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $padding = $this->random($padding);
            $message_key = substr(hash('sha256', substr($this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key'], 88, 32).$plaintext.$padding, true), 8, 16);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key']);
            $message = $this->datacenter->sockets[$datacenter]->temp_auth_key['id'].$message_key.$this->ige_encrypt($plaintext.$padding, $aes_key, $aes_iv);

            $this->datacenter->sockets[$datacenter]->send_message($message);
            $sent = time();

            foreach ($keys as $key => $message_id) {
                $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id] = &$this->datacenter->sockets[$datacenter]->pending_outgoing[$key];
                if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['promise'])) {
                    $this->datacenter->sockets[$datacenter]->new_outgoing[$message_id] = $message_id;
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['sent'] = $sent;
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['tries'] = 0;
                }
                unset($this->datacenter->sockets[$datacenter]->pending_outgoing[$key]);
            }

            if ($to_ack) {
                $this->datacenter->sockets[$datacenter]->ack_queue = [];
            }

            if ($has_http_wait) {
                $this->datacenter->sockets[$datacenter]->last_http_wait = $sent;
            } elseif ($this->altervista) {
                $this->datacenter->sockets[$datacenter]->last_http_wait = PHP_INT_MAX;
            }
            //if (!empty($this->datacenter->sockets[$datacenter]->pending_outgoing)) $this->select();
        } while (!empty($this->datacenter->sockets[$datacenter]->pending_outgoing));

        $this->datacenter->sockets[$datacenter]->pending_outgoing_key = 0;
    }

    /**
     * Reading connection and receiving message from server.
     */
    public function recv_message($datacenter)
    {
        if ($this->datacenter->sockets[$datacenter]->must_open) {
            $this->logger->logger('Trying to read from closed socket, sending initial ping');
            if ($this->is_http($datacenter)) {
                $this->send_messages($datacenter);
            } elseif (isset($this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) && $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) {
                $this->method_call('ping', ['ping_id' => 0], ['datacenter' => $datacenter]);
            } else {
                $this->close_and_reopen($datacenter);

                throw new \danog\MadelineProto\NothingInTheSocketException();
            }
        }
        $payload = $this->datacenter->sockets[$datacenter]->read_message();
        if (strlen($payload) === 4) {
            $payload = $this->unpack_signed_int($payload);
            $this->logger->logger("Received $payload from DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return $payload;
        }
        $auth_key_id = substr($payload, 0, 8);
        if ($auth_key_id === "\0\0\0\0\0\0\0\0") {
            $message_id = substr($payload, 8, 8);
            $this->check_message_id($message_id, ['outgoing' => false, 'datacenter' => $datacenter, 'container' => false]);
            $message_length = unpack('V', substr($payload, 16, 4))[1];
            $message_data = substr($payload, 20, $message_length);
            $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id] = [];
        } elseif ($auth_key_id === $this->datacenter->sockets[$datacenter]->temp_auth_key['id']) {
            $message_key = substr($payload, 8, 16);
            $encrypted_data = substr($payload, 24);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key'], false);
            $decrypted_data = $this->ige_decrypt($encrypted_data, $aes_key, $aes_iv);
            /*
            $server_salt = substr($decrypted_data, 0, 8);
            if ($server_salt != $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt']) {
            $this->logger->logger('WARNING: Server salt mismatch (my server salt '.$this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').', \danog\MadelineProto\Logger::WARNING);
            }
             */
            $session_id = substr($decrypted_data, 8, 8);
            if ($session_id != $this->datacenter->sockets[$datacenter]->session_id) {
                throw new \danog\MadelineProto\Exception('Session id mismatch.');
            }
            $message_id = substr($decrypted_data, 16, 8);
            $this->check_message_id($message_id, ['outgoing' => false, 'datacenter' => $datacenter, 'container' => false]);
            $seq_no = unpack('V', substr($decrypted_data, 24, 4))[1];
            // Dunno how to handle any incorrect sequence numbers
            $message_data_length = unpack('V', substr($decrypted_data, 28, 4))[1];
            if ($message_data_length > strlen($decrypted_data)) {
                throw new \danog\MadelineProto\SecurityException('message_data_length is too big');
            }
            if (strlen($decrypted_data) - 32 - $message_data_length < 12) {
                throw new \danog\MadelineProto\SecurityException('padding is too small');
            }
            if (strlen($decrypted_data) - 32 - $message_data_length > 1024) {
                throw new \danog\MadelineProto\SecurityException('padding is too big');
            }
            if ($message_data_length < 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not positive');
            }
            if ($message_data_length % 4 != 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not divisible by 4');
            }
            $message_data = substr($decrypted_data, 32, $message_data_length);
            if ($message_key != substr(hash('sha256', substr($this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key'], 96, 32).$decrypted_data, true), 8, 16)) {
                throw new \danog\MadelineProto\SecurityException('msg_key mismatch');
            }
            $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id] = ['seq_no' => $seq_no];
        } else {
            $this->close_and_reopen($datacenter);

            throw new \danog\MadelineProto\Exception('Got unknown auth_key id');
        }
        $deserialized = $this->deserialize($message_data, ['type' => '', 'datacenter' => $datacenter]);
        $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['content'] = $deserialized;
        $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['response'] = -1;
        $this->datacenter->sockets[$datacenter]->new_incoming[$message_id] = $message_id;
        $this->datacenter->sockets[$datacenter]->last_recv = time();
        $this->datacenter->sockets[$datacenter]->last_http_wait = 0;

        return true;
    }
}
