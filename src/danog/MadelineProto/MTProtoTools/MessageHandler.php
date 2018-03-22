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
    public function send_unencrypted_message($type, $message_data, $message_id, $datacenter)
    {
        \danog\MadelineProto\Logger::log("Sending $type as unencrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $message_data = "\0\0\0\0\0\0\0\0".$message_id.$this->pack_unsigned_int(strlen($message_data)).$message_data;
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id] = ['response' => -1];
        $this->datacenter->sockets[$datacenter]->send_message($message_data);
    }

    public function send_messages($datacenter)
    {
        //$has_ack = false;

        if (count($this->datacenter->sockets[$datacenter]->object_queue) > 1) {
            $messages = [];
            \danog\MadelineProto\Logger::log("Sending msg_container as encrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            foreach ($this->datacenter->sockets[$datacenter]->object_queue as $message) {
                $message['seqno'] = $this->generate_out_seq_no($datacenter, $message['content_related']);
                $message['bytes'] = strlen($message['body']);
                //$has_ack = $has_ack || $message['_'] === 'msgs_ack';
                \danog\MadelineProto\Logger::log("Inside of msg_container, sending {$message['_']} as encrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $message['_'] = 'MTmessage';
                $messages[] = $message;
                $this->datacenter->sockets[$datacenter]->outgoing_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'response' => -1];//, 'content' => $this->deserialize($message['body'], ['type' => '', 'datacenter' => $datacenter])];
            }
            $message_data = $this->serialize_object(['type' => ''], ['_' => 'msg_container', 'messages' => $messages], 'lol');
            $message_id = $this->generate_message_id($datacenter);
            $seq_no = $this->generate_out_seq_no($datacenter, false);
        } elseif (count($this->datacenter->sockets[$datacenter]->object_queue)) {
            $message = array_shift($this->datacenter->sockets[$datacenter]->object_queue);
            \danog\MadelineProto\Logger::log("Sending {$message['_']} as encrypted message to DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            $message_data = $message['body'];
            $message_id = $message['msg_id'];
            $seq_no = $this->generate_out_seq_no($datacenter, $message['content_related']);
        } else {
            return;
        }
        $plaintext = $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'].$this->datacenter->sockets[$datacenter]->session_id.$message_id.pack('VV', $seq_no, strlen($message_data)).$message_data;
        $padding = $this->posmod(-strlen($plaintext), 16);
        if ($padding < 12) {
            $padding += 16;
        }
        $padding = $this->random($padding);
        $message_key = substr(hash('sha256', substr($this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key'], 88, 32).$plaintext.$padding, true), 8, 16);
        list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key']);
        $message = $this->datacenter->sockets[$datacenter]->temp_auth_key['id'].$message_key.$this->ige_encrypt($plaintext.$padding, $aes_key, $aes_iv);
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id] = ['seq_no' => $seq_no, 'response' => -1];
        $this->datacenter->sockets[$datacenter]->send_message($message);
        $this->datacenter->sockets[$datacenter]->object_queue = [];

        /*if ($has_ack) {
            foreach ($this->datacenter->sockets[$datacenter]->ack_queue as $msg_id) {
                $this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['ack'] = true;
            }
            $this->datacenter->sockets[$datacenter]->ack_queue = [];
        }*/
    }

    /**
     * Reading connection and receiving message from server.
     */
    public function recv_message($datacenter)
    {
        if ($this->datacenter->sockets[$datacenter]->must_open) {
            \danog\MadelineProto\Logger::log('Trying to read from closed socket, sending initial ping');
            if ($this->is_http($datacenter)) {
                $this->method_call('http_wait', ['max_wait' => 500, 'wait_after' => 150, 'max_delay' => 500], ['datacenter' => $datacenter]);
            } elseif (isset($this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) && $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) {
                $this->method_call('ping', ['ping_id' => 0], ['datacenter' => $datacenter]);
            } else {
                throw new \danog\MadelineProto\Exception('Resend query');
            }
        }
        $payload = $this->datacenter->sockets[$datacenter]->read_message();
        if (strlen($payload) === 4) {
            $payload = $this->unpack_signed_int($payload);
            \danog\MadelineProto\Logger::log("Received $payload from DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

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
                \danog\MadelineProto\Logger::log('WARNING: Server salt mismatch (my server salt '.$this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').', \danog\MadelineProto\Logger::WARNING);
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
            throw new \danog\MadelineProto\SecurityException('Got unknown auth_key id');
        }
        $deserialized = $this->deserialize($message_data, ['type' => '', 'datacenter' => $datacenter]);
        $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['content'] = $deserialized;
        $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['response'] = -1;
        $this->datacenter->sockets[$datacenter]->new_incoming[$message_id] = $message_id;
        $this->datacenter->sockets[$datacenter]->last_recv = time();

        return true;
    }
}
