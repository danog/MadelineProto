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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Deferred;
use Amp\Promise;
use danog\MadelineProto\Async\Parameters;
use function Amp\Promise\all;

/**
 * Manages method and object calls.
 */
trait CallHandler
{
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
                $this->callFork($this->datacenter->sockets[$new_datacenter]->sendMessage($this->datacenter->sockets[$old_datacenter]->outgoing_messages[$message_id], false));
                $this->ack_outgoing_message_id($message_id, $old_datacenter);
                $this->got_response_for_outgoing_message_id($message_id, $old_datacenter);
            } else {
                $this->logger->logger('Could not resend '.isset($this->datacenter->sockets[$old_datacenter]->outgoing_messages[$message_id]['_']) ? $this->datacenter->sockets[$old_datacenter]->outgoing_messages[$message_id]['_'] : $message_id);
            }
        }
        if (!$postpone) {
            $this->datacenter->sockets[$new_datacenter]->writer->resume();
        }
    }

    public function method_call($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
    {
        return $this->wait($this->method_call_async_read($method, $args, $aargs));
    }

    public function method_call_async_read($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
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

        return isset($aargs['noResponse']) && $aargs['noResponse'] ? 0 : $deferred->promise();
    }

    public function method_call_async_write($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false]): Promise
    {
        return $this->call($this->method_call_async_write_generator($method, $args, $aargs));
    }

    public function method_call_async_write_generator($method, $args = [], $aargs = ['msg_id' => null, 'heavy' => false]): \Generator
    {
        if (is_array($args) && isset($args['id']['_']) && isset($args['id']['dc_id']) && $args['id']['_'] === 'inputBotInlineMessageID') {
            $aargs['datacenter'] = $args['id']['dc_id'];
        }
        if ($this->wrapper instanceof \danog\MadelineProto\API && isset($this->wrapper->session) && !is_null($this->wrapper->session) && time() - $this->wrapper->serialized > $this->settings['serialization']['serialization_interval'] && !$this->asyncInitPromise) {
            $this->logger->logger("Didn't serialize in a while, doing that now...");
            $this->wrapper->serialize($this->wrapper->session);
        }
        if (isset($aargs['file']) && $aargs['file'] && isset($this->datacenter->sockets[$aargs['datacenter'].'_media'])) {
            $this->logger->logger('Using media DC');
            $aargs['datacenter'] .= '_media';
        }
        if (in_array($method, ['messages.setEncryptedTyping', 'messages.readEncryptedHistory', 'messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService', 'messages.receivedQueue'])) {
            $aargs['queue'] = 'secret';
        }

        if (is_array($args)) {
            if (isset($args['multiple'])) {
                $aargs['multiple'] = true;
            }
            if (isset($args['message']) && is_string($args['message']) && mb_strlen($args['message'], 'UTF-8') > $this->config['message_length_max'] && mb_strlen((yield $this->parse_mode_async($args))['message'], 'UTF-8') > $this->config['message_length_max']) {
                $args = yield $this->split_to_chunks_async($args);
                $promises = [];
                $aargs['queue'] = $method;
                $aargs['multiple'] = true;
            }
            if (isset($aargs['multiple'])) {
                $new_aargs = $aargs;
                $new_aargs['postpone'] = true;
                unset($new_aargs['multiple']);

                if (isset($args['multiple'])) {
                    unset($args['multiple']);
                }
                foreach ($args as $single_args) {
                    $promises[] = $this->method_call_async_write($method, $single_args, $new_aargs);
                }

                if (!isset($aargs['postpone'])) {
                    $this->datacenter->sockets[$aargs['datacenter']]->writer->resume();
                }

                return yield all($promises);
            }
            $args = yield $this->botAPI_to_MTProto_async($args);
            if (isset($args['ping_id']) && is_int($args['ping_id'])) {
                $args['ping_id'] = $this->pack_signed_long($args['ping_id']);
            }
        }

        $deferred = new Deferred();
        $message = ['_' => $method, 'type' => $this->methods->find_by_method($method)['type'], 'content_related' => $this->content_related($method), 'promise' => $deferred, 'method' => true, 'unencrypted' => $this->datacenter->sockets[$aargs['datacenter']]->temp_auth_key === null && strpos($method, '.') === false];

        if (is_object($args) && $args instanceof Parameters) {
            $message['body'] = yield $args->fetchParameters();
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
        if (isset($aargs['FloodWaitLimit'])) {
            $message['FloodWaitLimit'] = $aargs['FloodWaitLimit'];
        }
        if (($method === 'users.getUsers' && $args === ['id' => [['_' => 'inputUserSelf']]]) || $method === 'auth.exportAuthorization' || $method === 'updates.getDifference') {
            $message['user_related'] = true;
        }

        $deferred = yield $this->datacenter->sockets[$aargs['datacenter']]->sendMessage($message, isset($aargs['postpone']) ? !$aargs['postpone'] : true);

        $this->datacenter->sockets[$aargs['datacenter']]->checker->resume();

        return $deferred;
    }

    public function object_call_async($object, $args = [], $aargs = ['msg_id' => null, 'heavy' => false])
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
'body' => deserialized body, (optional if container)
'serialized_body' => 'serialized body', (optional if container)
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
