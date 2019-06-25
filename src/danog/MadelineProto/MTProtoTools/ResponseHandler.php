<?php

/**
 * ResponseHandler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Loop;

/**
 * Manages responses.
 */
trait ResponseHandler
{
    public function send_msgs_state_info_async($req_msg_id, $msg_ids, $datacenter)
    {
        $this->logger->logger('Sending state info for '.count($msg_ids).' message IDs');
        $info = '';
        foreach ($msg_ids as $msg_id) {
            $cur_info = 0;
            if (!isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id])) {
                $msg_id = new \phpseclib\Math\BigInteger(strrev($msg_id), 256);
                if ((new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$datacenter]->time_delta + 30))->bitwise_leftShift(32)->compare($msg_id) < 0) {
                    $this->logger->logger("Do not know anything about $msg_id and it is too small");
                    $cur_info |= 3;
                } elseif ((new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$datacenter]->time_delta - 300))->bitwise_leftShift(32)->compare($msg_id) > 0) {
                    $this->logger->logger("Do not know anything about $msg_id and it is too big");
                    $cur_info |= 1;
                } else {
                    $this->logger->logger("Do not know anything about $msg_id");
                    $cur_info |= 2;
                }
            } else {
                $this->logger->logger("Know about $msg_id");
                $cur_info |= 4;
            }
            $info .= chr($cur_info);
        }
        $this->datacenter->sockets[$datacenter]->outgoing_messages[yield $this->object_call_async('msgs_state_info', ['req_msg_id' => $req_msg_id, 'info' => $info], ['datacenter' => $datacenter, 'postpone' => true])]['response'] = $req_msg_id;
    }

    public $n = 0;

    public function handle_messages($datacenter, $actual_datacenter = null)
    {
        if ($actual_datacenter) {
            $datacenter = $actual_datacenter;
        }
        $only_updates = true;
        while ($this->datacenter->sockets[$datacenter]->new_incoming) {
            reset($this->datacenter->sockets[$datacenter]->new_incoming);
            $current_msg_id = key($this->datacenter->sockets[$datacenter]->new_incoming);
            if (!isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id])) {
                unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                continue;
            }
            $this->logger->logger((isset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['from_container']) ? 'Inside of container, received ' : 'Received ').$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_'].' from DC '.$datacenter, \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            switch ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_']) {
                case 'msgs_ack':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        $this->ack_outgoing_message_id($msg_id, $datacenter);
                        // Acknowledge that the server received my message
                    }

                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'rpc_result':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    $only_updates = false;
                    // Acknowledge that the server received my request
                    $req_msg_id = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'];
                    $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result'];
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);

                    $this->handle_response($req_msg_id, $current_msg_id, $datacenter);
                    break;

                case 'future_salts':
                case 'msgs_state_info':
                    $msg_id_type = 'req_msg_id';
                case 'bad_server_salt':
                case 'bad_msg_notification':
                    $msg_id_type = isset($msg_id_type) ? $msg_id_type : 'bad_msg_id';
                case 'pong':
                    $msg_id_type = isset($msg_id_type) ? $msg_id_type : 'msg_id';
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;

                    $this->handle_response($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'][$msg_id_type], $current_msg_id, $datacenter);
                    unset($msg_id_type);
                    break;

                case 'new_session_created':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;

                    $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['server_salt'];
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);

                    // Acknowledge that I received the server's response
                    if ($this->authorized === self::LOGGED_IN && !$this->initing_authorization && $this->datacenter->sockets[$this->datacenter->curdc]->temp_auth_key !== null && isset($this->updaters[false])) {
                        $this->updaters[false]->resumeDefer();
                    }

                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'msg_container':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $only_updates = false;

                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['messages'] as $message) {
                        $this->datacenter->sockets[$datacenter]->check_message_id($message['msg_id'], ['outgoing' => false, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body'], 'from_container' => true];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['msg_id']] = $message['msg_id'];
                    }
                    ksort($this->datacenter->sockets[$datacenter]->new_incoming);
                    //$this->handle_messages($datacenter);
                    //$this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);

                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'msg_copy':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;

                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that I received the server's response
                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id'], $datacenter);
                    // Acknowledge that I received the server's response
                    } else {
                        $message = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'];
                        $this->datacenter->sockets[$datacenter]->check_message_id($message['orig_message']['msg_id'], ['outgoing' => false, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['orig_message']['msg_id']] = ['content' => $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['orig_message']['msg_id']] = $message['orig_message']['msg_id'];
                    }

                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;

                case 'http_wait':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;

                    $this->logger->logger($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'], \danog\MadelineProto\Logger::NOTICE);

                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;

                case 'msgs_state_req':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                    $this->callFork($this->send_msgs_state_info_async($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter));
                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'msgs_all_info':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $key => $msg_id) {
                        $info = ord($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['info'][$key]);
                        $msg_id = new \phpseclib\Math\BigInteger(strrev($msg_id), 256);
                        $status = 'Status for message id '.$msg_id.': ';
                        /*if ($info & 4) {
                         *$this->got_response_for_outgoing_message_id($msg_id, $datacenter);
                         *}
                         */
                        foreach (self::MSGS_INFO_FLAGS as $flag => $description) {
                            if (($info & $flag) !== 0) {
                                $status .= $description;
                            }
                        }
                        $this->logger->logger($status, \danog\MadelineProto\Logger::NOTICE);
                    }
                    break;
                case 'msg_detailed_info':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                    $only_updates = false;
                    if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']])) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                            $this->handle_response($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id'], $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'], $datacenter);
                        } else {
                            $this->callFork($this->object_call_async('msg_resend_req', ['msg_ids' => [$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']]], ['datacenter' => $datacenter, 'postpone' => true]));
                        }
                    }
                    break;
                case 'msg_new_detailed_info':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'], $datacenter);
                    } else {
                        $this->callFork($this->object_call_async('msg_resend_req', ['msg_ids' => [$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']]], ['datacenter' => $datacenter, 'postpone' => true]));
                    }
                    break;
                case 'msg_resend_req':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                    $ok = true;
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        if (!isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$msg_id]) || isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id])) {
                            $ok = false;
                        }
                    }
                    if ($ok) {
                        foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                            $this->method_recall('', ['message_id' => $msg_id, 'datacenter' => $datacenter, 'postpone' => true]);
                        }
                    } else {
                        $this->callFork($this->send_msgs_state_info_async($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter));
                    }
                    break;
                case 'msg_resend_ans_req':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                    $this->callFork($this->send_msgs_state_info_async($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter));
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']) && isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']])) {
                            $this->callFork($this->object_call_async($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['_'], $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['body'], ['datacenter' => $datacenter, 'postpone' => true]));
                        }
                    }
                    break;
                default:
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that I received the server's response
                    $response_type = $this->constructors->find_by_predicate($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['_'])['type'];

                    switch ($response_type) {
                        case 'Updates':
                            unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);

                            if (strpos($datacenter, 'cdn') === false) {
                                $this->callForkDefer($this->handle_updates_async($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']));
                            }

                            unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);

                            $only_updates = true && $only_updates;
                            break;
                        default:
                            $only_updates = false;
                            $this->logger->logger('Trying to assign a response of type '.$response_type.' to its request...', \danog\MadelineProto\Logger::VERBOSE);
                            foreach ($this->datacenter->sockets[$datacenter]->new_outgoing as $key => $expecting_msg_id) {
                                $expecting = $this->datacenter->sockets[$datacenter]->outgoing_messages[$expecting_msg_id];
                                if (!isset($expecting['type'])) {
                                    continue;
                                }

                                $this->logger->logger('Does the request of return type '.$expecting['type'].' match?', \danog\MadelineProto\Logger::VERBOSE);
                                if ($response_type === $expecting['type']) {
                                    $this->logger->logger('Yes', \danog\MadelineProto\Logger::VERBOSE);
                                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                                    $this->handle_response($expecting_msg_id, $current_msg_id, $datacenter);
                                    break 2;
                                }
                                $this->logger->logger('No', \danog\MadelineProto\Logger::VERBOSE);
                            }

                            throw new \danog\MadelineProto\ResponseException('Dunno how to handle '.PHP_EOL.var_export($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'], true));
                            break;
                    }
                    break;
            }
        }
        if ($this->datacenter->sockets[$datacenter]->pending_outgoing) 
        $this->datacenter->sockets[$datacenter]->writer->resume();

        //$this->n--;

        return $only_updates;
    }

    public function handle_reject($datacenter, &$request, $data)
    {
        if (isset($request['promise']) && is_object($request['promise'])) {
            Loop::defer(function () use (&$request, $data) {
                if (isset($request['promise'])) {
                    $promise = $request['promise'];
                    unset($request['promise']);
                    try {
                        $promise->fail($data);
                    } catch (\Error $e) {
                        if (strpos($e->getMessage(), "Promise has already been resolved") !== 0) {
                            throw $e;
                        }
                        $this->logger->logger("Got promise already resolved error", \danog\MadelineProto\Logger::FATAL_ERROR);
                    }

                } else {
                    $this->logger->logger('Rejecting: already got response for '.(isset($request['_']) ? $request['_'] : '-'));
                    $this->logger->logger("Rejecting: $data");
                }
            });
        } elseif (isset($request['container'])) {
            foreach ($request['container'] as $message_id) {
                $this->handle_reject($datacenter, $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id], $data);
            }
        } else {
            $this->logger->logger('Rejecting: already got response for '.(isset($request['_']) ? $request['_'] : '-'));
            $this->logger->logger("Rejecting: $data");
        }
    }

    public function handle_response($request_id, $response_id, $datacenter)
    {
        $response = &$this->datacenter->sockets[$datacenter]->incoming_messages[$response_id]['content'];
        unset($this->datacenter->sockets[$datacenter]->incoming_messages[$response_id]['content']);
        $request = &$this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id];

        if (isset($response['_'])) {
            switch ($response['_']) {
                case 'rpc_error':
                    if (isset($request['method']) && $request['method'] && $request['_'] !== 'auth.bindTempAuthKey' && $this->datacenter->sockets[$datacenter]->temp_auth_key !== null && (!isset($this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) || $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] === false)) {
                        $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] = true;
                    }

                    if (in_array($response['error_message'], ['PERSISTENT_TIMESTAMP_EMPTY', 'PERSISTENT_TIMESTAMP_OUTDATED', 'PERSISTENT_TIMESTAMP_INVALID'])) {
                        $this->got_response_for_outgoing_message_id($request_id, $datacenter);
                        $this->handle_reject($datacenter, $request, new \danog\MadelineProto\PTSException($response['error_message']));

                        return;
                    }
                    if (strpos($response['error_message'], 'FILE_REFERENCE_') === 0) {
                        $this->logger->logger("Got {$response['error_message']}, refreshing file reference and repeating method call...");

                        $request['refresh_references'] = true;
                        if (isset($request['serialized_body'])) {
                            unset($request['serialized_body']);
                        }

                        $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter, 'postpone' => true]);

                        return;
                    }
                    switch ($response['error_code']) {
                        case 500:
                        case -500:
                            if ($response['error_message'] === 'MSG_WAIT_FAILED') {
                                $this->datacenter->sockets[$datacenter]->call_queue[$request['queue']] = [];
                                $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter, 'postpone' => true]);

                                return;
                            }
                            $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code'], isset($request['_']) ? $request['_'] : ''));

                            return;
                        case 303:
                            $old_datacenter = $datacenter;
                            $this->datacenter->curdc = $datacenter = (int) preg_replace('/[^0-9]+/', '', $response['error_message']);

                            if (isset($request['file']) && $request['file'] && isset($this->datacenter->sockets[$datacenter.'_media'])) {
                                \danog\MadelineProto\Logger::log('Using media DC');
                                $datacenter .= '_media';
                            }

                            if (isset($request['user_related']) && $request['user_related']) {
                                $this->settings['connection_settings']['default_dc'] = $this->authorized_dc = $this->datacenter->curdc;
                            }
                            Loop::defer([$this, 'method_recall'], ['message_id' => $request_id, 'datacenter' => $datacenter, 'old_datacenter' => $old_datacenter]);
                            //$this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter, 'old_datacenter' => $old_datacenter, 'postpone' => true]);

                            return;
                        case 401:
                            switch ($response['error_message']) {
                                case 'USER_DEACTIVATED':
                                case 'SESSION_REVOKED':
                                case 'SESSION_EXPIRED':
                                    $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                                    $this->logger->logger($response['error_message'], \danog\MadelineProto\Logger::FATAL_ERROR);
                                    foreach ($this->datacenter->sockets as $socket) {
                                        $socket->temp_auth_key = null;
                                        $socket->session_id = null;
                                        $socket->auth_key = null;
                                        $socket->authorized = false;
                                    }

                                    if ($response['error_message'] === 'USER_DEACTIVATED') {
                                        $this->logger->logger('!!!!!!! WARNING !!!!!!!', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger("Telegram's flood prevention system suspended this account.", \danog\MadelineProto\Logger::ERROR);
                                        $this->logger->logger('To continue, manual verification is required.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $phone = isset($this->authorization['user']['phone']) ? '+'.$this->authorization['user']['phone'] : 'you are currently using';
                                        $this->logger->logger('Send an email to recover@telegram.org, asking to unban the phone number '.$phone.', and shortly describe what will you do with this phone number.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger('Then login again.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger('If you intentionally deleted this account, ignore this message.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                    }

                                    $this->resetSession();

                                    $this->callFork((function () use ($datacenter, &$request, &$response) {
                                        yield $this->init_authorization_async();

                                        $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code'], isset($request['_']) ? $request['_'] : ''));
                                    })());

                                    return;
                                case 'AUTH_KEY_UNREGISTERED':
                                case 'AUTH_KEY_INVALID':
                                    if ($this->authorized !== self::LOGGED_IN) {
                                        $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                                        $this->callFork((function () use ($datacenter, &$request, &$response) {
                                            yield $this->init_authorization_async();

                                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code'], isset($request['_']) ? $request['_'] : ''));
                                        })());

                                        return;
                                    }
                                    $this->datacenter->sockets[$datacenter]->session_id = null;
                                    $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                                    $this->datacenter->sockets[$datacenter]->auth_key = null;
                                    $this->datacenter->sockets[$datacenter]->authorized = false;

                                    $this->logger->logger('Auth key not registered, resetting temporary and permanent auth keys...', \danog\MadelineProto\Logger::ERROR);

                                    if ($this->authorized_dc === $datacenter && $this->authorized === self::LOGGED_IN) {
                                        $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                                        $this->logger->logger('Permanent auth key was main authorized key, logging out...', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        foreach ($this->datacenter->sockets as $socket) {
                                            $socket->temp_auth_key = null;
                                            $socket->auth_key = null;
                                            $socket->authorized = false;
                                        }
                                        $this->logger->logger('!!!!!!! WARNING !!!!!!!', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger("Telegram's flood prevention system suspended this account.", \danog\MadelineProto\Logger::ERROR);
                                        $this->logger->logger('To continue, manual verification is required.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $phone = isset($this->authorization['user']['phone']) ? '+'.$this->authorization['user']['phone'] : 'you are currently using';
                                        $this->logger->logger('Send an email to recover@telegram.org, asking to unban the phone number '.$phone.', and quickly describe what will you do with this phone number.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger('Then login again.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger('If you intentionally deleted this account, ignore this message.', \danog\MadelineProto\Logger::FATAL_ERROR);

                                        $this->resetSession();

                                        $this->callFork((function () use ($datacenter, &$request, &$response) {
                                            yield $this->init_authorization_async();

                                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code'], isset($request['_']) ? $request['_'] : ''));
                                        })());

                                        return;
                                    }
                                    $this->callFork((function () use ($request_id, $datacenter) {
                                        yield $this->init_authorization_async();

                                        $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter]);
                                    })());

                                    return;
                                case 'AUTH_KEY_PERM_EMPTY':
                                    $this->logger->logger('Temporary auth key not bound, resetting temporary auth key...', \danog\MadelineProto\Logger::ERROR);

                                    $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                                    $this->callFork((function () use ($request_id, $datacenter) {
                                        yield $this->init_authorization_async();
                                        $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter]);
                                    })());

                                    return;
                            }
                            $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code'], isset($request['_']) ? $request['_'] : ''));

                            return;
                        case 420:
                            $seconds = preg_replace('/[^0-9]+/', '', $response['error_message']);
                            $limit = isset($request['FloodWaitLimit']) ? $request['FloodWaitLimit'] : $this->settings['flood_timeout']['wait_if_lt'];
                            if (is_numeric($seconds) && $seconds < $limit) {
                                //$this->got_response_for_outgoing_message_id($request_id, $datacenter);

                                $this->logger->logger('Flood, waiting '.$seconds.' seconds before repeating async call...', \danog\MadelineProto\Logger::NOTICE);
                                $request['sent'] += $seconds;
                                Loop::delay($seconds * 1000, [$this, 'method_recall'], ['message_id' => $request_id, 'datacenter' => $datacenter]);

                                return;
                            }
                        // no break
                        default:
                            $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code'], isset($request['_']) ? $request['_'] : ''));

                            return;
                    }

                    return;
                case 'boolTrue':
                case 'boolFalse':
                    $response = $response['_'] === 'boolTrue';
                    break;
                case 'bad_server_salt':
                case 'bad_msg_notification':
                    $this->logger->logger('Received bad_msg_notification: '.self::BAD_MSG_ERROR_CODES[$response['error_code']], \danog\MadelineProto\Logger::WARNING);
                    switch ($response['error_code']) {
                        case 48:
                            $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'] = $response['new_server_salt'];
                            $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter, 'postpone' => true]);

                            return;
                        case 16:
                        case 17:
                            $this->datacenter->sockets[$datacenter]->time_delta = (int) (new \phpseclib\Math\BigInteger(strrev($response_id), 256))->bitwise_rightShift(32)->subtract(new \phpseclib\Math\BigInteger(time()))->toString();
                            $this->logger->logger('Set time delta to '.$this->datacenter->sockets[$datacenter]->time_delta, \danog\MadelineProto\Logger::WARNING);
                            $this->reset_session();
                            $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                            $this->callFork((function () use ($datacenter, $request_id) {
                                yield $this->init_authorization_async();
                                $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter]);
                            })());

                            return;
                    }
                    $this->got_response_for_outgoing_message_id($request_id, $datacenter);
                    $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException('Received bad_msg_notification: '.self::BAD_MSG_ERROR_CODES[$response['error_code']], $response['error_code'], isset($request['_']) ? $request['_'] : ''));

                    return;
            }
        }

        if (isset($request['method']) && $request['method'] && $request['_'] !== 'auth.bindTempAuthKey' && $this->datacenter->sockets[$datacenter]->temp_auth_key !== null && (!isset($this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) || $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] === false)) {
            $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] = true;
        }

        if (!isset($request['promise'])) {
            $this->logger->logger('Response: already got response for '.(isset($request['_']) ? $request['_'] : '-').' with message ID '.$request_id);

            return;
        }
        $botAPI = isset($request['botAPI']) && $request['botAPI'];
        if (isset($response['_']) && strpos($datacenter, 'cdn') === false && $this->constructors->find_by_predicate($response['_'])['type'] === 'Updates') {
            $response['request'] = $request;
            $this->callForkDefer($this->handle_updates_async($response));
        }
        unset($request);
        $this->got_response_for_outgoing_message_id($request_id, $datacenter);
        $r = isset($response['_']) ? $response['_'] : json_encode($response);
        $this->logger->logger("Defer sending $r to deferred");
        $this->callFork((
            function () use ($request_id, $response, $datacenter, $botAPI) {
                $r = isset($response['_']) ? $response['_'] : json_encode($response);
                $this->logger->logger("Deferred: sent $r to deferred");
                if ($botAPI) {
                    $response = yield $this->MTProto_to_botAPI_async($response);
                }
                if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id]['promise'])) { // This should not happen but happens, should debug
                    $promise = $this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id]['promise'];
                    unset($this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id]['promise']);
                    try {
                        $promise->resolve($response);
                    } catch (\Error $e) {
                        if (strpos($e->getMessage(), "Promise has already been resolved") !== 0) {
                            throw $e;
                        }
                        $this->logger->logger("Got promise already resolved error", \danog\MadelineProto\Logger::FATAL_ERROR);
                    }
                }
            }
        )());
    }

    public function handle_updates_async($updates, $actual_updates = null)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($actual_updates) {
            $updates = $actual_updates;
        }

        $this->logger->logger('Parsing updates ('.$updates['_'].') received via the socket...', \danog\MadelineProto\Logger::VERBOSE);
        switch ($updates['_']) {
            case 'updates':
            case 'updatesCombined':
                $result = [];
                foreach ($updates['updates'] as $key => $update) {
                    if ($update['_'] === 'updateNewMessage' || $update['_'] === 'updateReadMessagesContents' ||
                        $update['_'] === 'updateEditMessage' || $update['_'] === 'updateDeleteMessages' ||
                        $update['_'] === 'updateReadHistoryInbox' || $update['_'] === 'updateReadHistoryOutbox' ||
                        $update['_'] === 'updateWebPage' || $update['_'] === 'updateMessageID') {
                        $result[yield $this->feeders[false]->feedSingle($update)] = true;
                        unset($updates['updates'][$key]);
                    }
                }
                $this->seqUpdater->addPendingWakeups($result);
                if ($updates['updates']) {
                    if ($updates['_'] === 'updatesCombined') {
                        $updates['options'] = ['seq_start' => $updates['seq_start'], 'seq_end' => $updates['seq'], 'date' => $updates['date']];
                    } else {
                        $updates['options'] = ['seq_start' => $updates['seq'], 'seq_end' => $updates['seq'], 'date' => $updates['date']];
                    }
                    $this->seqUpdater->feed($updates);
                }
                $this->seqUpdater->resume();
                break;
            case 'updateShort':
                $this->feeders[yield $this->feeders[false]->feedSingle($updates['update'])]->resume();
                break;
            case 'updateShortSentMessage':
                if (!isset($updates['request']['body'])) {
                    break;
                }
                $updates['user_id'] = (yield $this->get_info_async($updates['request']['body']['peer']))['bot_api_id'];
                $updates['message'] = $updates['request']['body']['message'];
                unset($updates['request']);
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $from_id = isset($updates['from_id']) ? $updates['from_id'] : ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                if (!yield $this->peer_isset_async($from_id) || !yield $this->peer_isset_async($to_id) || isset($updates['via_bot_id']) && !yield $this->peer_isset_async($updates['via_bot_id']) || isset($updates['entities']) && !yield $this->entities_peer_isset_async($updates['entities']) || isset($updates['fwd_from']) && !yield $this->fwd_peer_isset_async($updates['fwd_from'])) {
                    yield $this->updaters[false]->resume();

                    return;
                }
                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $from_id;

                try {
                    $message['to_id'] = (yield $this->get_info_async($to_id))['Peer'];
                } catch (\danog\MadelineProto\Exception $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    //$this->pending_updates[] = $updates;
                    break;
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    //$this->pending_updates[] = $updates;
                    break;
                }
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->feeders[yield $this->feeders[false]->feedSingle($update)]->resume();
                break;
            case 'updatesTooLong':
                $this->updaters[false]->resume();
                break;
            default:
                throw new \danog\MadelineProto\ResponseException('Unrecognized update received: '.var_export($updates, true));
                break;
        }
    }
}
