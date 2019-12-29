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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use Amp\Deferred;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Async\AsyncParameters;
use danog\MadelineProto\Tools;

use function Amp\Promise\all;

/**
 * Manages method and object calls.
 */
trait CallHandler
{
    /**
     * Recall method.
     *
     * @param string $watcherId Watcher ID for defer
     * @param array  $args      Args
     *
     * @return void
     */
    public function methodRecall(string $watcherId, array $args)
    {
        $message_id = $args['message_id'];
        $postpone = $args['postpone'] ?? false;
        $datacenter = $args['datacenter'] ?? false;
        if ($datacenter === $this->datacenter) {
            $datacenter = false;
        }

        $message_ids = $this->outgoing_messages[$message_id]['container'] ?? [$message_id];

        foreach ($message_ids as $message_id) {
            if (isset($this->outgoing_messages[$message_id]['body'])) {
                if ($datacenter) {
                    $res = $this->API->datacenter->waitGetConnection($datacenter)->onResolve(
                        function ($e, $r) use ($message_id) {
                            return $r->sendMessage($this->outgoing_messages[$message_id], false);
                        }
                    );
                } else {
                    $res = $this->sendMessage($this->outgoing_messages[$message_id], false);
                }
                \danog\MadelineProto\Tools::callFork($res);
                $this->ackOutgoingMessageId($message_id);
                $this->gotResponseForOutgoingMessageId($message_id);
            } else {
                $this->logger->logger('Could not resend '.(isset($this->outgoing_messages[$message_id]['_']) ? $this->outgoing_messages[$message_id]['_'] : $message_id));
            }
        }
        if (!$postpone) {
            if ($datacenter) {
                $this->API->datacenter->getDataCenterConnection($datacenter)->flush();
            } else {
                $this->flush();
            }
        }
    }

    /**
     * Call method and wait asynchronously for response.
     *
     * If the $aargs['noResponse'] is true, will not wait for a response.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     * @param array  $aargs  Additional arguments
     *
     * @return Promise
     */
    public function methodCallAsyncRead(string $method, $args = [], array $aargs = ['msg_id' => null]): Promise
    {
        $deferred = new Deferred();
        $this->methodCallAsyncWrite($method, $args, $aargs)->onResolve(function ($e, $read_deferred) use ($deferred) {
            if ($e) {
                $deferred->fail($e);
            } else {
                if (\is_array($read_deferred)) {
                    $read_deferred = \array_map(
                        function ($value) {
                            return $value->promise();
                        },
                        $read_deferred
                    );
                    $deferred->resolve(all($read_deferred));
                } else {
                    $deferred->resolve($read_deferred->promise());
                }
            }
        });

        return ($aargs['noResponse'] ?? false) ? new Success() : $deferred->promise();
    }

    /**
     * Call method and make sure it is asynchronously sent.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     * @param array  $aargs  Additional arguments
     *
     * @return Promise
     */
    public function methodCallAsyncWrite(string $method, $args = [], array $aargs = ['msg_id' => null]): Promise
    {
        return \danog\MadelineProto\Tools::call($this->methodCallAsyncWriteGenerator($method, $args, $aargs));
    }

    /**
     * Call method and make sure it is asynchronously sent (generator).
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     * @param array  $aargs  Additional arguments
     *
     * @return Generator
     */
    public function methodCallAsyncWriteGenerator(string $method, $args = [], array $aargs = ['msg_id' => null]): \Generator
    {
        if (\is_array($args)
            && isset($args['id']['_'])
            && isset($args['id']['dc_id'])
            && $args['id']['_'] === 'inputBotInlineMessageID'
            && $this->datacenter !== $args['id']['dc_id']
        ) {
            $aargs['datacenter'] = $args['id']['dc_id'];
            return $this->API->methodCallAsyncWriteGenerator($method, $args, $aargs);
        }
        if (($aargs['file'] ?? false) && !$this->isMedia() && $this->API->datacenter->has($this->datacenter.'_media')) {
            $this->logger->logger('Using media DC');
            $aargs['datacenter'] = $this->datacenter.'_media';
            return $this->API->methodCallAsyncWriteGenerator($method, $args, $aargs);
        }
        if (\in_array($method, ['messages.setEncryptedTyping', 'messages.readEncryptedHistory', 'messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService', 'messages.receivedQueue'])) {
            $aargs['queue'] = 'secret';
        }

        if (\is_array($args)) {
            if (isset($args['multiple'])) {
                $aargs['multiple'] = true;
            }
            if (isset($args['message']) && \is_string($args['message']) && \mb_strlen($args['message'], 'UTF-8') > (yield $this->API->getConfig())['message_length_max'] && \mb_strlen((yield $this->API->parseMode($args))['message'], 'UTF-8') > (yield $this->API->getConfig())['message_length_max']) {
                $args = yield $this->API->splitToChunks($args);
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
                    $promises[] = $this->methodCallAsyncWrite($method, $single_args, $new_aargs);
                }

                if (!isset($aargs['postpone'])) {
                    $this->writer->resume();
                }

                return yield all($promises);
            }
            $args = yield $this->API->botAPIToMTProto($args);
            if (isset($args['ping_id']) && \is_int($args['ping_id'])) {
                $args['ping_id'] = Tools::packSignedLong($args['ping_id']);
            }
        }

        $deferred = new Deferred();
        $message = \array_merge(
            $aargs,
            [
                '_' => $method,
                'type' => $this->API->getTL()->getMethods()->findByMethod($method)['type'],
                'contentRelated' => $this->contentRelated($method),
                'promise' => $deferred,
                'method' => true,
                'unencrypted' => !$this->shared->hasTempAuthKey() && \strpos($method, '.') === false
            ]
        );

        if (\is_object($args) && $args instanceof AsyncParameters) {
            $message['body'] = yield $args->fetchParameters();
        } else {
            $message['body'] = $args;
        }

        if (($method === 'users.getUsers' && $args === ['id' => [['_' => 'inputUserSelf']]]) || $method === 'auth.exportAuthorization' || $method === 'updates.getDifference') {
            $message['user_related'] = true;
        }
        $aargs['postpone'] = $aargs['postpone'] ?? false;
        $deferred = yield $this->sendMessage($message, !$aargs['postpone']);

        $this->checker->resume();

        return $deferred;
    }

    /**
     * Send object and make sure it is asynchronously sent (generator).
     *
     * @param string $object Object name
     * @param array  $args   Arguments
     * @param array  $aargs  Additional arguments
     *
     * @return Promise
     */
    public function objectCall(string $object, $args = [], array $aargs = ['msg_id' => null]): \Generator
    {
        $message = ['_' => $object, 'body' => $args, 'contentRelated' => $this->contentRelated($object), 'unencrypted' => !$this->shared->hasTempAuthKey(), 'method' => false];
        if (isset($aargs['promise'])) {
            $message['promise'] = $aargs['promise'];
        }

        $aargs['postpone'] = $aargs['postpone'] ?? false;
        return $this->sendMessage($message, !$aargs['postpone']);
    }
}
