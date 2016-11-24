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
    public function handle_message($last_sent, $last_received)
    {
        $response = $this->datacenter->incoming_messages[$last_received]['content'];

        switch ($response['_']) {
            case 'msgs_ack':
                foreach ($response['msg_ids'] as $msg_id) {
                    $this->ack_outgoing_message_id($msg_id); // Acknowledge that the server received my message
                }
                break;

            case 'rpc_result':
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my request
                $this->datacenter->outgoing_messages[$response['req_msg_id']]['response'] = $last_received;
                $this->datacenter->incoming_messages[$last_received]['content'] = $response['result'];
                return $this->handle_message($last_sent, $last_received);
                break;

            case 'future_salts':
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my request
                $this->datacenter->outgoing_messages[$response['req_msg_id']]['response'] = $last_received;
                $this->datacenter->incoming_messages[$last_received]['content'] = $response;
                break;

            case 'bad_msg_notification':
                $error_codes = [
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
                throw new \danog\MadelineProto\RPCErrorException('Received bad_msg_notification for '.$response['bad_msg_id'].': '.$error_codes[$response['error_code']]);
                break;
            case 'bad_server_salt':
                $this->datacenter->temp_auth_key['server_salt'] = $response['new_server_salt'];
                $this->ack_outgoing_message_id($response['bad_msg_id']); // Acknowledge that the server received my request
                $this->datacenter->outgoing_messages[$response['bad_msg_id']]['response'] = $last_received;
                $this->datacenter->incoming_messages[$last_received]['content'] = $response;
                break;

            case 'pong':
                foreach ($this->datacenter->outgoing_messages as $msg_id => &$omessage) {
                    if (isset($omessage['content']['args']['ping_id']) && $omessage['content']['args']['ping_id'] == $response['ping_id']) {
                        $omessage['response'] = $response['msg_id'];
                        $this->datacenter->incoming_messages[$response['msg_id']]['content'] = $response;
                        $this->ack_outgoing_message_id($msg_id);
                    }
                }
                break;
            case 'new_session_created':
                $this->datacenter->temp_auth_key['server_salt'] = $response['server_salt'];
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                \danog\MadelineProto\Logger::log('new session created');
                \danog\MadelineProto\Logger::log($response);
                break;
            case 'msg_container':
                $responses = [];
                \danog\MadelineProto\Logger::log('Received container.');
                \danog\MadelineProto\Logger::log($response['messages']);
                foreach ($response['messages'] as $message) {
                    $this->check_message_id($message['msg_id'], false, true);
                    $this->datacenter->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body']];
                    $responses[] = $this->handle_message($last_sent, $message['msg_id']);
                }
                foreach ($responses as $key => $response) {
                    if ($response == null) {
                        unset($responses[$key]);
                    }
                }
                switch (count($responses)) {
                    case 0:
                        return;

                    case 1:
                        return end($responses);
                        break;
                    default:
                        \danog\MadelineProto\Logger::log('Received multiple responses, returning last one');
                        \danog\MadelineProto\Logger::log($responses);

                        return end($responses);
                        break;
                }
                break;
            case 'msg_copy':
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                if (isset($this->datacenter->incoming_messages[$response['orig_message']['msg_id']])) {
                    $this->ack_incoming_message_id($response['orig_message']['msg_id']); // Acknowledge that I received the server's response
                } else {
                    $this->check_message_id($message['orig_message']['msg_id'], false, true);
                    $this->datacenter->incoming_messages[$message['orig_message']['msg_id']] = ['content' => $response['orig_message']];

                    return $this->handle_message($last_sent, $message['orig_message']['msg_id']);
                }
                break;
            case 'http_wait':
                \danog\MadelineProto\Logger::log('Received http wait.');
                \danog\MadelineProto\Logger::log($response);
                break;
            case 'gzip_packed':
                $this->datacenter->incoming_messages[$last_received]['content'] = $this->tl->deserialize($this->fopen_and_write('php://memory', 'rw+b', gzdecode($response['packed_data'])));
                return $this->handle_message($last_sent, $last_received);
                break;
            case 'rpc_answer_dropped_running':
            case 'rpc_answer_dropped':
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
            default:
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                $this->datacenter->outgoing_messages[$last_sent]['response'] = $last_received;
                $this->datacenter->incoming_messages[$last_received]['content'] = $response;
                break;
        }
    }
}
