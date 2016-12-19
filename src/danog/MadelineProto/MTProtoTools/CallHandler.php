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
trait CallHandler
{
    public function method_call($method, $args = [], $message_id = null)
    {
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }
        foreach (range(1, $this->settings['max_tries']['query']) as $count) {
            try {
                \danog\MadelineProto\Logger::log('Calling method (try number '.$count.' for '.$method.')...');
                $args = $this->tl->get_named_method_args($method, $args);
                $int_message_id = $this->send_message($this->tl->serialize_method($method, $args), $this->tl->content_related($method), $message_id);
                $this->datacenter->outgoing_messages[$int_message_id]['content'] = ['method' => $method, 'args' => $args];
                $this->datacenter->new_outgoing[$int_message_id] = ['msg_id' => $int_message_id, 'method' => $method, 'type' => $this->tl->methods->find_by_method($method)['type']];
                $res_count = 0;
                $server_answer = null;
                while ($server_answer === null && $res_count++ < $this->settings['max_tries']['response']) {
                    \danog\MadelineProto\Logger::log('Getting response (try number '.$res_count.' for '.$method.')...');
                    $this->recv_message();
                    $this->handle_messages();
                    if (!isset($this->datacenter->incoming_messages[$this->datacenter->outgoing_messages[$int_message_id]['response']]['content'])) {
                        continue;
                    }
                    $server_answer = $this->datacenter->incoming_messages[$this->datacenter->outgoing_messages[$int_message_id]['response']]['content'];
                }
                if ($server_answer == null) {
                    throw new \danog\MadelineProto\Exception("Couldn't get response");
                }
                switch ($server_answer['_']) {
                    case 'rpc_error':
                        switch ($server_answer['error_code']) {
                            case 303:
                                $dc = preg_replace('/[^0-9]+/', '', $server_answer['error_message']);
                                \danog\MadelineProto\Logger::log('Received request to switch to DC '.$dc);
                                $this->switch_dc($dc);
                                throw new \danog\MadelineProto\Exception('I had to switch to datacenter '.$dc);

                                break;
                            case 401:
                                switch ($server_answer['error_message']) {
                                    case 'AUTH_KEY_UNREGISTERED':
                                    case 'AUTH_KEY_INVALID':
                                    case 'USER_DEACTIVATED':
                                    case 'SESSION_REVOKED':
                                    case 'SESSION_EXPIRED':
                                        unset($this->datacenter->temp_auth_key);
                                        unset($this->datacenter->auth_key);
                                        $this->datacenter->authorized = false;
                                        $this->datacenter->authorization = null;
                                        throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                                        break;
                                }
                            case 420:
                                $seconds = preg_replace('/[^0-9]+/', '', $server_answer['error_message']);
                                \danog\MadelineProto\Logger::log('Flood, waiting '.$seconds.' seconds...');
                                sleep($seconds);
                                throw new \danog\MadelineProto\Exception('Re-executing query...');
                            default:
                                throw new \danog\MadelineProto\RPCErrorException($server_answer['error_message'], $server_answer['error_code']);
                                break;
                        }
                        break;
                    case 'bad_server_salt':
                    case 'bad_msg_notification':
                        switch ($server_answer['error_code']) {
                            case 48:
                                $this->datacenter->temp_auth_key['server_salt'] = $server_answer['new_server_salt'];
                                throw new \danog\MadelineProto\Exception('New server salt stored, re-executing query');
                                break;
                        }
                        throw new \danog\MadelineProto\RPCErrorException('Received bad_msg_notification: '.$this->bad_msg_error_codes[$server_answer['error_code']], $server_answer['error_code']);
                        break;
                    case 'boolTrue':
                    case 'boolFalse':
                        $server_answer = $server_answer['_'] === 'boolTrue';
                        break;
                }
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log('An error occurred while calling method '.$method.': '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Recreating connection and retrying to call method...');
                $this->datacenter->close_and_reopen();
                continue;
            }
            if ($server_answer == null) {
                throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.'.');
            }

            return $server_answer;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.'.');
    }

    public function object_call($object, $args = [])
    {
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }

        foreach (range(1, $this->settings['max_tries']['query']) as $count) {
            try {
                \danog\MadelineProto\Logger::log('Sending object (try number '.$count.' for '.$object.')...');
                $int_message_id = $this->send_message($this->tl->serialize_object(['type' => $object], $args), $this->tl->content_related($object));
                $this->datacenter->outgoing_messages[$int_message_id]['content'] = ['object' => $object, 'args' => $args];
            } catch (Exception $e) {
                \danog\MadelineProto\Logger::log('An error occurred while calling object '.$object.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call object...');
                $this->datacenter->close_and_reopen();
                continue;
            }

            return;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while calling object '.$object.'.');
    }
}
