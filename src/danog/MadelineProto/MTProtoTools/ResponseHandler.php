<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
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
        $response = $this->incoming_messages[$last_received]['content'];
        switch ($response['_']) {
            case 'msgs_ack':
                foreach ($response['msg_ids'] as $msg_id) {
                    $this->ack_outgoing_message_id($msg_id); // Acknowledge that the server received my message
                }
                break;

            case 'rpc_result':
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my request
                $this->outgoing_messages[$response['req_msg_id']]['response'] = $last_received;
                $this->incoming_messages[$last_received]['content'] = $response['result'];
                break;

            case 'future_salts':
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my request
                $this->outgoing_messages[$response['req_msg_id']]['response'] = $last_received;
                $this->incoming_messages[$last_received]['content'] = $response;
                break;

            case 'bad_msg_notification':
                break;
            case 'bad_server_salt':
                $this->settings['authorization']['temp_auth_key']['server_salt'] = $response['new_server_salt'];
                $this->ack_outgoing_message_id($response['bad_msg_id']); // Acknowledge that the server received my request
                $this->outgoing_messages[$response['bad_msg_id']]['response'] = $last_received;
                $this->incoming_messages[$last_received]['content'] = $response;
                break;

            case 'pong':
                foreach ($this->outgoing_messages as $msg_id => &$omessage) {
                    if (isset($omessage['content']['args']['ping_id']) && $omessage['content']['args']['ping_id'] == $response['ping_id']) {
                        $omessage['response'] = $response['msg_id'];
                        $this->incoming_messages[$response['msg_id']]['content'] = $response;
                        $this->ack_outgoing_message_id($msg_id);
                    }
                }
                break;
            case 'new_session_created':
                $this->settings['authorization']['temp_auth_key']['server_salt'] = $response['server_salt'];
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                $this->log->log('new session created');
                $this->log->log($response);
                break;
            case 'msg_container':
                $responses = [];
                $this->log->log('Received container.');
                $this->log->log($response['messages']);
                foreach ($response['messages'] as $message) {
                    $this->check_message_id($message['msg_id'], false, true);
                    $this->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body']];
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
                        $this->log->log('Received multiple responses, returning last one');
                        $this->log->log($responses);

                        return end($responses);
                        break;
                }
                break;
            case 'msg_copy':
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                if (isset($this->incoming_messages[$response['orig_message']['msg_id']])) {
                    $this->ack_incoming_message_id($response['orig_message']['msg_id']); // Acknowledge that I received the server's response
                } else {
                    $this->check_message_id($message['orig_message']['msg_id'], false, true);
                    $this->incoming_messages[$message['orig_message']['msg_id']] = ['content' => $response['orig_message']];

                    return $this->handle_message($last_sent, $message['orig_message']['msg_id']);
                }
                break;
            case 'http_wait':
                $this->log->log('Received http wait.');
                $this->log->log($response);
                break;
            case 'gzip_packed':
                $this->incoming_messages[$last_received]['content'] = gzdecode($response);

                return $this->handle_message($last_sent, $last_received);
                break;
            case 'rpc_answer_dropped_running':
            case 'rpc_answer_dropped':
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
            default:
                $this->ack_incoming_message_id($last_received); // Acknowledge that I received the server's response
                $this->outgoing_messages[$last_sent]['response'] = $last_received;
                $this->incoming_messages[$last_received]['content'] = $response;
                break;
        }
    }
}
