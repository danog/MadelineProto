<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed i                            $this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id]['promise']->reject(new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']));
                            $this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id]['promise']->reject(new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']));
n the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
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
    public $wrapper;

    public function iorun()
    {
        try {
            if (!$this->is_http($this->datacenter->curdc) && !$this->altervista) {
                try {
                    $waiting = $this->datacenter->select();
                    if (count($waiting)) {
                        $tries = 10;
                        while (count($waiting) && $tries--) {
                            $dc = $waiting[0];
                            if (($error = $this->recv_message($dc)) !== true) {
                                if ($error === -404) {
                                    if ($this->datacenter->sockets[$dc]->temp_auth_key !== null) {
                                        $this->logger->logger('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                                        $this->datacenter->sockets[$dc]->temp_auth_key = null;
                                        $this->init_authorization();

                                        throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                                    }
                                }

                                throw new \danog\MadelineProto\RPCErrorException($error, $error);
                            }
                            $only_updates = $this->handle_messages($dc);
                            $waiting = $this->datacenter->select(true);
                        }
                    } else {
                        $this->poll();
                    }
                } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                    $this->poll();
                }
            } else {
                $this->poll();
            }
            if (time() - $this->last_getdifference > $this->settings['updates']['getdifference_interval']) {
                $this->poll();
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            throw $e;
        } catch (\danog\MadelineProto\Exception $e) {
            $this->connect_to_all_dcs();
        }
    }

    public function poll()
    {
        if ($this->settings['updates']['handle_updates']) {
            $this->get_updates_difference();
        } else {
            foreach ($this->datacenter->sockets as $datacenter => $socket) {

                $this->send_messages($datacenter);
                if (($error = $this->recv_message($datacenter)) !== true) {
                    var_dump($error);
                    if ($error === -404) {
                        if ($this->datacenter->sockets[$datacenter]->temp_auth_key !== null) {
                            $this->logger->logger('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                            $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                            $this->init_authorization();
    
                            throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                        }
                        $this->check_pending_calls_dc($datacenter);
                        return;
                    }
    
                    throw new \danog\MadelineProto\RPCErrorException($error, $error);
                }
    
                }
            $this->last_getdifference = time();
        }
    }

    public function check_pending_calls()
    {
        foreach ($this->datacenter->sockets as $datacenter => $socket) {
            $this->check_pending_calls_dc($datacenter);
        }
    }

    public function check_pending_calls_dc($datacenter) {
        $zis = $this;
        if (!empty($this->datacenter->sockets[$datacenter]->new_outgoing)) {
            $promise = new \GuzzleHttp\Promise\Promise();
            $promise->then(
                function ($result) use ($datacenter, $zis) {
                    $zis->logger->logger($result, \danog\MadelineProto\Logger::NOTICE);
                }, 
                function ($error) use ($datacenter, $zis) {
                    $zis->logger->logger($error, \danog\MadelineProto\Logger::FATAL_ERROR);
                }
            );
            $this->object_call('msgs_state_req', ['msg_ids' => $this->datacenter->sockets[$datacenter]->new_outgoing], ['promise' => $promise]);
        }
    }

    public function method_recall($message_id, $new_datacenter, $old_datacenter = false)
    {
        if ($old_datacenter === false) {
            $old_datacenter = $new_datacenter;
        }

        if (isset($this->datacenter->sockets[$old_datacenter][$message_id]['container'])) {
            $message_ids = $this->datacenter->sockets[$old_datacenter][$message_id]['container'];
        } else {
            $message_ids = [$message_id];
        }

        foreach ($message_ids as $message_id) {
            $this->append_message($this->datacenter->sockets[$old_datacenter][$message_id], ['datacenter' => $new_datacenter]);
            $this->ack_outgoing_message_id($message_id, $datacenter);
        }
        
        $this->send_messages($new_datacenter);
    }

    public function method_call($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
    {
        $promise = $this->method_call_async($method, $args, $aargs);
        do {
            $this->iorun();
        } while ($promise->getState() === 'pending');

        $promise->then(
            function ($result) use (&$out) {
                $out = $result;
            },
            function ($error) {
                throw $error;
            }
        );
        var_dump($out);
        return $out;
    }

    public function method_call_async($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
    {
        if (isset($args['id']['_']) && isset($args['id']['dc_id']) && $args['id']['_'] === 'inputBotInlineMessageID') {
            $aargs['datacenter'] = $args['id']['dc_id'];
        }
        if ($this->wrapper instanceof \danog\MadelineProto\API && isset($this->wrapper->session) && !is_null($this->wrapper->session) && time() - $this->wrapper->serialized > $this->settings['serialization']['serialization_interval']) {
            $this->logger->logger("Didn't serialize in a while, doing that now...");
            $this->wrapper->serialize($this->wrapper->session);
        }
        if (isset($aargs['file']) && $aargs['file'] && isset($this->datacenter->sockets[$aargs['datacenter'] . '_media'])) {
            \danog\MadelineProto\Logger::log('Using media DC');
            $aargs['datacenter'] .= '_media';
        }
        if (in_array($method, ['messages.setEncryptedTyping', 'messages.readEncryptedHistory', 'messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService', 'messages.receivedQueue'])) {
            $aargs['queue'] = 'secret';
        }
        
        if (isset($args['message']) && is_string($args['message']) && $this->mb_strlen($args['message']) > $this->config['message_length_max']) {
            $arg_chunks = $this->split_to_chunks($args);
            $args = array_shift($arg_chunks);
        }
        $args = $this->botAPI_to_MTProto($args);
        if (isset($args['ping_id']) && is_int($args['ping_id'])) {
            $args['ping_id'] = $this->pack_signed_long($args['ping_id']);
        }

        $promise = new \GuzzleHttp\Promise\Promise();
        $message = ['_' => $method, 'type' => $this->methods->find_by_method($method)['type'], 'content_related' => $this->content_related($method), 'promise' => $promise];

        if (isset($aargs['serialized'])) {
            $message['body'] = $aargs['serialized'];
        } else {
            $message['body'] = $this->serialize_method($method, $args);
        }
        if (isset($aargs['msg_id'])) {
            $message['msg_id'] = $aargs['msg_id'];
        }
        if (isset($aargs['file'])) {
            $message['file'] = $aargs['file'];
        }
        $this->append_message($message, $aargs['datacenter']);

        return $promise;

        $last_recv = $this->datacenter->sockets[$aargs['datacenter']]->last_recv;
        for ($count = 1; $count <= $this->settings['max_tries']['query']; $count++) {
            if ($canunset = !$this->updates_state['sync_loading']) {
                $this->updates_state['sync_loading'] = true;
            }
            if ($canunsetpostponeupdates = !$this->postpone_updates) {
                $this->postpone_updates = true;
            }
            if ($canunsetpostponepwrchat = !$this->postpone_pwrchat) {
                $this->postpone_pwrchat = true;
            }

            try {
                $this->logger->logger('Calling method (try number ' . $count . ' for ' . $method . ')...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                if (isset($queue)) {
                }
                if ($method === 'http_wait' || isset($aargs['noResponse']) && $aargs['noResponse']) {
                    return true;
                }
                //$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['content'] = ['method' => $method, 'args' => $args];
                $this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id] = ['msg_id' => $message_id, 'method' => $method, 'type' => $type];
                $res_count = 0;
                $server_answer = null;
                $update_count = 0;
                $only_updates = false;
                $response_tries = $this->settings['max_tries']['response'] + 1;
                if ($last_recv) {
                    $additional = (int) floor((time() - $last_recv) / 10);
                    if ($additional > $this->settings['max_tries']['response'] * 2) {
                        $additional = $this->settings['max_tries']['response'] * 2;
                    }
                    $response_tries += $additional;
                }
                while ($server_answer === null && $res_count++ < $response_tries) {
                    // Loop until we get a response, loop for a max of $this->settings['max_tries']['response'] times
                    try {
                        $this->logger->logger('Getting response (try number ' . $res_count . ' for ' . $method . ')...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        if (!isset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']) || !isset($this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']]['content'])) {
                            // Checks if I have received the response to the called method, if not continue looping
                            if ($only_updates) {
                                if ($update_count > 50) {
                                    $update_count = 0;
                                } else {
                                    $res_count--;
                                    $update_count++;
                                }
                            }
                        } else {
                            $server_answer = $this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']]['content'];
                            $this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['response']]['content'] = '';
                            break;
                        }
                        if (($error = $this->recv_message($aargs['datacenter'])) !== true) {
                            if ($error === -404) {
                                if ($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key !== null) {
                                    $this->logger->logger('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                                    $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key = null;
                                    $this->init_authorization();

                                    throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                                }
                            }

                            throw new \danog\MadelineProto\RPCErrorException($error, $error);
                        }
                        $only_updates = $this->handle_messages($aargs['datacenter']);
                        // This method receives data from the socket, and parses stuff
                    } catch (\danog\MadelineProto\Exception $e) {
                        $last_error = $e->getMessage() . ' in ' . basename($e->getFile(), '.php') . ' on line ' . $e->getLine();
                        if (in_array($e->getMessage(), ['Resend query', 'I had to recreate the temporary authorization key', 'Got bad message notification']) || $e->getCode() === 404) {
                            continue 2;
                        }
                        $this->logger->logger('An error getting response of method ' . $method . ': ' . $e->getMessage() . ' in ' . basename($e->getFile(), '.php') . ' on line ' . $e->getLine() . '. Retrying...', \danog\MadelineProto\Logger::WARNING);
                        $this->logger->logger('Full trace ' . $e, \danog\MadelineProto\Logger::WARNING);
                        continue;
                    } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                        $last_error = 'Nothing in the socket';
                        $this->logger->logger('An error getting response of method ' . $method . ': ' . $e->getMessage() . ' in ' . basename($e->getFile(), '.php') . ' on line ' . $e->getLine() . '. Retrying...', \danog\MadelineProto\Logger::WARNING);
                        $only_updates = false;
                        if ($last_recv === $this->datacenter->sockets[$aargs['datacenter']]->last_recv) { // the socket is dead, resend request
                            $this->close_and_reopen($aargs['datacenter']);
                            if ($this->altervista) {
                                continue 2;
                            }
                        }
                        //if ($this->datacenter->sockets[$aargs['datacenter']]->last_recv < time() - 1 && $this->is_http($aargs['datacenter'])) {
                        //    $this->close_and_reopen($aargs['datacenter']);
                        //    continue 2;
                        //}
                        continue; //2;
                    }
                }
                if ($canunset) {
                    $this->updates_state['sync_loading'] = false;
                }
                if ($canunsetpostponepwrchat) {
                    $this->postpone_pwrchat = false;
                    $this->handle_pending_pwrchat();
                }
                if ($canunsetpostponeupdates) {
                    $this->postpone_updates = false;
                    $this->handle_pending_updates();
                }
                if ($server_answer === null) {
                    throw new \danog\MadelineProto\Exception("Couldn't get response");
                }
                if (!isset($server_answer['_'])) {
                    return $server_answer;
                }
                if (isset($aargs['botAPI']) && $aargs['botAPI']) {
                    $server_answer = $this->MTProto_to_botAPI($server_answer, $args);
                }
            } catch (\danog\MadelineProto\Exception $e) {
                $last_error = $e->getMessage() . ' in ' . basename($e->getFile(), '.php') . ' on line ' . $e->getLine();
                if (strpos($e->getMessage(), 'Received request to switch to DC ') === 0) {
                    if (($method === 'users.getUsers' && $args = ['id' => [['_' => 'inputUserSelf']]]) || $method === 'auth.exportAuthorization' || $method === 'updates.getDifference') {
                        $this->settings['connection_settings']['default_dc'] = $this->authorized_dc = $this->datacenter->curdc;
                    }
                    $last_recv = $this->datacenter->sockets[$aargs['datacenter']]->last_recv;
                    $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                    continue;
                }
                $this->close_and_reopen($aargs['datacenter']);
                if ($e->getMessage() === 'Re-executing query after server error...') {
                    return $this->method_call($method, $args, $aargs);
                }
                continue;
            } catch (\RuntimeException $e) {
                $last_error = $e->getMessage() . ' in ' . basename($e->getFile(), '.php') . ' on line ' . $e->getLine();
                $this->logger->logger('An error occurred while calling method ' . $method . ': ' . $last_error . '. Recreating connection and retrying to call method...', \danog\MadelineProto\Logger::WARNING);
                $this->close_and_reopen($aargs['datacenter']);
                continue;
            } finally {
                if (isset($aargs['heavy']) && $aargs['heavy'] && isset($message_id)) {
                    //$this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]['args'] = [];
                    unset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]);
                    unset($this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id]);
                }
                if (isset($message_id) && $method === 'req_pq') {
                    unset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]);
                    unset($this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id]);
                }
                if ($canunset) {
                    $this->updates_state['sync_loading'] = false;
                }
                if ($canunsetpostponepwrchat) {
                    $this->postpone_pwrchat = false;
                    $this->handle_pending_pwrchat();
                }
                if ($canunsetpostponeupdates) {
                    $this->postpone_updates = false;
                    $this->handle_pending_updates();
                }
            }
            if ($server_answer === null) {
                if ($last_recv === $this->datacenter->sockets[$aargs['datacenter']]->last_recv && $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key !== null) {
                    $this->logger->logger('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                    $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key = null;
                    $this->init_authorization();

                    return $this->method_call($method, $args, $aargs);
                }

                throw new \danog\MadelineProto\Exception('An error occurred while calling method ' . $method . ' (' . $last_error . ').');
            }
            $this->logger->logger('Got response for method ' . $method . ' @ try ' . $count . ' (response try ' . $res_count . ')', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            if ($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key !== null && (!isset($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited']) || $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited'] === false)) {
                $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited'] = true;
            }
            $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id] = [];
            if (isset($arg_chunks) && count($arg_chunks)) {
                $server_answer = [$server_answer];
                foreach ($arg_chunks as $args) {
                    $server_answer[] = $this->method_call($method, $args, $aargs);
                }
            }

            return $server_answer;
        }
        if ($method === 'req_pq') {
            throw new \danog\MadelineProto\RPCErrorException('RPC_CALL_FAIL');
        }

        throw new \danog\MadelineProto\Exception('An error occurred while calling method ' . $method . ' (' . $last_error . ').');

    }

    public function object_call($object, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
    {
        $message = ['_' => $object, 'body' => $this->serialize_object(['type' => $object], $args, $object), 'content_related' => $this->content_related($object)];
        if (isset($aargs['promise'])) {
            $message['promise'] = $aargs['promise'];
        }
        $this->append_message($message, $aargs['datacenter']);
    }

    /*
    $message = [
        // only in outgoing messages
        'body' => 'serialized body', (optional if container)
        'content_related' => bool,
        '_' => 'predicate',
        'promise' => promise object (optional),
        'file' => bool (optional),
        'type' => 'type' (optional),
        'queue' => queue ID (optional),
        'container' => [message ids] (optional),

        // only in incoming messages
        'content' => deserialized body,
        'seq_no' => number (optional),
        'from_container' => bool (optional),

        // can be present in both
        'response' => message id (optional),
        'msg_id' => message id (optional),
        'sent' => timestamp,
        'tries' => number
    ];
    */
    public function append_message(&$message, $datacenter)
    {
        if ($this->datacenter->sockets[$datacenter]->temp_auth_key !== null) {
            $this->datacenter->sockets[$datacenter]->pending_outgoing[$this->datacenter->sockets[$datacenter]->pending_outgoing_key++] = $message;
        } else {
            $this->send_unencrypted_message($message, $datacenter);
        }
    }
}
