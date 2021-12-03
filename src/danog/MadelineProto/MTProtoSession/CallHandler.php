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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use Amp\Deferred;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\OutgoingMessage;
use danog\MadelineProto\TL\Exception;
use danog\MadelineProto\Tools;

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
    public function methodRecall(string $watcherId, array $args): void
    {
        $message_id = $args['message_id'];
        $postpone = $args['postpone'] ?? false;
        $datacenter = $args['datacenter'] ?? false;
        if ($datacenter === $this->datacenter) {
            $datacenter = false;
        }
        $message_ids = ($this->outgoing_messages[$message_id] ?? null) instanceof Container
            ? $this->outgoing_messages[$message_id]->getIds()
            : [$message_id];
        foreach ($message_ids as $message_id) {
            if (isset($this->outgoing_messages[$message_id])
                && !$this->outgoing_messages[$message_id]->canGarbageCollect()) {
                if ($datacenter) {
                    /** @var OutgoingMessage */
                    $message = $this->outgoing_messages[$message_id];
                    $this->gotResponseForOutgoingMessage($message);
                    $message->setMsgId(null);
                    $message->setSeqNo(null);
                    Tools::call($this->API->datacenter->waitGetConnection($datacenter))->onResolve(function ($e, $r) use ($message) {
                        Tools::callFork($r->sendMessage($message, false));
                    });
                } else {
                    /** @var OutgoingMessage */
                    $message = $this->outgoing_messages[$message_id];
                    if (!$message->hasSeqNo()) {
                        $this->gotResponseForOutgoingMessage($message);
                    }
                    Tools::callFork($this->sendMessage($message, false));
                }
            } else {
                $this->logger->logger('Could not resend '.($this->outgoing_messages[$message_id] ?? $message_id));
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
     * @param string            $method Method name
     * @param array|\Generator  $args   Arguments
     * @param array             $aargs  Additional arguments
     *
     * @psalm-param array|\Generator<mixed, mixed, mixed, array> $args
     *
     * @return \Generator
     */
    public function methodCallAsyncRead(string $method, $args = [], array $aargs = ['msg_id' => null]): \Generator
    {
        $readDeferred = yield from $this->methodCallAsyncWrite($method, $args, $aargs);
        if (\is_array($readDeferred)) {
            $readDeferred = Tools::all(\array_map(fn (Deferred $value) => $value->promise(), $readDeferred));
        } else {
            $readDeferred = $readDeferred->promise();
        }
        if (!($aargs['noResponse'] ?? false)) {
            return yield $readDeferred;
        }
    }
    /**
     * Call method and make sure it is asynchronously sent (generator).
     *
     * @param string            $method Method name
     * @param array|\Generator  $args   Arguments
     * @param array             $aargs  Additional arguments
     *
     * @psalm-param array|\Generator<mixed, mixed, mixed, array> $args
     *
     * @return \Generator
     */
    public function methodCallAsyncWrite(string $method, $args = [], array $aargs = ['msg_id' => null]): \Generator
    {
        if (\is_array($args) && isset($args['id']['_']) && isset($args['id']['dc_id']) && ($args['id']['_'] === 'inputBotInlineMessageID' || $args['id']['_'] === 'inputBotInlineMessageID64') && $this->datacenter != $args['id']['dc_id']) {
            $aargs['datacenter'] = $args['id']['dc_id'];
            return yield from $this->API->methodCallAsyncWrite($method, $args, $aargs);
        }
        if (($aargs['file'] ?? false) && !$this->isMedia() && $this->API->datacenter->has($this->datacenter.'_media')) {
            $this->logger->logger('Using media DC');
            $aargs['datacenter'] = $this->datacenter.'_media';
            return yield from $this->API->methodCallAsyncWrite($method, $args, $aargs);
        }
        if (\in_array($method, ['messages.setEncryptedTyping', 'messages.readEncryptedHistory', 'messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService', 'messages.receivedQueue'])) {
            $aargs['queue'] = 'secret';
        }
        if (\is_array($args)) {
            if (isset($args['multiple'])) {
                $aargs['multiple'] = true;
            }
            if (isset($args['message']) && \is_string($args['message']) && \mb_strlen($args['message'], 'UTF-8') > (yield from $this->API->getConfig())['message_length_max'] && \mb_strlen((yield from $this->API->parseMode($args))['message'], 'UTF-8') > (yield from $this->API->getConfig())['message_length_max']) {
                $args = (yield from $this->API->splitToChunks($args));
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
                $promises = [];
                foreach ($args as $single_args) {
                    $promises[] = Tools::call($this->methodCallAsyncWrite($method, $single_args, $new_aargs));
                }
                if (!isset($aargs['postpone'])) {
                    $this->writer->resume();
                }
                return yield Tools::all($promises);
            }
            $args = (yield from $this->API->botAPIToMTProto($args));
            if (isset($args['ping_id']) && \is_int($args['ping_id'])) {
                $args['ping_id'] = Tools::packSignedLong($args['ping_id']);
            }
        }
        $methodInfo = $this->API->getTL()->getMethods()->findByMethod($method);
        if (!$methodInfo) {
            throw new Exception("Could not find method $method!");
        }
        $message = new OutgoingMessage(
            $args,
            $method,
            $methodInfo['type'],
            true,
            !$this->shared->hasTempAuthKey() && \strpos($method, '.') === false
        );
        if ($method === 'users.getUsers' && $args === ['id' => [['_' => 'inputUserSelf']]] || $method === 'auth.exportAuthorization' || $method === 'updates.getDifference') {
            $message->setUserRelated(true);
        }
        if (isset($aargs['msg_id'])) {
            $message->setMsgId($aargs['msg_id']);
        }
        if ($aargs['file'] ?? false) {
            $message->setFileRelated(true);
        }
        if ($aargs['botAPI'] ?? false) {
            $message->setBotAPI(true);
        }
        if (isset($aargs['FloodWaitLimit'])) {
            $message->setFloodWaitLimit($aargs['FloodWaitLimit']);
        }
        $aargs['postpone'] = $aargs['postpone'] ?? false;
        $deferred = yield from $this->sendMessage($message, !$aargs['postpone']);
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
     * @return \Generator
     */
    public function objectCall(string $object, $args = [], array $aargs = ['msg_id' => null]): \Generator
    {
        $message = new OutgoingMessage(
            $args,
            $object,
            '',
            false,
            !$this->shared->hasTempAuthKey()
        );
        if (isset($aargs['promise'])) {
            $message->setPromise($aargs['promise']);
        }
        $aargs['postpone'] = $aargs['postpone'] ?? false;
        return yield from $this->sendMessage($message, !$aargs['postpone']);
    }
}
