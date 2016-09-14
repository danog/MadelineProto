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
 * Manages method and object calls.
 */
class CallHandler extends AuthKeyHandler
{
    public function wait_for_response($last_sent)
    {
        $response = null;
        $count = 0;
        while ($response == null && $count++ < $this->settings['max_tries']['response']) {
            $this->log->log("Getting response....");
            $deserialized = $this->recv_message();
            end($this->incoming_messages);
            $tempres = $this->handle_message($deserialized, $last_sent, key($this->incoming_messages));
var_dump($this->incoming_messages);
            if (isset($this->outgoing_messages[$last_sent]['response']) && isset($this->incoming_messages[$this->outgoing_messages[$last_sent]['response']]['content'])) {
                $response = $this->incoming_messages[$this->outgoing_messages[$last_sent]['response']]['content'];
            }
        }
        if ($response == null) {
            throw new Exception("Couldn't get response");
        }
        switch ($response['_']) {
            case 'rpc_error':
                throw new Exception('Got rpc error '.$response['error_code'].': '.$response['error_message']);
                break;
            default:
                return $response;
                break;
        }
    }

    public function method_call($method, $args)
    {
        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $int_message_id = $this->send_message($this->tl->serialize_method($method, $args), $this->tl->content_related($method));
                $this->outgoing_messages[$int_message_id]['content'] = ['method' => $method, 'args' => $args ];
                $server_answer = $this->wait_for_response($int_message_id);
            } catch (Exception $e) {
                $this->log->log('An error occurred while calling method '.$method.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call method...');
                unset($this->sock);
                $this->sock = new \danog\MadelineProto\Connection($this->settings['connection']['ip_address'], $this->settings['connection']['port'], $this->settings['connection']['protocol']);
                continue;
            }
            if ($server_answer == null) {
                throw new Exception('An error occurred while calling method '.$method.'.');
            }

            return $server_answer;
        }
        throw new Exception('An error occurred while calling method '.$method.'.');
    }

    public function object_call($object, $args)
    {
        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $int_message_id = $this->send_message($this->tl->serialize_obj($object, $args), $this->tl->content_related($object));
                $this->outgoing_messages[$int_message_id]['content'] = ['object' => $object, 'args' => $args ];
//                $server_answer = $this->wait_for_response($int_message_id);
            } catch (Exception $e) {
                $this->log->log('An error occurred while calling object '.$object.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call object...');
                unset($this->sock);
                $this->sock = new \danog\MadelineProto\Connection($this->settings['connection']['ip_address'], $this->settings['connection']['port'], $this->settings['connection']['protocol']);
                continue;
            }

            return;
//            if ($server_answer == null) {
//                throw new Exception('An error occurred while calling object '.$object.'.');
//            }
//            $deserialized = $this->tl->deserialize(\danog\MadelineProto\Tools::fopen_and_write('php://memory', 'rw+b', $server_answer));
//            return $deserialized;
        }
        throw new Exception('An error occurred while calling object '.$object.'.');
    }
}
