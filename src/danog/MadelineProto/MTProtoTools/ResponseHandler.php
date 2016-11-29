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
class ResponseHandler extends MsgIdHandler
{
    public function handle_messages($expecting)
    {
        foreach ($this->datacenter->new_incoming as $current_msg_id) {
            $response = $this->datacenter->incoming_messages[$current_msg_id]['content'];

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
                    break;

                case 'bad_server_salt':
                case 'bad_msg_notification':
                    $this->ack_outgoing_message_id($response['bad_msg_id']); // Acknowledge that the server received my request
                    $this->datacenter->outgoing_messages[$response['bad_msg_id']]['response'] = $current_msg_id;
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    break;

                case 'pong':
                    foreach ($this->datacenter->outgoing_messages as $msg_id => &$omessage) {
                        if (isset($omessage['content']['args']['ping_id']) && $omessage['content']['args']['ping_id'] == $response['ping_id']) {
                            $this->ack_outgoing_message_id($msg_id);
                            $omessage['response'] = $response['msg_id'];
                            $this->datacenter->incoming_messages[$response['msg_id']]['content'] = $response;
                            unset($this->datacenter->new_incoming[$current_msg_id]);
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
                    \danog\MadelineProto\Logger::log('Received container.');
                    \danog\MadelineProto\Logger::log($response['messages']);
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    foreach ($response['messages'] as $message) {
                        $this->check_message_id($message['msg_id'], false, true);
                        $this->datacenter->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body']];
                        $this->datacenter->new_incoming[$message['msg_id']] = $message['msg_id'];

                        $this->handle_messages($expecting);
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

                        $this->handle_messages($expecting);
                    }
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    break;
                case 'http_wait':
                    \danog\MadelineProto\Logger::log('Received http wait.');
                    \danog\MadelineProto\Logger::log($response);
                    unset($this->datacenter->new_incoming[$current_msg_id]);
                    break;
                case 'rpc_answer_dropped_running':
                case 'rpc_answer_dropped':
                    $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
                default:
                    $this->ack_incoming_message_id($current_msg_id); // Acknowledge that I received the server's response
                    if ($this->tl->constructors->find_by_predicate($this->datacenter->response['_'])['type'] == $expecting['type']) {
                        $this->datacenter->outgoing_messages[$expecting['msg_id']]['response'] = $response;
                        unset($this->datacenter->new_incoming[$current_msg_id]);
                    } else {
                        throw new \danog\MadelineProto\ResponseException('Dunno how to handle '.PHP_EOL.var_export($response, true));
                    }
                    break;
            }
        }
    }
}
