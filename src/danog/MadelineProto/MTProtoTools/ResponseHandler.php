<?php
/*
Copyright 2016 Daniil Gentili
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

    public function send_msgs_state_info($req_msg_id, $msg_ids)
    {
        $info = '';
        foreach ($msg_ids as $msg_id) {
            $cur_info = 0;
            if (!in_array($msg_id, $this->datacenter->incoming_messages)) {
                if (((int) ((time() + $this->datacenter->time_delta + 30) << 32)) < $msg_id) {
                    $cur_info |= 3;
                } elseif (((int) ((time() + $this->datacenter->time_delta - 300) << 32)) > $msg_id) {
                    $cur_info |= 1;
                } else {
                    $cur_info |= 2;
                }
            } else {
                $cur_info |= 4;
                if ($this->datacenter->incoming_messages[$msg_id]['ack']) {
                    $cur_info |= 8;
                }
            }
            $info .= chr($cur_info);
        }
        $this->datacenter->outgoing_messages[$this->object_call('msgs_state_info', ['req_msg_id' => $req_msg_id, 'info' => $info])]['response'] = $req_msg_id;
    }

    public function handle_messages()
    {
        foreach ($this->datacenter->new_incoming as $current_msg_id) {
            $response = $this->datacenter->incoming_messages[$current_msg_id]['content'];
            \danog\MadelineProto\Logger::log('Received '.$response['_'].'.');
            switch ($response['_']) {
                case 'msgs_ack':
                    foreach ($response['msg_ids'] as $msg_id) {
                        $this->ack_outgoing_message_id($msg_id); // Acknowledge that the server received my message
                    }
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    break;

                case 'rpc_result':
                    $this->ack_incoming_message_id($current_msg_id); // Acknowledge that I received the server's response
                    $this->datacenter->incoming_messages[$current_msg_id]['content'] = $response['result'];
                case 'future_salts':
                    $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my request
                    $this->datacenter->outgoing_messages[$response['req_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    unset($this->datacenter->new_outgoing[$response['req_msg_id']]);
                    break;

                case 'bad_server_salt':
                case 'bad_msg_notification':
                    $this->ack_outgoing_message_id($response['bad_msg_id']); // Acknowledge that the server received my request
                    $this->datacenter->outgoing_messages[$response['bad_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    unset($this->datacenter->new_outgoing[$response['bad_msg_id']]);
                    break;

                case 'pong':
                    foreach ($this->datacenter->outgoing_messages as $msg_id => &$omessage) {
                        if (isset($omessage['content']['args']['ping_id']) && $omessage['content']['args']['ping_id'] == $response['ping_id']) {
                            $this->ack_outgoing_message_id($msg_id);
                            $omessage['response'] = $response['msg_id'];
                            $this->datacenter->incoming_messages[$response['msg_id']]['content'] = $response;
                            unset($this->datacenter->new_incoming[$current_msg_id]);
                            unset($this->datacenter->new_outgoing[$msg_id]);
                        }
                    }
                    break;
                case 'new_session_created':
                    $this->datacenter->temp_auth_key['server_salt'] = $response['server_salt'];
                    $this->ack_incoming_message_id($current_msg_id); // Acknowledge that I received the server's response
                    \danog\MadelineProto\Logger::log('new session created');
                    \danog\MadelineProto\Logger::log($response);
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    break;
                case 'msg_container':

                    \danog\MadelineProto\Logger::log($response['messages']);
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    foreach ($response['messages'] as $message) {
                        $this->check_message_id($message['msg_id'], false, true);
                        $this->datacenter->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body']];
                        $this->datacenter->new_incoming[$message['msg_id']] = $message['msg_id'];

                        $this->handle_messages();
                    }
                    break;
                case 'msg_copy':
                    $this->ack_incoming_message_id($current_msg_id); // Acknowledge that I received the server's response
                    if (isset($this->datacenter->incoming_messages[$response['orig_message']['msg_id']])) {
                        $this->ack_incoming_message_id($response['orig_message']['msg_id']); // Acknowledge that I received the server's response
                    } else {
                        $this->check_message_id($message['orig_message']['msg_id'], false, true);
                        $this->datacenter->incoming_messages[$message['orig_message']['msg_id']] = ['content' => $response['orig_message']];
                        $this->datacenter->new_incoming[$message['orig_message']['msg_id']] = $message['orig_message']['msg_id'];

                        $this->handle_messages();
                    }
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    break;
                case 'http_wait':

                    \danog\MadelineProto\Logger::log($response);
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    break;
                case 'msgs_state_info':
                    $this->datacenter->outgoing_messages[$response['req_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    unset($this->datacenter->new_outgoing[$response['req_msg_id']]);
                    break;
                case 'msgs_state_req':
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($current_msg_id, $response['msg_ids']);
                    break;
                case 'msgs_all_info':
                    unset($this->datacenter->new_incoming[$current_msg_id]);

                    foreach ($response['msg_ids'] as $key => $msg_id) {
                        $status = 'Status for message id '.$msg_id.': ';
                        if (($response['info'][$key] & 4) == 1) {
                            $this->ack_outgoing_message_id($msg_id);
                        }
                        foreach ($msgs_info_flags as $flag => $description) {
                            if (($response['info'][$key] & $flag) == 1) {
                                $status .= $description;
                            }
                        }
                        \danog\MadelineProto\Logger::log($status);
                    }
                    break;
                case 'msg_new_detailed_info':
                case 'msg_detailed_info':

                    if (isset($this->datacenter->incoming_messages[$response['answer_msg_id']])) {
                        $this->ack_incoming_message_id($response['answer_msg_id']);
                    } else {
                        $this->object_call('msg_resend_req', ['msg_ids' => [$response['answer_msg_id']]]);
                    }
                    break;
                case 'msg_resend_req':
                    $ok = true;
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    foreach ($response['msg_ids'] as $msg_id) {
                        if (!isset($this->datacenter->outgoing_messages[$msg_id]) || isset($this->datacenter->incoming_messages[$msg_id])) {
                            $ok = false;
                        }
                    }
                    if ($ok) {
                        foreach ($response['msg_ids'] as $msg_id) {
                            $this->object_call($this->datacenter->outgoing_messages[$msg_id]['content']['method'], $this->datacenter->outgoing_messages[$msg_id]['content']['args']);
                        }
                    } else {
                        $this->send_msgs_state_info($current_msg_id, $response['msg_ids']);
                    }
                    break;
                case 'msg_resend_ans_req':
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($response['msg_ids']);
                    foreach ($response['msg_ids'] as $msg_id) {
                        if (isset($this->datacenter->incoming_messages[$msg_id]) && isset($this->datacenter->outgoing_messages[$this->datacenter->incoming_messages[$msg_id]['response']])) {
                            $this->object_call($this->datacenter->outgoing_messages[$this->datacenter->incoming_messages[$msg_id]['response']]['method'], $this->datacenter->outgoing_messages[$this->datacenter->incoming_messages[$msg_id]['response']]['args']);
                        }
                    }
                    break;
                default:
                    $this->ack_incoming_message_id($current_msg_id); // Acknowledge that I received the server's response
                    $response_type = $this->tl->constructors->find_by_predicate($response['_'])['type'];
                    switch ($response_type) {
                        case 'Updates':
                            unset($this->datacenter->new_incoming[$current_msg_id]);
                            $this->handle_updates($response);
                            break;
                        default:
                            \danog\MadelineProto\Logger::log('Trying to assign a response of type '.$response_type.' to its request...');
                            foreach ($this->datacenter->new_outgoing as $key => $expecting) {
                                \danog\MadelineProto\Logger::log('Does the request of return type '.$expecting['type'].' and msg_id '.$expecting['msg_id'].' match?');
                                if ($response_type == $expecting['type']) {
                                    \danog\MadelineProto\Logger::log('Yes');
                                    $this->datacenter->outgoing_messages[$expecting['msg_id']]['response'] = $current_msg_id;
                                    unset($this->datacenter->new_outgoing[$key]);
                                    unset($this->datacenter->new_incoming[$current_msg_id]);

                                    return;
                                }
                                \danog\MadelineProto\Logger::log('No');
                            }
                            throw new \danog\MadelineProto\ResponseException('Dunno how to handle '.PHP_EOL.var_export($response, true));
                            break;
                    }
                    break;
            }
        }
    }
}
