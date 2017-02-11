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

namespace danog\MadelineProto\Threads;

/**
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 */
class SocketHandler extends Threaded
{
    public $payloads = [];
    public function __construct(&$me) {
        $this->API = $me;
    }
    /**
     * Reading connection and receiving message from server. Check the CRC32.
     */
    public function run()
    {
        $this->socket_handler->synchronized(function ($thread) {
            if (empty($thread->payloads)) {
                $thread->wait();
            } else {
                foreach ($thread->payloads as $payload) {
        if (fstat($payload)['size'] === 4) {
            $error = \danog\PHP\Struct::unpack('<i', stream_get_contents($payload, 4))[0];
            if ($error === -404) {
                if ($thread->API->datacenter->temp_auth_key != null) {
                    \danog\MadelineProto\Logger::log(['WARNING: Resetting auth key...'], \danog\MadelineProto\Logger::WARNING);
                    $thread->API->datacenter->temp_auth_key = null;
                    $thread->API->init_authorization();
                    $thread->API->config = $thread->API->write_client_info('help.getConfig');
                    $thread->API->parse_config();
                    continue;
                    //throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                }
            }
            throw new \danog\MadelineProto\RPCErrorException($error, $error);
        }
        $auth_key_id = stream_get_contents($payload, 8);
        if ($auth_key_id === str_repeat(chr(0), 8)) {
            list($message_id, $message_length) = \danog\PHP\Struct::unpack('<QI', stream_get_contents($payload, 12));
            $thread->API->check_message_id($message_id, false);
            $message_data = stream_get_contents($payload, $message_length);
        } elseif ($auth_key_id === $thread->API->datacenter->temp_auth_key['id']) {
            $message_key = stream_get_contents($payload, 16);
            $encrypted_data = stream_get_contents($payload);
            list($aes_key, $aes_iv) = $thread->API->aes_calculate($message_key, $thread->API->datacenter->temp_auth_key['auth_key'], 'from server');
            $decrypted_data = $thread->API->ige_decrypt($encrypted_data, $aes_key, $aes_iv);

            $server_salt = \danog\PHP\Struct::unpack('<q', substr($decrypted_data, 0, 8))[0];
            if ($server_salt != $thread->API->datacenter->temp_auth_key['server_salt']) {
                //\danog\MadelineProto\Logger::log(['WARNING: Server salt mismatch (my server salt '.$thread->API->datacenter->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').'], \danog\MadelineProto\Logger::WARNING);
            }

            $session_id = substr($decrypted_data, 8, 8);
            if ($session_id != $thread->API->datacenter->session_id) {
                throw new \danog\MadelineProto\Exception('Session id mismatch.');
            }

            $message_id = \danog\PHP\Struct::unpack('<Q', substr($decrypted_data, 16, 8))[0];
            $thread->API->check_message_id($message_id, false);

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
            $thread->API->datacenter->incoming_messages[$message_id]['seq_no'] = $seq_no;
        } else {
            throw new \danog\MadelineProto\Exception('Got unknown auth_key id');
        }
        $deserialized = $thread->API->deserialize($message_data);
        $thread->API->datacenter->incoming_messages[$message_id]['content'] = $deserialized;
        $thread->API->datacenter->incoming_messages[$message_id]['response'] = -1;
        $thread->API->datacenter->new_incoming[$message_id] = $message_id;
        $thread->API->handle_messages();
                }
            }
        }, $this);

    }
}
