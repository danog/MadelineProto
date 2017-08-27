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
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 */
trait MessageHandler
{
    /**
     * Forming the message frame and sending message to server
     * :param message: byte string to send.
     */
    public function send_message($message_data, $content_related, $aargs = [])
    {
        if (!isset($aargs['message_id']) || $aargs['message_id'] === null) {
            $message_id = $this->generate_message_id($aargs['datacenter']);
        } else {
            $message_id = $aargs['message_id'];
        }
        if (!is_string($message_id)) {
            throw new \danog\MadelineProto\Exception("Specified message id isn't a string");
        }
        $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id] = [];

        if ($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['auth_key'] === null || $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['server_salt'] === null) {
            $message = "\0\0\0\0\0\0\0\0".$message_id.$this->pack_unsigned_int(strlen($message_data)).$message_data;
        } else {
            $seq_no = $this->generate_out_seq_no($aargs['datacenter'], $content_related);
            $data2enc = $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['server_salt'].$this->datacenter->sockets[$aargs['datacenter']]->session_id.$message_id.pack('VV', $seq_no, strlen($message_data)).$message_data;
            $padding = $this->random($this->posmod(-strlen($data2enc), 16));
            $message_key = substr(sha1($data2enc, true), -16);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['auth_key']);
            $message = $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['id'].$message_key.$this->ige_encrypt($data2enc.$padding, $aes_key, $aes_iv);
            $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['seq_no'] = $seq_no;
        }
        $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response'] = -1;
        $this->datacenter->sockets[$aargs['datacenter']]->send_message($message);

        return $message_id;
    }

    /**
     * Reading connection and receiving message from server.
     */
    public function recv_message($datacenter)
    {
        $payload = $this->datacenter->sockets[$datacenter]->read_message();
        if (strlen($payload) === 4) {
            return $this->unpack_signed_int($payload);
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
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key'], 'from server');
            $decrypted_data = $this->ige_decrypt($encrypted_data, $aes_key, $aes_iv);
            /*
            $server_salt = substr($decrypted_data, 0, 8);
            if ($server_salt != $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt']) {
                \danog\MadelineProto\Logger::log(['WARNING: Server salt mismatch (my server salt '.$this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').'], \danog\MadelineProto\Logger::WARNING);
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

            if ((strlen($decrypted_data) - 32) - $message_data_length > 15) {
                throw new \danog\MadelineProto\SecurityException('difference between message_data_length and the length of the remaining decrypted buffer is too big');
            }

            if ($message_data_length < 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not positive');
            }

            if ($message_data_length % 4 != 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not divisible by 4');
            }

            $message_data = substr($decrypted_data, 32, $message_data_length);
            if ($message_key != substr(sha1(substr($decrypted_data, 0, 32 + $message_data_length), true), -16)) {
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
        $this->last_recv = time();

        return true;
    }
}
