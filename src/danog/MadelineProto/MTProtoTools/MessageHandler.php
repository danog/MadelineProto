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
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 */
trait MessageHandler
{
    /**
     * Forming the message frame and sending message to server
     * :param message: byte string to send.
     */
    public function send_message($message_data, $content_related, $int_message_id = null)
    {
        if ($int_message_id == null) {
            $int_message_id = $this->generate_message_id();
        }
        if (!is_int($int_message_id)) {
            throw new \danog\MadelineProto\Exception("Specified message id isn't an integer");
        }

        $message_id = \danog\PHP\Struct::pack('<Q', $int_message_id);
        if ($this->datacenter->temp_auth_key['auth_key'] == null || $this->datacenter->temp_auth_key['server_salt'] == null) {
            $message = $this->string2bin('\x00\x00\x00\x00\x00\x00\x00\x00').$message_id.\danog\PHP\Struct::pack('<I', strlen($message_data)).$message_data;
        } else {
            $seq_no = $this->generate_seq_no($content_related);
            $encrypted_data = \danog\PHP\Struct::pack('<q', $this->datacenter->temp_auth_key['server_salt']).$this->datacenter->session_id.$message_id.\danog\PHP\Struct::pack('<II', $seq_no, strlen($message_data)).$message_data;
            $message_key = substr(sha1($encrypted_data, true), -16);
            $padding = \danog\MadelineProto\Tools::random($this->posmod(-strlen($encrypted_data), 16));
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->temp_auth_key['auth_key']);
            $message = $this->datacenter->temp_auth_key['id'].$message_key.$this->ige_encrypt($encrypted_data.$padding, $aes_key, $aes_iv);
            $this->datacenter->outgoing_messages[$int_message_id]['seq_no'] = $seq_no;
        }
        $this->datacenter->outgoing_messages[$int_message_id]['response'] = -1;
        $this->datacenter->send_message($message);

        return $int_message_id;
    }

    /**
     * Reading connectionet and receiving message from server. Check the CRC32.
     */
    public function recv_message()
    {
        $payload = $this->datacenter->read_message();
        if (fstat($payload)['size'] == 4) {
            $error = \danog\PHP\Struct::unpack('<i', stream_get_contents($payload, 4))[0];
            if ($error == -404) {
                if ($this->datacenter->temp_auth_key != null) {
                    \danog\MadelineProto\Logger::log('WARNING: Resetting auth key...');
                    $this->datacenter->temp_auth_key = null;
                    $this->init_authorization();
                    $this->config = $this->write_client_info('help.getConfig');
                    $this->parse_config();
                    throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                }
            }
            throw new \danog\MadelineProto\RPCErrorException($error, $error);
        }
        $auth_key_id = stream_get_contents($payload, 8);
        if ($auth_key_id == $this->string2bin('\x00\x00\x00\x00\x00\x00\x00\x00')) {
            list($message_id, $message_length) = \danog\PHP\Struct::unpack('<QI', stream_get_contents($payload, 12));
            $this->check_message_id($message_id, false);
            $message_data = stream_get_contents($payload, $message_length);
        } elseif ($auth_key_id == $this->datacenter->temp_auth_key['id']) {
            $message_key = stream_get_contents($payload, 16);
            $encrypted_data = stream_get_contents($payload);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->temp_auth_key['auth_key'], 'from server');
            $decrypted_data = $this->ige_decrypt($encrypted_data, $aes_key, $aes_iv);

            $server_salt = \danog\PHP\Struct::unpack('<q', substr($decrypted_data, 0, 8))[0];
            if ($server_salt != $this->datacenter->temp_auth_key['server_salt']) {
                \danog\MadelineProto\Logger::log('WARNING: Server salt mismatch (my server salt '.$this->datacenter->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').');
            }

            $session_id = substr($decrypted_data, 8, 8);
            if ($session_id != $this->datacenter->session_id) {
                throw new \danog\MadelineProto\Exception('Session id mismatch.');
            }

            $message_id = \danog\PHP\Struct::unpack('<Q', substr($decrypted_data, 16, 8))[0];
            $this->check_message_id($message_id, false);

            $seq_no = \danog\PHP\Struct::unpack('<I', substr($decrypted_data, 24, 4))[0];
            // Dunno how to handle any incorrect sequence numbers

            $message_data_length = \danog\PHP\Struct::unpack('<I', substr($decrypted_data, 28, 4))[0];

            if ($message_data_length > strlen($decrypted_data)) {
                throw new \danog\MadelineProto\Exception('message_data_length is too big');
            }

            if ((strlen($decrypted_data) - 32) - $message_data_length > 15) {
                throw new \danog\MadelineProto\Exception('difference between message_data_length and the length of the remaining decrypted buffer is too big');
            }

            if ($message_data_length < 0) {
                throw new \danog\MadelineProto\Exception('message_data_length not positive');
            }

            if ($message_data_length % 4 != 0) {
                throw new \danog\MadelineProto\Exception('message_data_length not divisible by 4');
            }

            $message_data = substr($decrypted_data, 32, $message_data_length);
            if ($message_key != substr(sha1(substr($decrypted_data, 0, 32 + $message_data_length), true), -16)) {
                throw new \danog\MadelineProto\Exception('msg_key mismatch');
            }
            $this->datacenter->incoming_messages[$message_id]['seq_no'] = $seq_no;
        } else {
            throw new \danog\MadelineProto\Exception('Got unknown auth_key id');
        }
        $deserialized = $this->deserialize($this->fopen_and_write('php://memory', 'rw+b', $message_data));
        $this->datacenter->incoming_messages[$message_id]['content'] = $deserialized;
        $this->datacenter->incoming_messages[$message_id]['response'] = -1;
        $this->datacenter->new_incoming[$message_id] = $message_id;
        $this->handle_messages();
    }
}
