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
        if (!$this->is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }
        if (!$this->is_array($aargs)) {
            throw new \danog\MadelineProto\Exception("Additonal arguments aren't an array.");
        }
        if (!isset($aargs['datacenter'])) {
            throw new \danog\MadelineProto\Exception('No datacenter provided');
        }
        if (isset($args['message']) && is_string($args['message']) && mb_strlen($args['message']) > 4096) {
            $message_chunks = $this->split_to_chunks($args['message']);
            $args['message'] = array_shift($message_chunks);
        }
        $args = $this->botAPI_to_MTProto($args);
        if (isset($args['ping_id']) && is_int($args['ping_id'])) {
            $args['ping_id'] = $this->pack_signed_long($args['ping_id']);
        }
        if (isset($args['chat_id']) && !isset($args['peer']) && $method !== 'messages.discardEncryption' && (is_object($args['chat_id']) || $args['chat_id'] < 0)) {
            $res = $this->get_info($args['chat_id']);
            if ($res['type'] !== 'chat') {
                throw new \danog\MadelineProto\Exception('chat_id is not a chat id!');
            }
            $args['chat_id'] = $res['chat_id'];
        }
        $serialized = $this->serialize_method($method, $args);
        $content_related = $this->content_related($method);
        $type = $this->methods->find_by_method($method)['type'];
        $last_recv = $this->last_recv;
        if ($canunset = !$this->getting_state && !$this->threads && !$this->run_workers) {
            $this->getting_state = true;
        }
        for ($count = 1; $count <= $this->settings['max_tries']['query']; $count++) {
            try {
                \danog\MadelineProto\Logger::log(['Calling method (try number '.$count.' for '.$method.')...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $message_id = $this->send_message($serialized, $content_related, $aargs);
                if ($method === 'http_wait' || (isset($aargs['noResponse']) && $aargs['noResponse'])) {
                    return true;
                }
                $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['content'] = ['method' => $method, 'args' => $args];
                $this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id] = ['msg_id' => $message_id, 'method' => $method, 'type' => $type];
                $res_count = 0;
                $server_answer = null;
                $update_count = 0;
                $only_updates = false;
                while ($server_answer === null && $res_count++ < $this->settings['max_tries']['response'] + 1) { // Loop until we get a response, loop for a max of $this->settings['max_tries']['response'] times
                    try {
                        \danog\MadelineProto\Logger::log(['Getting response (try number '.$res_count.' for '.$method.')...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        //sleep(2);
                        $this->start_threads();
                        if (!isset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']) || !isset($this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']]['content'])) { // Checks if I have received the response to the called method, if not continue looping
                            if ($only_updates) {
                                if ($update_count > 50) {
                                    $update_count = 0;
                                } else {
                                    $res_count--;
                                    $update_count++;
                                }
                            }
                        } else {
                            //var_dump(base64_encode($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']), $this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']]);
                            $server_answer = (array) $this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']]['content'];
                            $this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']]['content'] = [];
                            break;
                        }
                        if (!$this->threads && !$this->run_workers) {
                            $this->recv_message($aargs['datacenter']); // This method receives data from the socket, and parses stuff
                            $only_updates = $this->handle_messages($aargs['datacenter']); // This method receives data from the socket, and parses stuff
                        } else {
                            $res_count--;
                            //var_dump($this->datacenter->sockets[$aargs['datacenter']]->incoming_messages);
                            sleep(1);
                        }
                    } catch (\danog\MadelineProto\Exception $e) {
                        if ($e->getMessage() === 'I had to recreate the temporary authorization key') {
                            continue 2;
                        }
                        \danog\MadelineProto\Logger::log(['An error getting response of method '.$method.': '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...'], \danog\MadelineProto\Logger::WARNING);
                        continue;
                    } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                        \danog\MadelineProto\Logger::log(['An error getting response of method '.$method.': '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...'], \danog\MadelineProto\Logger::WARNING);
                        continue;
                    }
                }

                if ($canunset) {
                    $this->getting_state = false;
                    $this->handle_pending_updates();
                }
                if ($server_answer === null) {
                    throw new \danog\MadelineProto\Exception("Couldn't get response");
                }
                if (!isset($server_answer['_'])) {
                    return $server_answer;
                }
                switch ($server_answer['_']) {
                    case 'rpc_error':
                        $this->handle_rpc_error($server_answer, $aargs['datacenter']);
                        break;
                    case 'bad_server_salt':
                    case 'bad_msg_notification':
                        switch ($server_answer['error_code']) {
                            case 48:
                                $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['server_salt'] = $server_answer['new_server_salt'];
                                continue 3;
                            case 16:
                            case 17:
                                \danog\MadelineProto\Logger::log(['Received bad_msg_notification: '.$this->bad_msg_error_codes[$server_answer['error_code']]], \danog\MadelineProto\Logger::WARNING);
                                $this->datacenter->sockets[$aargs['datacenter']]->timedelta = (int) ((new \phpseclib\Math\BigInteger(strrev($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']), 256))->bitwise_rightShift(32)->subtract(new \phpseclib\Math\BigInteger(time()))->toString());
                                \danog\MadelineProto\Logger::log(['Set time delta to '.$this->datacenter->sockets[$aargs['datacenter']]->timedelta], \danog\MadelineProto\Logger::WARNING);
                                $this->reset_session();
                                $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key = null;
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
                if ($this->in_array($this->datacenter->sockets[$aargs['datacenter']]->protocol, ['http', 'https']) && $method !== 'http_wait') {
                    //$this->method_call('http_wait', ['max_wait' => $this->datacenter->sockets[$aargs['datacenter']]->timeout, 'wait_after' => 0, 'max_delay' => 0], ['datacenter' => $aargs['datacenter']]);
                } else {
                    $this->datacenter->sockets[$aargs['datacenter']]->close_and_reopen();
                }
                //sleep(1); // To avoid flooding
                continue;
            } finally {
                if (isset($aargs['heavy']) && $aargs['heavy'] && isset($message_id)) {
                    //$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['args'] = [];
                    $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id] = [];
                    unset($this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id]);
                }
                if (isset($message_id) && $method === 'req_pq') {
                    unset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]);
                    unset($this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id]);
                }
                if ($canunset) {
                    $this->getting_state = false;
                    $this->handle_pending_updates();
                }
            }

            if ($server_answer === null) {
                if ($last_recv === $this->last_recv && $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key !== null) {
                    \danog\MadelineProto\Logger::log(['WARNING: Resetting auth key...'], \danog\MadelineProto\Logger::WARNING);
                    $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key = null;
                    $this->init_authorization();

                    return $this->method_call($method, $args, $aargs);
                }
                throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.' ('.$last_error.').');
            }
            \danog\MadelineProto\Logger::log(['Got response for method '.$method.' @ try '.$count.' (response try '.$res_count.')'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id] = [];
            if (isset($message_chunks) && count($message_chunks)) {
                $server_answer = [$server_answer];
                foreach ($message_chunks as $message) {
                    $args['message'] = $message;
                    $server_answer[] = $this->method_call($method, $args, $aargs);
                }
            }

            return $server_answer;
        }
        if ($method === 'req_pq') {
            throw new \danog\MadelineProto\RPCErrorException('RPC_CALL_FAIL');
        }
        throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.' ('.$last_error.').');
    }

    public function object_call($object, $args = [], $aargs = ['message_id' => null, 'heavy' => false])
    {
        if (!$this->is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }
        if (!isset($aargs['datacenter'])) {
            throw new \danog\MadelineProto\Exception('No datacenter provided');
        }
        for ($count = 1; $count <= $this->settings['max_tries']['query']; $count++) {
            try {
                \danog\MadelineProto\Logger::log([$object === 'msgs_ack' ? 'ack '.$args['msg_ids'][0] : 'Sending object (try number '.$count.' for '.$object.')...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $message_id = $this->send_message($this->serialize_object(['type' => $object], $args), $this->content_related($object), $aargs);
                if ($object !== 'msgs_ack') {
                    $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['content'] = ['method' => $object, 'args' => $args];
                }
            } catch (Exception $e) {
                \danog\MadelineProto\Logger::log(['An error occurred while calling object '.$object.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call object...'], \danog\MadelineProto\Logger::WARNING);
                $this->datacenter->sockets[$aargs['datacenter']]->close_and_reopen();
                continue;
            }

            return $message_id;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while sending object '.$object.'.');
    }
}
