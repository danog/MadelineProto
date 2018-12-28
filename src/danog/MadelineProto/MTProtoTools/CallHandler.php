<?php

/**
 * CallHandler module.
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

use Amp\Deferred;
use Amp\Promise;
use danog\MadelineProto\Async\Parameters;
use function Amp\call;
use function Amp\Promise\all;

/**
 * Manages method and object calls.
 */
trait CallHandler
{
    /*
    public function select()
    {
        $result = [];

        try {
            /*
            if ($this->is_http($this->settings['connection_settings']['default_dc']) || $this->altervista) {
            $this->logger->logger("Initial HTTP short poll");
            $waiting = $this->datacenter->select(0.1);
            $result = $this->handle_select($waiting, $result);
            }/
            $tries = 10; // TODO add setting
            $this->logger->logger('Long poll');
            $t = microtime(true);
            $waiting = $this->datacenter->select();
            $t = microtime(true) - $t;
            $this->logger->logger("Long poll took $t");

            $result = $this->handle_select($waiting, $result);

            do {
                $this->logger->logger('Short poll');
                $waiting = $this->datacenter->select($this->is_http($this->settings['connection_settings']['default_dc']) || $this->altervista ? $this->settings['connection_settings']['all']['timeout'] / 10 : true);
                $result = $this->handle_select($waiting, $result);
            } while ($tries-- && $waiting);
        } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
            $this->logger->logger('Nothing in the socket while selecting', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            foreach ($this->datacenter->sockets as $dc => $socket) {
                $this->close_and_reopen($dc);
                $this->send_messages($dc);
            }
        }

        return $result;
    }

    public $did = [];

    public function handle_select($waiting, $result)
    {
        foreach ($waiting as $dc) {
            $error = $this->recv_message($dc);
            if ($error !== true) {
                $this->close_and_reopen($dc);
                if ($error === -404) {
                    if ($this->datacenter->sockets[$dc]->temp_auth_key !== null) {
                        $this->logger->logger('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                        $this->datacenter->sockets[$dc]->temp_auth_key = null;
                        $this->init_authorization();

                        return $result;
                    }
                }

                throw new \danog\MadelineProto\RPCErrorException($error, $error);
            }
            $result[$dc] = $this->handle_messages($dc) && (isset($result[$dc]) ? $result[$dc] : true);
            if (($this->is_http($dc) || $this->altervista) && $this->datacenter->sockets[$dc]->new_outgoing) {
                $this->send_messages($dc);
            }
        }

        return $result;
    }
    public function iorun($updates)
    {
        do {
            if ($updates && time() - $this->last_getdifference > $this->settings['updates']['getdifference_interval']) {
                $this->get_updates_difference();

                return;
            }

            if ($canunset = !$this->updates_state['sync_loading']) {
                $this->updates_state['sync_loading'] = true;
            }
            if ($canunsetpostponeupdates = !$this->postpone_updates) {
                $this->postpone_updates = true;
            }
            if ($canunsetpostponepwrchat = !$this->postpone_pwrchat) {
                $this->postpone_pwrchat = true;
            }

            if (($this->is_http($this->settings['connection_settings']['default_dc']) || $this->altervista) && $updates) {
                $this->send_messages($this->settings['connection_settings']['default_dc']);
            }
            foreach ($this->datacenter->sockets as $id => $datacenter) {
                if ($datacenter->pending_outgoing) {
                    $this->send_messages($id);
                }
            }

            $this->logger->logger('Polling for ' . ($updates ? 'updates' : 'replies') . ': selecting', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            $t = microtime(true);
            $only_updates = $this->select();
            $t = microtime(true) - $t;
            $this->logger->logger('Polling for ' . ($updates ? 'updates' : 'replies') . ': selecting took ' . $t, \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            $response_result = $this->has_pending_calls();

            $repeat = 0;
            foreach ($this->datacenter->sockets as $id => $datacenter) {
                if ($updates) {
                    if (isset($only_updates[$id])) {
                        if ($only_updates[$id]) {
                            $this->logger->logger("Polling for updates: got only updates for DC $id", \danog\MadelineProto\Logger::VERBOSE);
                        } else {
                            $this->logger->logger("Polling for updates: got also RPC replies for DC $id", \danog\MadelineProto\Logger::NOTICE);
                        }
                        if ($response_result[$id]) {
                            $this->logger->logger("Polling for updates: still pending requests, resending for DC $id", \danog\MadelineProto\Logger::WARNING);
                            $this->send_messages($id);
                        }
                    } else {
                        if ($response_result[$id] || $id === $this->settings['connection_settings']['default_dc']) {
                            $this->logger->logger("Polling for updates: got nothing for DC $id", \danog\MadelineProto\Logger::ERROR);

                            if ($this->is_http($id) || $this->altervista) {
                                $this->logger->logger("Polling for updates: closing and reopening DC $id since we're on HTTP, and we polled");
                                $this->close_and_reopen($id);
                                $datacenter->last_http_wait = 0;
                                $repeat |= 1;
                                $this->logger->logger("Polling for updates: and now repolling for DC $id");
                                $this->send_messages($id);
                            }
                        }
                    }
                } else {
                    if (isset($only_updates[$id])) {
                        if ($only_updates[$id]) {
                            $this->logger->logger("Polling for replies: got only updates for DC $id", \danog\MadelineProto\Logger::WARNING);

                            if ($response_result[$id]) {
                                $this->logger->logger("Polling for replies: still pending requests, repolling for DC $id", \danog\MadelineProto\Logger::WARNING);
                                $this->send_messages($id);
                                $repeat |= 1;
                            } else {
                                $this->logger->logger("Polling for replies: got all RPC replies for DC $id", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                            }
                        } else {
                            if ($response_result[$id]) {
                                $this->logger->logger("Polling for replies: still pending requests, repolling for DC $id", \danog\MadelineProto\Logger::WARNING);
                                $this->send_messages($id);
                            } else {
                                $this->logger->logger("Polling for replies: got all RPC replies for DC $id", \danog\MadelineProto\Logger::NOTICE);
                            }
                        }
                    } else {
                        if ($response_result[$id]) {
                            $this->logger->logger("Polling for replies: got nothing for DC $id", \danog\MadelineProto\Logger::ERROR);
                            $this->logger->logger("Polling for replies: closing and reopening DC $id", \danog\MadelineProto\Logger::ERROR);
                            $this->close_and_reopen($id);
                            $datacenter->last_http_wait = 0;
                            $repeat |= 1;
                            $this->logger->logger("Polling for replies: resending for DC $id", \danog\MadelineProto\Logger::WARNING);
                            $this->send_messages($id);
                        } else {
                            $this->logger->logger("Polling for replies: got all RPC replies for DC $id", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        }
                    }
                }
            }
            $this->logger->logger('Running guzzle promise queue');
            \GuzzleHttp\Promise\queue()->run();

            if ($repeat) {
                $this->logger->logger('Repeat iowait');
            }
        } while ($repeat);

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

    */

    public function has_pending_calls()
    {
        $result = [];
        foreach ($this->datacenter->sockets as $id => $socket) {
            $result[$id] = $this->has_pending_calls_dc($id);
        }

        return $result;
    }

    public function has_pending_calls_dc($datacenter)
    {
        //$result = 0;
        $dc_config_number = isset($this->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';
        foreach ($this->datacenter->sockets[$datacenter]->new_outgoing as $message_id) {
            if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['sent']) && ($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['sent'] + $this->settings['connection_settings'][$dc_config_number]['timeout'] < time()) && ($this->datacenter->sockets[$datacenter]->temp_auth_key === null) === (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['unencrypted']) && $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['unencrypted']) && $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'] !== 'msgs_state_req') {
                return true;
                //$result |= 1;
            }
        }

        return false; //(bool) $result;
    }

    public function check_pending_calls()
    {
        foreach ($this->datacenter->sockets as $datacenter => $socket) {
            $this->check_pending_calls_dc($datacenter);
        }
    }

    public function check_pending_calls_dc($datacenter)
    {
        if (!empty($this->datacenter->sockets[$datacenter]->new_outgoing)) {
            if ($this->has_pending_calls_dc($datacenter)) {
                if ($this->datacenter->sockets[$datacenter]->temp_auth_key !== null) {
                    $message_ids = array_values($this->datacenter->sockets[$datacenter]->new_outgoing);
                    $deferred = new \danog\MadelineProto\ImmediatePromise();
                    $deferred->then(
                        function ($result) use ($datacenter, $message_ids) {
                            $reply = [];
                            foreach (str_split($result['info']) as $key => $chr) {
                                $message_id = $message_ids[$key];
                                if (!isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id])) {
                                    $this->logger->logger('Already got response for and forgot about message ID '.$this->unpack_signed_long($message_id));
                                    continue;
                                }
                                if (!isset($this->datacenter->sockets[$datacenter]->new_outgoing[$message_id])) {
                                    $this->logger->logger('Already got response for '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'].' with message ID '.$this->unpack_signed_long($message_id));
                                    continue;
                                }
                                $chr = ord($chr);
                                switch ($chr & 7) {
                                    case 0:
                                        $this->logger->logger('Wrong message status 0 for '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'], \danog\MadelineProto\Logger::FATAL_ERROR);
                                        break;
                                    case 1:
                                    case 2:
                                    case 3:
                                        $this->logger->logger('Message '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'].' with message ID '.$this->unpack_signed_long($message_id).' not received by server, resending...', \danog\MadelineProto\Logger::ERROR);
                                        $this->method_recall($message_id, $datacenter, false, true);
                                        break;
                                    case 4:
                                        if ($chr & 32) {
                                            $this->logger->logger('Message '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'].' with message ID '.$this->unpack_signed_long($message_id).' received by server and is being processed, waiting...', \danog\MadelineProto\Logger::ERROR);
                                        } elseif ($chr & 64) {
                                            $this->logger->logger('Message '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'].' with message ID '.$this->unpack_signed_long($message_id).' received by server and was already processed, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                            $reply[] = $message_id;
                                        } elseif ($chr & 128) {
                                            $this->logger->logger('Message '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'].' with message ID '.$this->unpack_signed_long($message_id).' received by server and was already sent, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                            $reply[] = $message_id;
                                        } else {
                                            $this->logger->logger('Message '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'].' with message ID '.$this->unpack_signed_long($message_id).' received by server, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                            $reply[] = $message_id;
                                        }
                                }
                            }
                            if ($reply) {
                                $this->object_call('msg_resend_ans_req', ['msg_ids' => $reply], ['datacenter' => $datacenter, 'postpone' => true]);
                            }
                            $this->send_messages($datacenter);
                        },
                        function ($error) use ($datacenter) {
                            throw $error;
                        }
                    );
                    $this->logger->logger("Still missing something on DC $datacenter, sending state request", \danog\MadelineProto\Logger::ERROR);
                    $this->object_call('msgs_state_req', ['msg_ids' => $message_ids], ['datacenter' => $datacenter, 'promise' => $deferred]);
                } else {
                    $dc_config_number = isset($this->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';
                    foreach ($this->datacenter->sockets[$datacenter]->new_outgoing as $message_id) {
                        if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['sent']) && $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['sent'] + $this->settings['connection_settings'][$dc_config_number]['timeout'] < time() && $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['unencrypted']) {
                            $this->logger->logger('Still missing '.$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_'].' with message id '.$this->unpack_signed_long($message_id)." on DC $datacenter, resending", \danog\MadelineProto\Logger::ERROR);
                            $this->method_recall($message_id, $datacenter, false, true);
                        }
                    }
                }
            }
        }
    }

    public function method_recall($watcherId, $args)
    {
        $message_id = $args['message_id'];
        $new_datacenter = $args['datacenter'];
        $old_datacenter = $new_datacenter;
        if (isset($args['old_datacenter'])) {
            $old_datacenter = $args['old_datacenter'];
        }
        $postpone = false;
        if (isset($args['postpone'])) {
            $postpone = $args['postpone'];
        }

        if (isset($this->datacenter->sockets[$old_datacenter]->outgoing_messages[$message_id]['container'])) {
            $message_ids = $this->datacenter->sockets[$old_datacenter]->outgoing_messages[$message_id]['container'];
        } else {
            $message_ids = [$message_id];
        }

        foreach ($message_ids as $message_id) {
            if (isset($this->datacenter->sockets[$old_datacenter]->outgoing_messages[$message_id]['body'])) {
                $this->datacenter->sockets[$new_datacenter]->sendMessage($this->datacenter->sockets[$old_datacenter]->outgoing_messages[$message_id], false);
                $this->ack_outgoing_message_id($message_id, $old_datacenter);
                $this->got_response_for_outgoing_message_id($message_id, $old_datacenter);
            }
        }
        if (!$postpone) {
            $this->datacenter->sockets[$new_datacenter]->writer->resume();
        }
    }

    public function method_call($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
    {
        $promise = $this->method_call_async_read($method, $args, $aargs);

        return $this->wait($promise);
    }

    public function method_call_async_read($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false]): Promise
    {
        $deferred = new Deferred();
        $this->method_call_async_write($method, $args, $aargs)->onResolve(function ($e, $read_deferred) use ($deferred) {
            if ($e) {
                $deferred->fail($e);
            } else {
                if (is_array($read_deferred)) {
                    $read_deferred = array_map(function ($value) {
                        return $value->promise();
                    }, $read_deferred);
                    $deferred->resolve(all($read_deferred));
                } else {
                    $deferred->resolve($read_deferred->promise());
                }
            }
        });

        return isset($aargs['noResponse']) && $aargs['noResponse'] ? new \Amp\Success(0) : $deferred->promise();
    }

    public function method_call_async_write($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false]): Promise
    {
        return call([$this, 'method_call_async_write_generator'], $method, $args, $aargs);
    }

    public function method_call_async_write_generator($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false]): \Generator
    {
        if (is_array($args) && isset($args['id']['_']) && isset($args['id']['dc_id']) && $args['id']['_'] === 'inputBotInlineMessageID') {
            $aargs['datacenter'] = $args['id']['dc_id'];
        }
        if ($this->wrapper instanceof \danog\MadelineProto\API && isset($this->wrapper->session) && !is_null($this->wrapper->session) && time() - $this->wrapper->serialized > $this->settings['serialization']['serialization_interval']) {
            $this->logger->logger("Didn't serialize in a while, doing that now...");
            $this->wrapper->serialize($this->wrapper->session);
        }
        if (isset($aargs['file']) && $aargs['file'] && isset($this->datacenter->sockets[$aargs['datacenter'].'_media'])) {
            \danog\MadelineProto\Logger::log('Using media DC');
            $aargs['datacenter'] .= '_media';
        }
        if (in_array($method, ['messages.setEncryptedTyping', 'messages.readEncryptedHistory', 'messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService', 'messages.receivedQueue'])) {
            $aargs['queue'] = 'secret';
        }

        if (is_array($args)) {
            if (isset($args['message']) && is_string($args['message']) && $this->mb_strlen($args['message']) > $this->config['message_length_max']) {
                $arg_chunks = $this->split_to_chunks($args);
                $promises = [];
                $new_aargs = $aargs;
                $new_aargs['postpone'] = true;
                $new_aargs['queue'] = $method;

                foreach ($arg_chunks as $args) {
                    $promises[] = $this->method_call_async_write($method, $args, $new_aargs);
                }

                if (!isset($aargs['postpone'])) {
                    $this->datacenter->sockets[$aargs['datacenter']]->writer->resume();
                }

                return yield $promises;
            }
            $args = $this->botAPI_to_MTProto($args);
            if (isset($args['ping_id']) && is_int($args['ping_id'])) {
                $args['ping_id'] = $this->pack_signed_long($args['ping_id']);
            }
        }

        $deferred = new Deferred();
        $message = ['_' => $method, 'type' => $this->methods->find_by_method($method)['type'], 'content_related' => $this->content_related($method), 'promise' => $deferred, 'method' => true, 'unencrypted' => $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key === null && strpos($method, '.') === false];

        if (is_object($args) && $args instanceof Parameters) {
            $message['body'] = call([$args, 'fetchParameters']);
        } else {
            $message['body'] = $args;
        }

        if (isset($aargs['msg_id'])) {
            $message['msg_id'] = $aargs['msg_id'];
        }
        if (isset($aargs['queue'])) {
            $message['queue'] = $aargs['queue'];
        }
        if (isset($aargs['file'])) {
            $message['file'] = $aargs['file'];
        }
        if (isset($aargs['botAPI'])) {
            $message['botAPI'] = $aargs['botAPI'];
        }
        if (($method === 'users.getUsers' && $args === ['id' => [['_' => 'inputUserSelf']]]) || $method === 'auth.exportAuthorization' || $method === 'updates.getDifference') {
            $message['user_related'] = true;
        }

        $deferred = yield $this->datacenter->sockets[$aargs['datacenter']]->sendMessage($message, isset($aargs['postpone']) ? !$aargs['postpone'] : true);

        $this->datacenter->sockets[$aargs['datacenter']]->checker->resume();

        return $deferred;
    }

    public function object_call($object, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
    {
        return $this->wait($this->object_call_async($object, $args, $aargs));
    }

    public function object_call_async($object, $args = [], $aargs = ['msg_id' => null, 'heavy' => false]): Promise
    {
        $message = ['_' => $object, 'body' => $args, 'content_related' => $this->content_related($object), 'unencrypted' => $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key === null, 'method' => false];
        if (isset($aargs['promise'])) {
            $message['promise'] = $aargs['promise'];
        }

        return $this->datacenter->sockets[$aargs['datacenter']]->sendMessage($message, isset($aargs['postpone']) ? !$aargs['postpone'] : true);
    }

    /*
$message = [
// only in outgoing messages
'body' => 'serialized body', (optional if container)
'content_related' => bool,
'_' => 'predicate',
'promise' => deferred promise that gets resolved when a response to the message is received (optional),
'send_promise' => deferred promise that gets resolved when the message is sent (optional),
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
}
