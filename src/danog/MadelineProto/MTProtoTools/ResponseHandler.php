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
    public function handle_message($response)
    {
        switch ($response['_']) {
            case 'msgs_ack':
                foreach ($response['msg_ids'] as $msg_id) {
                    $this->ack_outgoing_message_id($msg_id); // Acknowledge that the server received my message
                }
                break;
                
            case 'rpc_result':
                end($this->incoming_messages);
                end($this->outgoing_messages);
                $this->ack_incoming_message_id(key($this->incoming_messages)); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my request
                if ($response['req_msg_id'] != key($this->outgoing_messages)) {
                    throw new Exception('Message id mismatch; req_msg_id ('.$response['req_msg_id'].') != last sent msg id ('.key($this->outgoing_messages).').');
                }
                return $this->handle_response($response['result'], $response['req_msg_id']);
                break;
                
            case 'future_salts':
                end($this->outgoing_messages);
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my request
                if ($response['req_msg_id'] != key($this->outgoing_messages)) {
                    throw new Exception('Message id mismatch; req_msg_id ('.$response['req_msg_id'].') != last sent msg id ('.key($this->outgoing_messages).').');
                }
                $this->log->log('Received future salts.');
                return $this->handle_response($response, $response['req_msg_id']);
                break;

            case 'pong':
                foreach ($this->outgoing_messages as $omessage) {
                    if ($omessage["args"]["ping_id"] == $response["ping_id"]) {
                        $this->outgoing_messages[$response["msg_id"]]["response"] = $response;
                    }
                }
                break;
            case 'new_session_created':
                end($this->incoming_messages);
                $this->ack_incoming_message_id(key($this->incoming_messages)); // Acknowledge that I received the server's response
                $this->log->log('new session created');
                $this->log->log($response);
                break;
            case 'msg_container':
                $responses = [];
                $this->log->log('Received container.');
                $this->log->log($response['messages']);
                foreach ($response['messages'] as $message) {
                    $this->incoming_messages[$message['msg_id']] = [ 'seq_no' => $message['seqno']];
                    $responses[] = $this->handle_message($message['body']);
                }
                foreach ($responses as $response) {
                    if ($response !== null) {
                        return $response;
                    }
                }
                break;
            case 'msg_copy':
                return $this->handle_response($response['orig_message']);
                break;
            case 'gzip_packed':
                return $this->handle_response(gzdecode($response));
                break;
            case 'http_wait':
                $this->log->log('Received http wait.');
                $this->log->log($response);
                break;
            default:
                end($this->incoming_messages);
                $this->ack_incoming_message_id(key($this->incoming_messages)); // Acknowledge that I received the server's response
                return $this->handle_response($response);
                break;
        }
    }
    public function handle_response($response, $res_id = null) {
        if ($res_id == null) {
            return $response;
        }
        switch ($response['_']) {
            case 'rpc_answer_dropped_running':
            case 'rpc_answer_dropped':
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
            default:
                $this->outgoing_messages[$res_id]["response"] = $response;
                break;
        }
    }
}
