<?php
/*
Copyright 2016-2017 Daniil Gentili
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
    private $pending_updates = [];
    private $bad_msg_error_codes = [
        16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the “correct” msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)',
        17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)',
        18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)',
        19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)',
        20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not',
        32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)',
        33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)',
        34 => 'an even msg_seqno expected (irrelevant message), but odd received',
        35 => 'odd msg_seqno expected (relevant message), but even received',
        48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)',
        64 => 'invalid container.',
    ];
    private $msgs_info_flags = [
        1   => 'nothing is known about the message (msg_id too low, the other party may have forgotten it)',
        2   => 'message not received (msg_id falls within the range of stored identifiers; however, the other party has certainly not received a message like that)',
        3   => 'message not received (msg_id too high; however, the other party has certainly not received it yet)',
        4   => 'message received (note that this response is also at the same time a receipt acknowledgment)',
        8   => ' and message already acknowledged',
        16  => ' and message not requiring acknowledgment',
        32  => ' and RPC query contained in message being processed or processing already complete',
        64  => ' and content-related response to message already generated',
        128 => ' and other party knows for a fact that message is already received',
    ];

    public function send_msgs_state_info($req_msg_id, $msg_ids, $datacenter)
    {
        $info = '';
        foreach ($msg_ids as $msg_id) {
            $cur_info = 0;
            if (!in_array($msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages)) {
                
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
        $only_updates = false;
        $first = true;
        foreach ($this->datacenter->sockets[$datacenter]->new_incoming as $current_msg_id) {
            $last_was_updates = false;
            $unset = false;
            \danog\MadelineProto\Logger::log(['Received '.$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_'].'.'], \danog\MadelineProto\Logger::VERBOSE);

            switch ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_']) {
                case 'msgs_ack':
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        $this->ack_outgoing_message_id($msg_id, $datacenter); // Acknowledge that the server received my message
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    break;

                case 'rpc_result':
                    $this->ack_incoming_message_id($current_msg_id, $datacenter); // Acknowledge that I received the server's response
                    $this->ack_outgoing_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'], $datacenter); // Acknowledge that the server received my request
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]);
                    $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result'];
                    break;
                case 'future_salts':
                    $this->ack_outgoing_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'], $datacenter); // Acknowledge that the server received my request
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]);
                    break;
                case 'rpc_error':
                    $this->handle_rpc_error($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'], $datacenter);
                    break;

                case 'bad_server_salt':
                case 'bad_msg_notification':
                    $this->ack_outgoing_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['bad_msg_id'], $datacenter); // Acknowledge that the server received my request
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['bad_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['bad_msg_id']]);
                    break;

                case 'pong':
                    foreach ($this->datacenter->sockets[$datacenter]->outgoing_messages as $msg_id => &$omessage) {
                        if (isset($omessage['content']['args']['ping_id']) && $omessage['content']['args']['ping_id'] === $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['ping_id']) {
                            $this->ack_outgoing_message_id($msg_id, $datacenter);
                            $omessage['response'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id'];
                            $this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]['content'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'];
                            unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                            unset($this->datacenter->sockets[$datacenter]->new_outgoing[$msg_id]);
                        }
                    }
                    break;
                case 'new_session_created':
                    $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['server_salt'];
                    $this->ack_incoming_message_id($current_msg_id, $datacenter); // Acknowledge that I received the server's response
                    if ($this->authorized) {
                        $this->force_get_updates_difference();
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $unset = true;
                    break;
                case 'msg_container':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['messages'] as $message) {
                        $this->check_message_id($message['msg_id'],  ['outgoing' => false, 'datacenter' => $datacenter, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body']];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['msg_id']] = $message['msg_id'];

                        $this->handle_messages($datacenter);
                    }
                    $unset = true;
                    break;
                case 'msg_copy':
                    $this->ack_incoming_message_id($current_msg_id, $datacenter); // Acknowledge that I received the server's response
                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id'], $datacenter); // Acknowledge that I received the server's response
                    } else {
                        $this->check_message_id($message['orig_message']['msg_id'],  ['outgoing' => false, 'datacenter' => $datacenter, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['orig_message']['msg_id']] = ['content' => $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['orig_message']['msg_id']] = $message['orig_message']['msg_id'];

                        $this->handle_messages($datacenter);
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $unset = true;
                    break;
                case 'http_wait':

                    \danog\MadelineProto\Logger::log([$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']], \danog\MadelineProto\Logger::NOTICE);
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $unset = true;
                    break;
                case 'msgs_state_info':
                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id']]);
                    $unset = true;
                    break;
                case 'msgs_state_req':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    break;
                case 'msgs_all_info':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $key => $msg_id) {
                        $msg_id = new \phpseclib\Math\BigInteger(strrev($msg_id), 256);
                        $status = 'Status for message id '.$msg_id.': ';
                        if (($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['info'][$key] & 4) === 1) {
                            $this->ack_outgoing_message_id($msg_id, $datacenter);
                        }
                        foreach ($this->msgs_info_flags as $flag => $description) {
                            if (($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['info'][$key] & $flag) === 1) {
                                $status .= $description;
                            }
                        }
                        \danog\MadelineProto\Logger::log([$status], \danog\MadelineProto\Logger::NOTICE);
                    }
                    break;
                case 'msg_detailed_info':
                    if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']])) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                            $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]['response'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'];
                            unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]);
                        }
                    }
                case 'msg_new_detailed_info':
                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'], $datacenter);
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    break;
                case 'msg_resend_req':
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
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]) && isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']])) {
                            $this->object_call($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['method'], $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['args'], ['datacenter' => $datacenter]);
                        }
                    }
                    break;
                default:
                    $this->ack_incoming_message_id($current_msg_id, $datacenter); // Acknowledge that I received the server's response
                    $response_type = $this->constructors->find_by_predicate($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_'])['type'];
                    switch ($response_type) {
                        case 'Updates':
                            unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                            $unset = true;
                            $this->handle_updates($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                            $last_was_updates = true;
                            break;
                        default:
                            \danog\MadelineProto\Logger::log(['Trying to assign a response of type '.$response_type.' to its request...'], \danog\MadelineProto\Logger::VERBOSE);
                            foreach ($this->datacenter->sockets[$datacenter]->new_outgoing as $key => $expecting) {
                                \danog\MadelineProto\Logger::log(['Does the request of return type '.$expecting['type'].' match?'], \danog\MadelineProto\Logger::VERBOSE);
                                if ($response_type === $expecting['type']) {
                                    \danog\MadelineProto\Logger::log(['Yes'], \danog\MadelineProto\Logger::VERBOSE);
                                    $this->datacenter->sockets[$datacenter]->outgoing_messages[$expecting['msg_id']]['response'] = $current_msg_id;
                                    unset($this->datacenter->sockets[$datacenter]->new_outgoing[$key]);
                                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                                    break 2;
                                }
                                \danog\MadelineProto\Logger::log(['No'], \danog\MadelineProto\Logger::VERBOSE);
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
            $only_updates = ($only_updates || $first) && $last_was_updates;
            $first = false;
            if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['seq_no']) && ($seq_no = $this->generate_in_seq_no($datacenter, $this->content_related($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_']))) !== $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['seq_no']) {
                throw new \danog\MadelineProto\SecurityException('Seqno mismatch (should be '.$seq_no.', is '.$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['seq_no']);
            }
            if ($unset) {
                unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]);
            }
        }
        return $only_updates;
    }
    public function handle_rpc_error($server_answer, &$datacenter) {
        
                        switch ($server_answer['error_code']) {
                            case 303:
                                $this->datacenter->curdc = $datacenter = preg_replace('/[^0-9]+/', '', $server_answer['error_message']);
                                throw new \danog\MadelineProto\Exception('Received request to switch to DC '.$this->datacenter->curdc);
                                
                            case 401:
                                switch ($server_answer['error_message']) {
                                    case 'USER_DEACTIVATED':
                                    case 'SESSION_REVOKED':
                                    case 'SESSION_EXPIRED':
                                        $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                                        $this->datacenter->sockets[$datacenter]->auth_key = null;
                                        $this->authorized = false;
                                        $this->authorization = null;
                                        $this->init_authorization(); // idk
                                        throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                                    case 'AUTH_KEY_UNREGISTERED':
                                    case 'AUTH_KEY_INVALID':
                                        $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                                        $this->init_authorization(); // idk
                                        throw new \danog\MadelineProto\Exception($server_answer['error_message'], $server_answer['error_code']);
                                }

                            case 420:
                                $seconds = preg_replace('/[^0-9]+/', '', $server_answer['error_message']);
                                if (is_numeric($seconds) && isset($this->settings['flood_timeout']['wait_if_lt']) && $seconds < $this->settings['flood_timeout']['wait_if_lt']) {
                                    \danog\MadelineProto\Logger::log(['Flood, waiting '.$seconds.' seconds...'], \danog\MadelineProto\Logger::NOTICE);
                                    sleep($seconds);
                                    throw new \danog\MadelineProto\Exception('Re-executing query...');
                                }
                            default:
                                throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                                break;
                        }
    }
    public function handle_pending_updates()
    {
        \danog\MadelineProto\Logger::log(['Parsing pending updates...'], \danog\MadelineProto\Logger::VERBOSE);
        foreach ($this->pending_updates as $updates) {
            $this->handle_updates($updates);
        }
    }

    public function handle_updates($updates)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        \danog\MadelineProto\Logger::log(['Parsing updates received via the socket...'], \danog\MadelineProto\Logger::VERBOSE);
        if ($this->getting_state) {
            \danog\MadelineProto\Logger::log(['Getting state, handle later'], \danog\MadelineProto\Logger::VERBOSE);
            $this->pending_updates []= $updates;

            return false;
        }
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
                $to_id = isset($updates['chat_id'])
                    ? -$updates['chat_id']
                    : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);

                if (!$this->peer_isset($from_id) ||
                    !$this->peer_isset($to_id) ||
                    (isset($updates['via_bot_id']) && !$this->peer_isset($updates['via_bot_id'])) ||
                    (isset($updates['entities']) && !$this->entities_peer_isset($updates['entities'])) ||
                    (isset($updates['fwd_from']) && !$this->fwd_peer_isset($updates['fwd_from']))) {
                    \danog\MadelineProto\Logger::log(['getDifference: good - getting user for updateShortMessage'], \danog\MadelineProto\Logger::VERBOSE);

                    return $this->get_updates_difference();
                }

                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $from_id;
                $message['to_id'] = $this->get_info($to_id)['Peer'];
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->handle_update($update, $opts);
                break;
            case 'updateShortSentMessage':
                //$this->set_update_state(['date' => $updates['date']]);
                break;
            case 'updatesTooLong':
                $this->force_get_updates_difference();
                break;
            default:
                throw new \danog\MadelineProto\ResponseException('Unrecognized update received: '.var_export($updates));
                break;
        }
    }

    public function handle_decrypted_update($update)
    {
        if (isset($update['message']['decrypted_message']['random_bytes']) && strlen($update['message']['decrypted_message']['random_bytes']) < 15) {
            throw new \danog\MadelineProto\ResponseException('random_bytes is too short!');
        }
        $this->secret_chats[$update['message']['chat_id']]['incoming'][] = $update['message'];
        switch ($update['message']['decrypted_message']['_']) {
            case 'decryptedMessageService':
            switch ($update['message']['decrypted_message']['action']) {
                case 'decryptedMessageActionRequestKey':
                $this->accept_rekey($update['message']['chat_id'], $update['message']['decrypted_message']['action']);

                return;

                case 'decryptedMessageActionAcceptKey':
                $this->commit_rekey($update['message']['chat_id'], $update['message']['decrypted_message']['action']);

                return;

                case 'decryptedMessageActionCommitKey':
                $this->complete_rekey($update['message']['chat_id'], $update['message']['decrypted_message']['action']);

                return;

                case 'decryptedMessageActionNotifyLayer':
                $this->secret_chats[$update['message']['chat_id']]['layer'] = $update['message']['decrypted_message']['action']['layer'];
                if ($update['message']['decrypted_message']['action']['layer'] >= 17 && time() - $this->secret_chats[$update['message']['chat_id']]['created'] > 15) {
                    $this->notify_layer($update['message']['chat_id']);
                }

                return;

                case 'decryptedMessageActionSetMessageTTL':
                $this->secret_chats[$update['message']['chat_id']]['ttl'] = $update['message']['decrypted_message']['action']['ttl_seconds'];

                return;

                case 'decryptedMessageActionResend':
                foreach ($this->secret_chats[$update['message']['chat_id']]['outgoing'] as $seq => $update['message']['decrypted_message']) {
                    if ($seq >= $update['message']['decrypted_message']['action']['start_seq_no'] && $seq <= $update['message']['decrypted_message']['action']['end_seq_no']) {
                        throw new \danog\MadelineProto\ResponseException('Resending of messages is not yet supported');
//                        $this->send_encrypted_message($update['message']['chat_id'], $update['message']['decrypted_message']);
                    }
                }

                return;
                default:
//                $this->save_update(['_' => 'updateNewDecryptedMessage', 'peer' => $this->secret_chats[$update['message']['chat_id']]['InputEncryptedChat'], 'in_seq_no' => $this->get_in_seq_no($update['message']['chat_id']), 'out_seq_no' => $this->get_out_seq_no($update['message']['chat_id']), 'message' => $update['message']['decrypted_message']]);
                $this->save_update($update);
                }
                break;
            case 'decryptedMessage':
                $this->save_update($update);
                break;
            case 'decryptedMessageLayer':
                $update['message']['decrypted_message'] = $update['message']['decrypted_message']['message'];
                $this->handle_decrypted_update($update);
                break;
            default:
                throw new \danog\MadelineProto\ResponseException('Unrecognized decrypted message received: '.var_export($update));
                break;
        }
    }
}
