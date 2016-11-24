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
 * Manages method and object calls.
 */
class CallHandler extends AuthKeyHandler
{
    public function wait_for_response($last_sent, $optional_name = null)
    {
        if ($optional_name == null) {
            $optional_name = $last_sent;
        }
        $response = null;
        $count = 0;
        while ($response == null && $count++ < $this->settings['max_tries']['response']) {
            \danog\MadelineProto\Logger::log('Getting response (try number '.$count.' for '.$optional_name.')...');
            $last_received = $this->recv_message();
            $this->handle_message($last_sent, $last_received);
            if (isset($this->outgoing_messages[$last_sent]['response']) && isset($this->incoming_messages[$this->outgoing_messages[$last_sent]['response']]['content'])) {
                $response = $this->incoming_messages[$this->outgoing_messages[$last_sent]['response']]['content'];
            }
        }
        if ($response == null) {
            throw new \danog\MadelineProto\Exception("Couldn't get response");
        }
        switch ($response['_']) {
            case 'rpc_error':
                switch ($response['error_code']) {
                    case 303:
                        $dc = preg_replace('/[^0-9]+/', '', $response['error_message']);
                        \danog\MadelineProto\Logger::log('Received request to switch to DC '.$dc);
                        $this->switch_dc($dc);

                        return $this->method_call($this->outgoing_messages[$last_sent]['content']['method'], $this->outgoing_messages[$last_sent]['content']['args']);

                        break;
                    default:
                        throw new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']);
                        break;
                }
                break;
            default:
                return $response;
                break;
        }
    }

    public function method_call($method, $args, $message_id = null)
    {
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }
        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $args = $this->tl->get_named_method_args($method, $args);
                $int_message_id = $this->send_message($this->tl->serialize_method($method, $args), $this->tl->content_related($method), $message_id);
                $this->outgoing_messages[$int_message_id]['content'] = ['method' => $method, 'args' => $args];
                $server_answer = $this->wait_for_response($int_message_id, $method);
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log('An error occurred while calling method '.$method.': '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Recreating connection and retrying to call method...');
                $this->datacenter->close_and_reopen();
                continue;
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if ($e->getCode() == -404) {
                    \danog\MadelineProto\Logger::log('Temporary authorization key expired. Regenerating and recalling method...');
                    $this->datacenter->temp_auth_key = null;
                    $this->init_authorization();
                    continue;
                }
                throw $e;
            }
            if ($server_answer == null) {
                throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.'.');
            }

            return $server_answer;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.'.');
    }

    public function object_call($object, $args)
    {
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }

        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $int_message_id = $this->send_message($this->tl->serialize_obj($object, $args), $this->tl->content_related($object));
                $this->outgoing_messages[$int_message_id]['content'] = ['object' => $object, 'args' => $args];
//                $server_answer = $this->wait_for_response($int_message_id);
            } catch (Exception $e) {
                \danog\MadelineProto\Logger::log('An error occurred while calling object '.$object.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call object...');
                $this->datacenter->close_and_reopen();
                continue;
            }

            return;
//            if ($server_answer == null) {
//                throw new \danog\MadelineProto\Exception('An error occurred while calling object '.$object.'.');
//            }
//            $deserialized = $this->tl->deserialize($this->fopen_and_write('php://memory', 'rw+b', $server_answer));
//            return $deserialized;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while calling object '.$object.'.');
    }
}
