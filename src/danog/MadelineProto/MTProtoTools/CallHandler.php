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
 * Manages method and object calls.
 */
trait CallHandler
{
    public function method_call($method, $args = [], $aargs = ['message_id' => null, 'heavy' => false])
    {
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }
        if (!is_array($aargs)) {
            throw new \danog\MadelineProto\Exception("Additonal arguments aren't an array.");
        }
        $args = $this->botAPI_to_MTProto($args);
        $serialized = $this->serialize_method($method, $args);
        $content_related = $this->content_related($method);
        for ($count = 1; $count <= $this->settings['max_tries']['query']; $count++) {
            try {
                \danog\MadelineProto\Logger::log(['Calling method (try number '.$count.' for '.$method.')...'], \danog\MadelineProto\Logger::VERBOSE);

                $int_message_id = $this->send_message($serialized, $content_related, $aargs);
                if ($method === 'http_wait') {
                    return true;
                }
                $this->datacenter->outgoing_messages[$int_message_id]['content'] = ['method' => $method, 'args' => $args];
                $this->datacenter->new_outgoing[$int_message_id] = ['msg_id' => $int_message_id, 'method' => $method, 'type' => $this->methods->find_by_method($method)['type']];
                $res_count = 0;
                $server_answer = null;
                $update_count = 0;
                while ($server_answer === null && $res_count++ < $this->settings['max_tries']['response']) { // Loop until we get a response, loop for a max of $this->settings['max_tries']['response'] times
                    try {
                        \danog\MadelineProto\Logger::log(['Getting response (try number '.$res_count.' for '.$method.')...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        $this->recv_message(); // This method receives data from the socket, and parses stuff

                        if (!isset($this->datacenter->outgoing_messages[$int_message_id]['response']) || !isset($this->datacenter->incoming_messages[$this->datacenter->outgoing_messages[$int_message_id]['response']]['content'])) { // Checks if I have received the response to the called method, if not continue looping
                            if ($this->only_updates) {
                                if ($update_count > 50) {
                                    $update_count = 0;
                                    continue;
                                }
                                $res_count--;
                                $update_count++;
                            }
                            continue;
                        }
                        $server_answer = $this->datacenter->incoming_messages[$this->datacenter->outgoing_messages[$int_message_id]['response']]['content']; // continue was not called, so I got a response
                        if (isset($aargs['heavy']) && $aargs['heavy']) {
                            $this->datacenter->incoming_messages[$this->datacenter->outgoing_messages[$int_message_id]['response']]['content'] = [];
                        }
                    } catch (\danog\MadelineProto\Exception $e) {
                        if ($e->getMessage() === 'I had to recreate the temporary authorization key') {
                            continue 2;
                        }
                        \danog\MadelineProto\Logger::log(['An error getting response of method '.$method.': '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...'], \danog\MadelineProto\Logger::WARNING);
                        continue;
                    }
                }
                if ($server_answer === null) {
                    throw new \danog\MadelineProto\Exception("Couldn't get response");
                }
                switch ($server_answer['_']) {
                    case 'rpc_error':
                        switch ($server_answer['error_code']) {
                            case 303:
                                $dc = preg_replace('/[^0-9]+/', '', $server_answer['error_message']);
                                \danog\MadelineProto\Logger::log(['Received request to switch to DC '.$dc], \danog\MadelineProto\Logger::NOTICE);
                                $this->switch_dc($dc);
                                continue 3;
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
                                if (is_numeric($seconds) && isset($this->settings['flood_timeout']['wait_if_lt']) && $seconds < $this->settings['flood_timeout']['wait_if_lt']) {
                                    \danog\MadelineProto\Logger::log(['Flood, waiting '.$seconds.' seconds...'], \danog\MadelineProto\Logger::NOTICE);
                                    sleep($seconds);
                                    throw new \danog\MadelineProto\Exception('Re-executing query...');
                                }
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
                                continue 3;
                            case 16:
                            case 17:
                                \danog\MadelineProto\Logger::log(['Received bad_msg_notification: '.$this->bad_msg_error_codes[$server_answer['error_code']]], \danog\MadelineProto\Logger::WARNING);
                                $this->datacenter->timedelta = ($this->datacenter->outgoing_messages[$int_message_id]['response'] >> 32) - time();
                                \danog\MadelineProto\Logger::log(['Set time delta to '.$this->datacenter->timedelta], \danog\MadelineProto\Logger::WARNING);
                                $this->reset_session();
                                $this->datacenter->temp_auth_key = null;
                                $this->init_authorization();
                                continue 3;
                        }
                        throw new \danog\MadelineProto\RPCErrorException('Received bad_msg_notification: '.$this->bad_msg_error_codes[$server_answer['error_code']], $server_answer['error_code']);
                        break;
                    case 'boolTrue':
                    case 'boolFalse':
                        $server_answer = $server_answer['_'] === 'boolTrue';
                        break;
                }
                if (isset($aargs['botAPI']) && $aargs['botAPI']) {
                    $server_answer = $this->MTProto_to_botAPI($server_answer, $args);
                }
            } catch (\danog\MadelineProto\Exception $e) {
                $last_error = $e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine();
                \danog\MadelineProto\Logger::log(['An error occurred while calling method '.$method.': '.$last_error.'. Recreating connection and retrying to call method...'], \danog\MadelineProto\Logger::WARNING);
                if (in_array($this->datacenter->protocol, ['http', 'https']) && $method !== 'http_wait') {
                    //$this->method_call('http_wait', ['max_wait' => $this->datacenter->timeout, 'wait_after' => 0, 'max_delay' => 0]);
                } else {
                    $this->datacenter->close_and_reopen();
                }
                sleep(1); // To avoid flooding
                continue;
            } finally {
                if (isset($aargs['heavy']) && $aargs['heavy'] && isset($int_message_id)) {
                    $this->datacenter->outgoing_messages[$int_message_id]['args'] = [];
                }
            }
            if ($server_answer === null) {
                throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.' ('.$last_error.').');
            }
            \danog\MadelineProto\Logger::log(['Got response for method '.$method.' @ try '.$count.' (response try '.$res_count.')'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return $server_answer;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.' ('.$last_error.').');
    }

    public function object_call($object, $args = [])
    {
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }

        for ($count = 1; $count <= $this->settings['max_tries']['query']; $count++) {
            try {
                \danog\MadelineProto\Logger::log([$object === 'msgs_ack' ? 'ack '.$args['msg_ids'][0] : 'Sending object (try number '.$count.' for '.$object.')...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $int_message_id = $this->send_message($this->serialize_object(['type' => $object], $args), $this->content_related($object));
                $this->datacenter->outgoing_messages[$int_message_id]['content'] = ['method' => $object, 'args' => $args];
            } catch (Exception $e) {
                \danog\MadelineProto\Logger::log(['An error occurred while calling object '.$object.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call object...'], \danog\MadelineProto\Logger::WARNING);
                $this->datacenter->close_and_reopen();
                continue;
            }

            return $int_message_id;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while sending object '.$object.'.');
    }
}
