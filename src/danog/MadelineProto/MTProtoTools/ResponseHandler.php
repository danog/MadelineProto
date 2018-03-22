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
 * Manages responses.
 */
trait ResponseHandler
{
    public function send_msgs_state_info($req_msg_id, $msg_ids, $datacenter)
    {
        $info = '';
        foreach ($msg_ids as $msg_id) {
            $cur_info = 0;
            if (!isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id])) {
                $msg_id = new \phpseclib\Math\BigInteger(strrev($msg_id), 256);
                if ((new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$datacenter]->time_delta + 30))->bitwise_leftShift(32)->compare($msg_id) < 0) {
                    $cur_info |= 3;
                } elseif ((new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$datacenter]->time_delta - 300))->bitwise_leftShift(32)->compare($msg_id) > 0) {
                    $cur_info |= 1;
                } else {
                    $cur_info |= 2;
                }
            } else {
                $cur_info |= 4;
                if ($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['ack']) {
                    $cur_info |= 8;
                }
            }
            $info .= chr($cur_info);
        }
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->object_call('msgs_state_info', ['req_msg_id' => $req_msg_id, 'info' => $info], ['datacenter' => $datacenter])]['response'] = $req_msg_id;
    }

    public function handle_messages($datacenter)
    {
        $only_updates = true;
        foreach ($this->datacenter->sockets[$datacenter]->new_incoming as $current_msg_id) {
            $unset = false;
            \danog\MadelineProto\Logger::log((isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['from_container']) ? 'Inside of container, received ' : 'Received ').$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_'].' from DC '.$datacenter, \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            //\danog\MadelineProto\Logger::log($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            switch ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_']) {
                case 'msgs_ack':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        $this->ack_outgoing_message_id($msg_id, $datacenter);
                        // Acknowledge that the server received my message
                    }
                    break;
                case 'rpc_result':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]);
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that I received the server's response
                    $this->ack_outgoing_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'], $datacenter);
                    // Acknowledge that the server received my request
                    //\danog\MadelineProto\Logger::log($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]);
                    //\danog\MadelineProto\Logger::log($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]['response'] = $current_msg_id;
                    $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result'];
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    break;
                case 'future_salts':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]);
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    $this->ack_outgoing_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'], $datacenter);
                    // Acknowledge that the server received my request
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]['response'] = $current_msg_id;
                    break;
                case 'bad_server_salt':
                case 'bad_msg_notification':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    $this->ack_outgoing_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['bad_msg_id'], $datacenter);
                    // Acknowledge that the server received my request

                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['bad_msg_id']]);

                    switch ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['error_code']) {
                            case 48:
                                $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['new_server_salt'];

                                throw new \danog\MadelineProto\Exception('Got bad message notification');
                            case 16:
                            case 17:
                                \danog\MadelineProto\Logger::log('Received bad_msg_notification: '.self::BAD_MSG_ERROR_CODES[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['error_code']], \danog\MadelineProto\Logger::WARNING);
                                $this->datacenter->sockets[$datacenter]->time_delta = (int) (new \phpseclib\Math\BigInteger(strrev($current_message_id), 256))->bitwise_rightShift(32)->subtract(new \phpseclib\Math\BigInteger(time()))->toString();
                                \danog\MadelineProto\Logger::log('Set time delta to '.$this->datacenter->sockets[$datacenter]->time_delta, \danog\MadelineProto\Logger::WARNING);
                                $this->reset_session();
                                $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                                $this->init_authorization();

                                throw new \danog\MadelineProto\Exception('Got bad message notification');
                    }
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['bad_msg_id']]['response'] = $current_msg_id;
                    break;
                case 'pong':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]);
                    $this->ack_outgoing_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id'], $datacenter);
                    // Acknowledge that the server received my request
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]['response'] = $current_msg_id;
                    break;
                case 'new_session_created':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['server_salt'];
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that I received the server's response
                    if ($this->authorized === self::LOGGED_IN && !$this->initing_authorization && $this->datacenter->sockets[$this->datacenter->curdc]->temp_auth_key !== null) {
                        $this->get_updates_difference();
                    }
                    $unset = true;
                    break;
                case 'msg_container':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['messages'] as $message) {
                        $this->check_message_id($message['msg_id'], ['outgoing' => false, 'datacenter' => $datacenter, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body'], 'from_container' => true];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['msg_id']] = $message['msg_id'];
                        $this->handle_messages($datacenter);
                    }
                    $unset = true;
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    break;
                case 'msg_copy':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that I received the server's response
                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id'], $datacenter);
                    // Acknowledge that I received the server's response
                    } else {
                        $this->check_message_id($message['orig_message']['msg_id'], ['outgoing' => false, 'datacenter' => $datacenter, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['orig_message']['msg_id']] = ['content' => $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['orig_message']['msg_id']] = $message['orig_message']['msg_id'];
                        $this->handle_messages($datacenter);
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $unset = true;
                    break;
                case 'http_wait':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    \danog\MadelineProto\Logger::log($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'], \danog\MadelineProto\Logger::NOTICE);
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $unset = true;
                    break;
                case 'msgs_state_info':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]);
                    $unset = true;
                    break;
                case 'msgs_state_req':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    break;
                case 'msgs_all_info':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $key => $msg_id) {
                        $msg_id = new \phpseclib\Math\BigInteger(strrev($msg_id), 256);
                        $status = 'Status for message id '.$msg_id.': ';
                        if (($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['info'][$key] & 4) !== 0) {
                            $this->ack_outgoing_message_id($msg_id, $datacenter);
                        }
                        foreach (self::MSGS_INFO_FLAGS as $flag => $description) {
                            if (($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['info'][$key] & $flag) !== 0) {
                                $status .= $description;
                            }
                        }
                        \danog\MadelineProto\Logger::log($status, \danog\MadelineProto\Logger::NOTICE);
                    }
                    break;
                case 'msg_detailed_info':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']])) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                            $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]['response'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'];
                            unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]);
                        } else {
                            $this->object_call('msg_resend_req', ['msg_ids' => [$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']]], ['datacenter' => $datacenter]);
                        }
                    }
                case 'msg_new_detailed_info':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'], $datacenter);
                    } else {
                        $this->object_call('msg_resend_req', ['msg_ids' => [$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']]], ['datacenter' => $datacenter]);
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    break;
                case 'msg_resend_req':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    $ok = true;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        if (!isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$msg_id]) || isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id])) {
                            $ok = false;
                        }
                    }
                    if ($ok) {
                        foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                            $this->object_call($this->datacenter->sockets[$datacenter]->outgoing_messages[$msg_id]['content']['method'], $this->datacenter->sockets[$datacenter]->outgoing_messages[$msg_id]['content']['args'], ['datacenter' => $datacenter]);
                        }
                    } else {
                        $this->send_msgs_state_info($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    }
                    break;
                case 'msg_resend_ans_req':
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]) && isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']])) {
                            $this->object_call($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['method'], $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['args'], ['datacenter' => $datacenter]);
                        }
                    }
                    break;
                default:
                    $this->check_in_seq_no($datacenter, $current_msg_id);
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that I received the server's response
                    $response_type = $this->constructors->find_by_predicate($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_'])['type'];
                    switch ($response_type) {
                        case 'Updates':
                            unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                            $unset = true;

                            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['users'])) {
                                $this->add_users($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['users']);
                            }
                            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['chats'])) {
                                $this->add_chats($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['chats']);
                            }

                            if (strpos($datacenter, 'cdn') === false) {
                                $this->handle_updates($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                            }
                            $only_updates = true && $only_updates;
                            break;
                        default:
                            $only_updates = false;
                            \danog\MadelineProto\Logger::log('Trying to assign a response of type '.$response_type.' to its request...', \danog\MadelineProto\Logger::VERBOSE);
                            foreach ($this->datacenter->sockets[$datacenter]->new_outgoing as $key => $expecting) {
                                \danog\MadelineProto\Logger::log('Does the request of return type '.$expecting['type'].' match?', \danog\MadelineProto\Logger::VERBOSE);
                                if ($response_type === $expecting['type']) {
                                    \danog\MadelineProto\Logger::log('Yes', \danog\MadelineProto\Logger::VERBOSE);
                                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$expecting['msg_id']]['response'] = $current_msg_id;
                                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$key]);
                                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                                    break 2;
                                }
                                \danog\MadelineProto\Logger::log('No', \danog\MadelineProto\Logger::VERBOSE);
                            }

                            throw new \danog\MadelineProto\ResponseException('Dunno how to handle '.PHP_EOL.var_export($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'], true));
                            break;
                    }
                    break;
            }
            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['users'])) {
                $this->add_users($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['users']);
            }
            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['chats'])) {
                $this->add_chats($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['chats']);
            }
            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['chat'])) {
                $this->add_chats([$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['chat']]);
            }
            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result']['users'])) {
                $this->add_users($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result']['users']);
            }
            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result']['chats'])) {
                $this->add_chats($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result']['chats']);
            }
            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result']['_'])) {
                switch ($this->constructors->find_by_predicate($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result']['_'])['type']) {
                    case 'Update':
                        $this->handle_update($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result']);
                        break;
                }
            }
            if ($unset) {
                unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]);
            }
        }

        return $only_updates;
    }

    public function handle_rpc_error($server_answer, &$aargs)
    {
        if (in_array($server_answer['error_message'], ['PERSISTENT_TIMESTAMP_EMPTY', 'PERSISTENT_TIMESTAMP_OUTDATED', 'PERSISTENT_TIMESTAMP_INVALID'])) {
            throw new \danog\MadelineProto\PTSException($server_answer['error_message']);
        }
        switch ($server_answer['error_code']) {
            case 500:
                throw new \danog\MadelineProto\Exception('Re-executing query after server error...');
            case 303:
                $this->datacenter->curdc = $aargs['datacenter'] = (int) preg_replace('/[^0-9]+/', '', $server_answer['error_message']);

                throw new \danog\MadelineProto\Exception('Received request to switch to DC '.$this->datacenter->curdc);
            case 401:
                switch ($server_answer['error_message']) {
                    case 'USER_DEACTIVATED':
                    case 'SESSION_REVOKED':
                    case 'SESSION_EXPIRED':
                        \danog\MadelineProto\Logger::log($server_answer['error_message'], \danog\MadelineProto\Logger::FATAL_ERROR);
                        foreach ($this->datacenter->sockets as $socket) {
                            $socket->temp_auth_key = null;
                            $socket->auth_key = null;
                            $socket->authorized = false;
                        }
                        $this->authorized = self::NOT_LOGGED_IN;
                        $this->authorization = null;
                        $this->init_authorization();

                        throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                    case 'AUTH_KEY_UNREGISTERED':
                    case 'AUTH_KEY_INVALID':
                        if ($this->authorized !== self::LOGGED_IN) {
                            throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                        }
                        \danog\MadelineProto\Logger::log('Auth key not registered, resetting temporary and permanent auth keys...', \danog\MadelineProto\Logger::ERROR);

                        $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key = null;
                        $this->datacenter->sockets[$aargs['datacenter']]->auth_key = null;
                        $this->datacenter->sockets[$aargs['datacenter']]->authorized = false;
                        if ($this->authorized_dc === $aargs['datacenter'] && $this->authorized === self::LOGGED_IN) {
                            \danog\MadelineProto\Logger::log('Permanent auth key was main authorized key, logging out...', \danog\MadelineProto\Logger::FATAL_ERROR);
                            foreach ($this->datacenter->sockets as $socket) {
                                $socket->temp_auth_key = null;
                                $socket->auth_key = null;
                                $socket->authorized = false;
                            }
                            $this->authorized = self::NOT_LOGGED_IN;
                            $this->authorization = null;
                            $this->init_authorization();

                            throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                        }
                        $this->init_authorization();

                        throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                    case 'AUTH_KEY_PERM_EMPTY':
                        if ($this->authorized !== self::LOGGED_IN) {
                            throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                        }
                        \danog\MadelineProto\Logger::log('Temporary auth key not bound, resetting temporary auth key...', \danog\MadelineProto\Logger::ERROR);

                        $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key = null;
                        $this->init_authorization();
                        // idk
                        throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                }
            case 420:
                $seconds = preg_replace('/[^0-9]+/', '', $server_answer['error_message']);
                $limit = isset($aargs['FloodWaitLimit']) ? $aargs['FloodWaitLimit'] : $this->settings['flood_timeout']['wait_if_lt'];
                if (is_numeric($seconds) && $seconds < $limit) {
                    \danog\MadelineProto\Logger::log('Flood, waiting '.$seconds.' seconds...', \danog\MadelineProto\Logger::NOTICE);
                    sleep($seconds);

                    throw new \danog\MadelineProto\Exception('Re-executing query...');
                }

            default:
                throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
        }
    }

    public function handle_pending_updates()
    {
        if ($this->postpone_updates) {
            return false;
        }
        if (count($this->pending_updates)) {
            \danog\MadelineProto\Logger::log('Parsing pending updates...');
            foreach (array_keys($this->pending_updates) as $key) {
                if (isset($this->pending_updates[$key])) {
                    $updates = $this->pending_updates[$key];
                    unset($this->pending_updates[$key]);
                    $this->handle_updates($updates);
                }
            }
        }
    }

    public function handle_updates($updates)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($this->postpone_updates) {
            \danog\MadelineProto\Logger::log('Postpone update handling', \danog\MadelineProto\Logger::VERBOSE);
            $this->pending_updates[] = $updates;

            return false;
        }
        $this->handle_pending_updates();
        \danog\MadelineProto\Logger::log('Parsing updates received via the socket...', \danog\MadelineProto\Logger::VERBOSE);

        try {
            $this->postpone_updates = true;

            $opts = [];
            foreach (['date', 'seq', 'seq_start'] as $key) {
                if (isset($updates[$key])) {
                    $opts[$key] = $updates[$key];
                }
            }
            switch ($updates['_']) {
            case 'updates':
            case 'updatesCombined':
                foreach ($updates['updates'] as $update) {
                    $this->handle_update($update, $opts);
                }
                break;
            case 'updateShort':
                $this->handle_update($updates['update'], $opts);
                break;
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $from_id = isset($updates['from_id']) ? $updates['from_id'] : ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                if (!$this->peer_isset($from_id) || !$this->peer_isset($to_id) || isset($updates['via_bot_id']) && !$this->peer_isset($updates['via_bot_id']) || isset($updates['entities']) && !$this->entities_peer_isset($updates['entities']) || isset($updates['fwd_from']) && !$this->fwd_peer_isset($updates['fwd_from'])) {
                    \danog\MadelineProto\Logger::log('getDifference: good - getting user for updateShortMessage', \danog\MadelineProto\Logger::VERBOSE);
                    $this->get_updates_difference();
                }
                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $from_id;

                try {
                    $message['to_id'] = $this->get_info($to_id)['Peer'];
                } catch (\danog\MadelineProto\Exception $e) {
                    \danog\MadelineProto\Logger::log('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    //$this->pending_updates[] = $updates;
                    break;
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    \danog\MadelineProto\Logger::log('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    //$this->pending_updates[] = $updates;
                    break;
                }
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->handle_update($update, $opts);
                break;
            case 'updateShortSentMessage':
                //$this->set_update_state(['date' => $updates['date']]);
                break;
            case 'updatesTooLong':
                $this->get_updates_difference();
                break;
            default:
                throw new \danog\MadelineProto\ResponseException('Unrecognized update received: '.var_export($updates, true));
                break;
        }
        } finally {
            $this->postpone_updates = false;
        }
    }
}
