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
    public function handle_response($response, $name, $args)
    {
        switch ($response['_']) {
            case 'rpc_result':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my
                if ($response['req_msg_id'] != $this->last_sent['message_id']) {
                    throw new Exception('Message id mismatch; req_msg_id ('.$response['req_msg_id'].') != last sent msg id ('.$this->last_sent['message_id'].').');
                }

                return $this->handle_response($response['result'], $name, $args);
                break;
            case 'rpc_error':
                throw new Exception('Got rpc error '.$response['error_code'].': '.$response['error_message']);
                break;
            case 'rpc_answer_unknown':
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message
                return $response; // I'm not handling this error
                break;
            case 'rpc_answer_dropped_running':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message

                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
                return $response; // I'm not handling this
                break;
            case 'rpc_answer_dropped':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message

                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
                return $response; // I'm not handling this
                break;
            case 'future_salts':
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message
                if ($response['req_msg_id'] != $this->last_sent['message_id']) {
                    throw new Exception('Message id mismatch; req_msg_id ('.$response['req_msg_id'].') != last sent msg id ('.$this->last_sent['message_id'].').');
                }
                $this->log->log('Received future salts.');
                $this->future_salts = $response['salts'];
                break;
            case 'pong':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message
                $this->log->log('pong');
                break;
            case 'msgs_ack':
                foreach ($response['msg_ids'] as $msg_id) {
                    $this->ack_outgoing_message_id($msg_id); // Acknowledge that the server received my message
                }
                break;
            case 'new_session_created':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->log->log('new session created');
                $this->log->log($response);
                break;
            case 'msg_container':
                $responses = [];
                $this->log->log('Received container.');
                $this->log->log($response['messages']);
                foreach ($response['messages'] as $message) {
                    $this->last_recieved = ['message_id' => $message['msg_id'], 'seq_no' => $message['seqno']];
                    $responses[] = $this->handle_response($message['body'], $name, $args);
                }
                foreach ($responses as $response) {
                    if ($response != null) {
                        return $response;
                    }
                }
                break;
            case 'msg_copy':
                $this->handle_response($response['orig_message'], $name, $args);
                break;
            case 'gzip_packed':
                return $this->handle_response(gzdecode($response));
                break;
            case 'http_wait':
                $this->log->log('Received http wait.');
                $this->log->log($response);
                break;
            default:
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                return $response;
                break;
        }
    }

}
