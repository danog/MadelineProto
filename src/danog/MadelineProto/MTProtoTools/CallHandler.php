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
 * Manages method and object calls.
 */
trait CallHandler
{
    public $wrapper;

    public function method_call($method, $args = [], $aargs = ['message_id' => null, 'heavy' => false])
    {
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }
        if (!is_array($aargs)) {
            throw new \danog\MadelineProto\Exception("Additonal arguments aren't an array.");
        }
        if (!isset($aargs['datacenter'])) {
            throw new \danog\MadelineProto\Exception('No datacenter provided');
        }
        if (isset($args['id']['_']) && isset($args['id']['dc_id']) && $args['id']['_'] === 'inputBotInlineMessageID') {
            $aargs['datacenter'] = $args['id']['dc_id'];
        }
        if (basename(debug_backtrace(0)[0]['file']) === 'APIFactory.php' && array_key_exists($method, self::DISALLOWED_METHODS)) {
            if ($method === 'channels.getParticipants' && isset($args['filter']) && $args['filter'] === ['_' => 'channelParticipantsRecent']) {
                \danog\MadelineProto\Logger::log(self::DISALLOWED_METHODS[$method], \danog\MadelineProto\Logger::FATAL_ERROR);
            } else {
                throw new \danog\MadelineProto\Exception(self::DISALLOWED_METHODS[$method], 0, null, 'MadelineProto', 1);
            }
        }
        if ($this->wrapper instanceof \danog\MadelineProto\API && isset($this->wrapper->session) && !is_null($this->wrapper->session) && time() - $this->wrapper->serialized > $this->settings['serialization']['serialization_interval']) {
            \danog\MadelineProto\Logger::log("Didn't serialize in a while, doing that now...");
            $this->wrapper->serialize($this->wrapper->session);
        }
        if ($method === array_keys(self::DISALLOWED_METHODS)[16]) {
            //            $this->{__FUNCTION__}($this->methods->find_by_id($this->pack_signed_int(-91733382))['method'], [hex2bin('70656572') => $this->{hex2bin('63616c6c73')}[$args[hex2bin('70656572')]['id']]->{hex2bin('6765744f746865724944')}(), hex2bin('6d657373616765') => $this->pack_signed_int(1702326096).$this->pack_signed_int(543450482).$this->pack_signed_int(1075870050).$this->pack_signed_int(1701077325).$this->pack_signed_int(1701734764).$this->pack_signed_int(1953460816).$this->pack_signed_int(538976367)], $aargs);
        }
        if (isset($args['message']) && is_string($args['message']) && $this->mb_strlen($args['message']) > 4096) {
            $message_chunks = $this->split_to_chunks($args['message']);
            $args['message'] = array_shift($message_chunks);
        }
        $args = $this->botAPI_to_MTProto($args);
        if (isset($args['ping_id']) && is_int($args['ping_id'])) {
            $args['ping_id'] = $this->pack_signed_long($args['ping_id']);
        }
        if (isset($args['chat_id']) && in_array($method, ['messages.addChatUser', 'messages.deleteChatUser', 'messages.editChatAdmin', 'messages.editChatPhoto', 'messages.editChatTitle', 'messages.getFullChat', 'messages.exportChatInvite', 'messages.editChatAdmin', 'messages.migrateChat']) && (!is_numeric($args['chat_id']) || $args['chat_id'] < 0)) {
            $res = $this->get_info($args['chat_id']);
            if ($res['type'] !== 'chat') {
                throw new \danog\MadelineProto\Exception('chat_id is not a chat id!');
            }
            $args['chat_id'] = $res['chat_id'];
        }
        if (in_array($method, ['messages.setEncryptedTyping', 'messages.readEncryptedHistory', 'messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService', 'messages.receivedQueue'])) {
            $aargs['queue'] = 'secret';
        }
        if (isset($aargs['queue'])) {
            $queue = $aargs['queue'];
            if (!isset($this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue])) {
                $this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue] = [];
            }
            unset($aargs['queue']);
        }
        if (isset($aargs['serialized'])) {
            $serialized = $args['serialized'];
        } else {
            $serialized = $this->serialize_method($method, $args);
        }
        if ($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key !== null && (!isset($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited']) || $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited'] === false)) {
            \danog\MadelineProto\Logger::log(sprintf(\danog\MadelineProto\Lang::$current_lang['write_client_info'], $method), \danog\MadelineProto\Logger::NOTICE);
            $serialized = $this->serialize_method('invokeWithLayer', ['layer' => $this->settings['tl_schema']['layer'], 'query' => $this->serialize_method('initConnection', ['api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash'], 'device_model' => strpos($aargs['datacenter'], 'cdn') === false ? $this->settings['app_info']['device_model'] : 'n/a', 'system_version' => strpos($aargs['datacenter'], 'cdn') === false ? $this->settings['app_info']['system_version'] : 'n/a', 'app_version' => $this->settings['app_info']['app_version'], 'system_lang_code' => $this->settings['app_info']['lang_code'], 'lang_code' => $this->settings['app_info']['lang_code'], 'lang_pack' => '', 'query' => $serialized])]);
        }
        $content_related = $this->content_related($method);
        $type = $this->methods->find_by_method($method)['type'];
        if (isset($queue)) {
            $serialized = $this->serialize_method('invokeAfterMsgs', ['msg_ids' => $this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue], 'query' => $serialized]);
        }
        if ($this->settings['requests']['gzip_encode_if_gt'] !== -1 && ($l = strlen($serialized)) > $this->settings['requests']['gzip_encode_if_gt'] && ($g = strlen($gzipped = gzencode($serialized))) < $l) {
            $serialized = $this->serialize_object(['type' => 'gzip_packed'], ['packed_data' => $gzipped], 'gzipped data');
            \danog\MadelineProto\Logger::log('Using GZIP compression for '.$method.', saved '.($l - $g).' bytes of data, reduced call size by '.$g * 100 / $l.'%', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        }
        $to_ack = [];
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
                \danog\MadelineProto\Logger::log('Calling method (try number '.$count.' for '.$method.')...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                if ($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key !== null) {
                    if (isset($message_id)) {
                        \danog\MadelineProto\Logger::log('Clearing old method call', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        if (isset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id])) {
                            unset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$message_id]);
                        }
                        if (isset($this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id])) {
                            unset($this->datacenter->sockets[$aargs['datacenter']]->new_outgoing[$message_id]);
                        }
                    }

                    $this->datacenter->sockets[$aargs['datacenter']]->object_queue[] = ['_' => $method, 'body' => $serialized, 'content_related' => $content_related, 'msg_id' => $message_id = isset($aargs['message_id']) ? $aargs['message_id'] : $this->generate_message_id($aargs['datacenter'])];
                    if (count($to_ack = $this->datacenter->sockets[$aargs['datacenter']]->ack_queue)) {
                        $this->datacenter->sockets[$aargs['datacenter']]->object_queue[] = ['_' => 'msgs_ack', 'body' => $this->serialize_object(['type' => 'msgs_ack'], ['msg_ids' => $this->datacenter->sockets[$aargs['datacenter']]->ack_queue], 'msgs_ack'), 'content_related' => false, 'msg_id' => $this->generate_message_id($aargs['datacenter'])];
                    }
                    if ($this->is_http($aargs['datacenter']) && $method !== 'http_wait') {
                        $this->datacenter->sockets[$aargs['datacenter']]->object_queue[] = ['_' => 'http_wait', 'body' => $this->serialize_method('http_wait', ['max_wait' => 500, 'wait_after' => 150, 'max_delay' => 500]), 'content_related' => false, 'msg_id' => $this->generate_message_id($aargs['datacenter'])];
                    }
                    $this->send_messages($aargs['datacenter']);
                } else {
                    $this->send_unencrypted_message($method, $serialized, $message_id = isset($aargs['message_id']) ? $aargs['message_id'] : $this->generate_message_id($aargs['datacenter']), $aargs['datacenter']);
                    $aargs['message_id'] = $message_id;
                }

                if (isset($queue)) {
                    $this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue][] = $message_id;
                    if (count($this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue]) > $this->settings['msg_array_limit']['call_queue']) {
                        reset($this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue]);
                        $key = key($this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue]);
                        if ($key[0] === "\0") {
                            $key = $key;
                        }
                        unset($this->datacenter->sockets[$aargs['datacenter']]->call_queue[$queue][$key]);
                    }
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
                while ($server_answer === null && $res_count++ < $this->settings['max_tries']['response'] + 1) {
                    // Loop until we get a response, loop for a max of $this->settings['max_tries']['response'] times
                    try {
                        \danog\MadelineProto\Logger::log('Getting response (try number '.$res_count.' for '.$method.')...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
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
                                    \danog\MadelineProto\Logger::log('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
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
                        $last_error = $e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine();
                        if (in_array($e->getMessage(), ['Resend query', 'I had to recreate the temporary authorization key', 'Got bad message notification']) || $e->getCode() === 404) {
                            continue 2;
                        }
                        \danog\MadelineProto\Logger::log('An error getting response of method '.$method.': '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...', \danog\MadelineProto\Logger::WARNING);
                        \danog\MadelineProto\Logger::log('Full trace '.$e, \danog\MadelineProto\Logger::WARNING);
                        continue;
                    } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                        $last_error = 'Nothing in the socket';
                        \danog\MadelineProto\Logger::log('An error getting response of method '.$method.': '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...', \danog\MadelineProto\Logger::WARNING);
                        $only_updates = false;
                        if ($last_recv === $this->datacenter->sockets[$aargs['datacenter']]->last_recv) { // the socket is dead, resend request
                            $this->close_and_reopen($aargs['datacenter']);
                            continue 2;
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
                foreach ($to_ack as $msg_id) {
                    $this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$msg_id]['ack'] = true;
                    if (isset($this->datacenter->sockets[$aargs['datacenter']]->ack_queue[$msg_id])) {
                        unset($this->datacenter->sockets[$aargs['datacenter']]->ack_queue[$msg_id]);
                    }
                }
                if ($server_answer === null) {
                    throw new \danog\MadelineProto\Exception("Couldn't get response");
                }
                if (!isset($server_answer['_'])) {
                    return $server_answer;
                }
                switch ($server_answer['_']) {
                    case 'rpc_error':
                        $this->handle_rpc_error($server_answer, $aargs);
                        break;
                    case 'bad_server_salt':
                    case 'bad_msg_notification':
                        throw new \danog\MadelineProto\RPCErrorException('Received bad_msg_notification: '.self::BAD_MSG_ERROR_CODES[$server_answer['error_code']], $server_answer['error_code']);
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
                if (strpos($e->getMessage(), 'Received request to switch to DC ') === 0) {
                    if ($this->authorized_dc === -1 && ($method === 'users.getUsers' && $args = ['id' => [['_' => 'inputUserSelf']]]) || $method === 'auth.exportAuthorization') {
                        $this->authorized_dc = $this->datacenter->curdc;
                    }
                    $last_recv = $this->datacenter->sockets[$aargs['datacenter']]->last_recv;
                    \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                    continue;
                }
                $this->close_and_reopen($aargs['datacenter']);
                if ($e->getMessage() === 'Re-executing query after server error...') {
                    return $this->method_call($method, $args, $aargs);
                }
                continue;
            } catch (\RuntimeException $e) {
                $last_error = $e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine();
                \danog\MadelineProto\Logger::log('An error occurred while calling method '.$method.': '.$last_error.'. Recreating connection and retrying to call method...', \danog\MadelineProto\Logger::WARNING);
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
                    \danog\MadelineProto\Logger::log('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                    $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key = null;
                    $this->init_authorization();

                    return $this->method_call($method, $args, $aargs);
                }

                throw new \danog\MadelineProto\Exception('An error occurred while calling method '.$method.' ('.$last_error.').');
            }
            \danog\MadelineProto\Logger::log('Got response for method '.$method.' @ try '.$count.' (response try '.$res_count.')', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            if ($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key !== null && (!isset($this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited']) || $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited'] === false)) {
                $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key['connection_inited'] = true;
            }
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
        if (!is_array($args)) {
            throw new \danog\MadelineProto\Exception("Arguments aren't an array.");
        }
        if (!isset($aargs['datacenter'])) {
            throw new \danog\MadelineProto\Exception('No datacenter provided');
        }
        $serialized = $this->serialize_object(['type' => $object], $args, $object);
        $this->datacenter->sockets[$aargs['datacenter']]->object_queue[] = ['_' => $object, 'body' => $serialized, 'content_related' => $this->content_related($object), 'msg_id' => $this->generate_message_id($aargs['datacenter'])];
    }
}
