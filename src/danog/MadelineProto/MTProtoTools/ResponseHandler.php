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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
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
    public function send_msgs_state_info($req_msg_id, $msg_ids, $datacenter)
    {
        // TODO REWRITE
        $info = '';
        foreach ($msg_ids as $msg_id) {
            $cur_info = 0;
            if (!isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id])) {
                $msg_id = new \phpseclib\Math\BigInteger(strrev($msg_id), 256);
                if ((new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$datacenter]->time_delta + 30))->bitwise_leftShift(32)->compare($msg_id) < 0) {
                    $cur_info |= 3;
                } elseif ((new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$datacenter]->time_delta - 300))->bitwise_leftShift(32)->compare($msg_id) > 0) {
                    $cur_info |= 1;
                } else {
                    $cur_info |= 2;
                }
            } else {
                $cur_info |= 4;
                if ($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['ack']) {
                    $cur_info |= 8;
                }
            }
            $info .= chr($cur_info);
        }
        $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->object_call('msgs_state_info', ['req_msg_id' => $req_msg_id, 'info' => $info], ['datacenter' => $datacenter])]['response'] = $req_msg_id;
    }

    public $n = 0;

    public function handle_messages($datacenter, $actual_datacenter = null)
    {
        if ($actual_datacenter) {
            $datacenter = $actual_datacenter;
        }

        //$n = $this->n++;
        $only_updates = true;
        foreach ($this->datacenter->sockets[$datacenter]->new_incoming as $current_msg_id) {
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
                    break;
                case 'rpc_result':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that the server received my request
                    $req_msg_id = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'];
                    $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['result'];
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);

                    $this->handle_response($req_msg_id, $current_msg_id, $datacenter);

                    $only_updates = false;
                    break;
                case 'future_salts':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    $this->handle_response($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'], $current_msg_id, $datacenter);
                    break;
                case 'bad_server_salt':
                case 'bad_msg_notification':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->handle_response($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['bad_msg_id'], $current_msg_id, $datacenter);
                    break;
                case 'pong':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->handle_response($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id'], $current_msg_id, $datacenter);
                    break;
                case 'new_session_created':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['server_salt'];
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);

                    // Acknowledge that I received the server's response
                    if ($this->authorized === self::LOGGED_IN && !$this->initing_authorization && $this->datacenter->sockets[$this->datacenter->curdc]->temp_auth_key !== null) {
                        Loop::defer([$this, 'get_updates_difference']);
                    }
                    //foreach ($this->datacenter->sockets[$datacenter]->new_outgoing as $message_id) {
                    //    $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['sent'] = 0;
                    //}
//                    Loop::defer([$this->datacenter->sockets[$datacenter]->checker, 'resume']);

                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'msg_container':
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['messages'] as $message) {
                        $this->datacenter->sockets[$datacenter]->check_message_id($message['msg_id'], ['outgoing' => false, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['msg_id']] = ['seq_no' => $message['seqno'], 'content' => $message['body'], 'from_container' => true];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['msg_id']] = $message['msg_id'];
                        $this->handle_messages($datacenter);
                    }
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'msg_copy':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    $this->ack_incoming_message_id($current_msg_id, $datacenter);
                    // Acknowledge that I received the server's response
                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']['msg_id'], $datacenter);
                    // Acknowledge that I received the server's response
                    } else {
                        $this->datacenter->sockets[$datacenter]->check_message_id($message['orig_message']['msg_id'], ['outgoing' => false, 'container' => true]);
                        $this->datacenter->sockets[$datacenter]->incoming_messages[$message['orig_message']['msg_id']] = ['content' => $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['orig_message']];
                        $this->datacenter->sockets[$datacenter]->new_incoming[$message['orig_message']['msg_id']] = $message['orig_message']['msg_id'];
                        $this->handle_messages($datacenter);
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'http_wait':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    $this->logger->logger($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content'], \danog\MadelineProto\Logger::NOTICE);
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                    break;
                case 'msgs_state_info':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->handle_response($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['req_msg_id'], $current_msg_id, $datacenter);
                    break;
                case 'msgs_state_req':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    break;
                case 'msgs_all_info':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $key => $msg_id) {
                        $msg_id = new \phpseclib\Math\BigInteger(strrev($msg_id), 256);
                        $status = 'Status for message id '.$msg_id.': ';
                        if (($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['info'][$key] & 4) !== 0) {
                            $this->got_response_for_outgoing_message_id($msg_id, $datacenter);
                        }
                        foreach (self::MSGS_INFO_FLAGS as $flag => $description) {
                            if (($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['info'][$key] & $flag) !== 0) {
                                $status .= $description;
                            }
                        }
                        $this->logger->logger($status, \danog\MadelineProto\Logger::NOTICE);
                    }
                    break;
                case 'msg_detailed_info':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']])) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                            $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]['response'] = $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'];
                            unset($this->datacenter->sockets[$datacenter]->new_outgoing[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_id']]);
                        } else {
                            $this->object_call('msg_resend_req', ['msg_ids' => [$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']]], ['datacenter' => $datacenter]);
                        }
                    }
                case 'msg_new_detailed_info':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']])) {
                        $this->ack_incoming_message_id($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id'], $datacenter);
                    } else {
                        $this->object_call('msg_resend_req', ['msg_ids' => [$this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['answer_msg_id']]], ['datacenter' => $datacenter]);
                    }
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    break;
                case 'msg_resend_req':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    $ok = true;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        if (!isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$msg_id]) || isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id])) {
                            $ok = false;
                        }
                    }
                    if ($ok) {
                        foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                            $this->object_call($this->datacenter->sockets[$datacenter]->outgoing_messages[$msg_id]['content']['method'], $this->datacenter->sockets[$datacenter]->outgoing_messages[$msg_id]['content']['args'], ['datacenter' => $datacenter]);
                        }
                    } else {
                        $this->send_msgs_state_info($current_msg_id, $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    }
                    break;
                case 'msg_resend_ans_req':
                    $this->datacenter->sockets[$datacenter]->check_in_seq_no($current_msg_id);
                    $only_updates = false;
                    unset($this->datacenter->sockets[$datacenter]->new_incoming[$current_msg_id]);
                    $this->send_msgs_state_info($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'], $datacenter);
                    foreach ($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']['msg_ids'] as $msg_id) {
                        if (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]) && isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']])) {
                            $this->object_call($this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['method'], $this->datacenter->sockets[$datacenter]->outgoing_messages[$this->datacenter->sockets[$datacenter]->incoming_messages[$msg_id]['response']]['args'], ['datacenter' => $datacenter]);
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
                                Loop::defer([$this, 'handle_updates'], $this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);
                            }

                            unset($this->datacenter->sockets[$datacenter]->incoming_messages[$current_msg_id]['content']);

                            $only_updates = true && $only_updates;
                            break;
                        default:
                            $only_updates = false;
                            $this->logger->logger('Trying to assign a response of type '.$response_type.' to its request...', \danog\MadelineProto\Logger::VERBOSE);
                            foreach ($this->datacenter->sockets[$datacenter]->new_outgoing as $key => $expecting_msg_id) {
                                $expecting = $this->datacenter->sockets[$datacenter]->outgoing_messages[$expecting_msg_id];

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
        //$this->n--;

        return $only_updates;
    }

    public function handle_reject($datacenter, &$request, $data)
    {
        if (isset($request['promise']) && is_object($request['promise'])) {
            Loop::defer(function () use (&$request, $data) {
                $request['promise']->fail($data);
                unset($request['promise']);
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

                        Loop::defer([$this, 'method_recall'], ['message_id' => $request_id, 'datacenter' => $datacenter]);

                        return;
                    }
                    switch ($response['error_code']) {
                        case 500:
                            if ($response['error_message'] === 'MSG_WAIT_FAILED') {
                                $this->datacenter->sockets[$datacenter]->call_queue[$request['queue']] = [];
                            }

                            Loop::defer([$this, 'method_recall'], ['message_id' => $request_id, 'datacenter' => $datacenter]);

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
                                        $this->logger->logger('Send an email to recover@telegram.org, asking to unban the phone number '.$phone.', and quickly describe what will you do with this phone number.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger('Then login again.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                        $this->logger->logger('If you intentionally deleted this account, ignore this message.', \danog\MadelineProto\Logger::FATAL_ERROR);
                                    }

                                    $this->authorized = self::NOT_LOGGED_IN;
                                    $this->authorization = null;

                                    Loop::defer(function () use ($datacenter, &$request, &$response) {
                                        $this->init_authorization();

                                        $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']));
                                    });

                                    return;
                                case 'AUTH_KEY_UNREGISTERED':
                                case 'AUTH_KEY_INVALID':
                                    if ($this->authorized !== self::LOGGED_IN) {
                                        $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                                        Loop::defer(function () use ($datacenter, &$request, &$response) {
                                            $this->init_authorization();

                                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']));
                                        });

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

                                        $this->authorized = self::NOT_LOGGED_IN;
                                        $this->authorization = null;

                                        Loop::defer(function () use ($datacenter, &$request, &$response) {
                                            $this->init_authorization();

                                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']));
                                        });

                                        return;
                                    }
                                    Loop::defer(function () use ($request_id, $datacenter) {
                                        $this->init_authorization();

                                        $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter]);
                                    });

                                    return;
                                case 'AUTH_KEY_PERM_EMPTY':
                                    $this->logger->logger('Temporary auth key not bound, resetting temporary auth key...', \danog\MadelineProto\Logger::ERROR);

                                    $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                                    Loop::defer(function () use ($request_id, $datacenter) {
                                        $this->init_authorization();
                                        $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter]);
                                    });

                                    return;
                            }
                            $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']));

                            return;
                        case 420:
                            $seconds = preg_replace('/[^0-9]+/', '', $response['error_message']);
                            $limit = isset($aargs['FloodWaitLimit']) ? $aargs['FloodWaitLimit'] : $this->settings['flood_timeout']['wait_if_lt'];
                            if (is_numeric($seconds) && $seconds < $limit) {
                                $this->logger->logger('Flood, waiting '.$seconds.' seconds before repeating async call...', \danog\MadelineProto\Logger::NOTICE);
                                Loop::delay($seconds * 1000, [$this, 'method_recall'], ['message_id' => $request_id, 'datacenter' => $datacenter]);

                                return;
                            }
                        // no break
                        default:
                            $this->got_response_for_outgoing_message_id($request_id, $datacenter);

                            $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException($response['error_message'], $response['error_code']));

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
                            Loop::defer([$this, 'method_recall'], ['message_id' => $request_id, 'datacenter' => $datacenter]);

                            return;
                        case 16:
                        case 17:
                            $this->datacenter->sockets[$datacenter]->time_delta = (int) (new \phpseclib\Math\BigInteger(strrev($response_id), 256))->bitwise_rightShift(32)->subtract(new \phpseclib\Math\BigInteger(time()))->toString();
                            $this->logger->logger('Set time delta to '.$this->datacenter->sockets[$datacenter]->time_delta, \danog\MadelineProto\Logger::WARNING);
                            $this->reset_session();
                            $this->datacenter->sockets[$datacenter]->temp_auth_key = null;
                            Loop::defer(function () use ($request_id, $datacenter) {
                                $this->init_authorization();
                                $this->method_recall('', ['message_id' => $request_id, 'datacenter' => $datacenter]);
                            });

                            return;
                    }
                    $this->got_response_for_outgoing_message_id($request_id, $datacenter);
                    $this->handle_reject($datacenter, $request, new \danog\MadelineProto\RPCErrorException('Received bad_msg_notification: '.self::BAD_MSG_ERROR_CODES[$response['error_code']], $response['error_code']));

                    return;
            }
        }

        if (isset($request['method']) && $request['method'] && $request['_'] !== 'auth.bindTempAuthKey' && $this->datacenter->sockets[$datacenter]->temp_auth_key !== null && (!isset($this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) || $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] === false)) {
            $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] = true;
        }

        if (!isset($request['promise'])) {
            $this->logger->logger('Response: already got response for '.(isset($request['_']) ? $request['_'] : '-').' with message ID '.$this->unpack_signed_long($request_id));

            return;
        }

        if (isset($request['botAPI']) && $request['botAPI']) {
            $response = $this->MTProto_to_botAPI($response);
        }
        unset($request);
        $this->got_response_for_outgoing_message_id($request_id, $datacenter);
        Loop::defer(function () use ($request_id, $response, $datacenter) {
            $r = isset($response['_']) ? $response['_'] : json_encode($response);
            $this->logger->logger("Deferred: sent $r to deferred");
            $this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id]['promise']->resolve($response);
            unset($this->datacenter->sockets[$datacenter]->outgoing_messages[$request_id]['promise']);
        });
    }

    public function handle_pending_updates()
    {
        if ($this->postpone_updates) {
            return false;
        }
        if (count($this->pending_updates)) {
            $this->logger->logger('Parsing pending updates...');
            foreach (array_keys($this->pending_updates) as $key) {
                if (isset($this->pending_updates[$key])) {
                    $updates = $this->pending_updates[$key];
                    unset($this->pending_updates[$key]);
                    $this->handle_updates($updates);
                }
            }
        }
    }

    public function handle_updates($updates, $actual_updates = null)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($actual_updates) {
            $updates = $actual_updates;
        }

        if ($this->postpone_updates) {
            $this->logger->logger('Postpone update handling', \danog\MadelineProto\Logger::VERBOSE);
            $this->pending_updates[] = $updates;

            return false;
        }
        $this->handle_pending_updates();
        $this->logger->logger('Parsing updates received via the socket...', \danog\MadelineProto\Logger::VERBOSE);

        try {
            $this->postpone_updates = true;

            $opts = [];
            foreach (['date', 'seq', 'seq_start'] as $key) {
                if (isset($updates[$key])) {
                    $opts[$key] = $updates[$key];
                }
            }

            switch ($updates['_']) {
                case 'updates':
                case 'updatesCombined':
                    foreach ($updates['updates'] as $update) {
                        $this->handle_update($update, $opts);
                    }
                    break;
                case 'updateShort':
                    $this->handle_update($updates['update'], $opts);
                    break;
                case 'updateShortMessage':
                case 'updateShortChatMessage':
                    $from_id = isset($updates['from_id']) ? $updates['from_id'] : ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                    $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                    if (!$this->peer_isset($from_id) || !$this->peer_isset($to_id) || isset($updates['via_bot_id']) && !$this->peer_isset($updates['via_bot_id']) || isset($updates['entities']) && !$this->entities_peer_isset($updates['entities']) || isset($updates['fwd_from']) && !$this->fwd_peer_isset($updates['fwd_from'])) {
                        $this->logger->logger('getDifference: good - getting user for updateShortMessage', \danog\MadelineProto\Logger::VERBOSE);
                        $this->get_updates_difference();
                    }
                    $message = $updates;
                    $message['_'] = 'message';
                    $message['from_id'] = $from_id;

                    try {
                        $message['to_id'] = $this->get_info($to_id)['Peer'];
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
                    $this->handle_update($update, $opts);
                    break;
                case 'updateShortSentMessage':
                    //$this->set_update_state(['date' => $updates['date']]);
                    break;
                case 'updatesTooLong':
                    $this->get_updates_difference();
                    break;
                default:
                    throw new \danog\MadelineProto\ResponseException('Unrecognized update received: '.var_export($updates, true));
                    break;
            }
        } finally {
            $this->postpone_updates = false;
        }
        if ($this->updates && $this->update_deferred) {
            $d = $this->update_deferred;
            $this->update_deferred = null;

            $d->resolve();
        }
    }
}
