<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use Amp\DeferredFuture;
use Amp\Sync\LocalKeyedMutex;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\TL\Exception;
use danog\MadelineProto\WrappedFuture;
use Revolt\EventLoop;

use function Amp\async;
use function Amp\Future\await;

/**
 * Manages method and object calls.
 *
 *
 * @property DataCenterConnection $shared
 * @internal
 */
trait CallHandler
{
    /**
     * Recall method.
     */
    public function methodRecall(int $message_id, ?int $datacenter = null): void
    {
        if ($datacenter === $this->datacenter) {
            $datacenter = null;
        }
        $message = $this->outgoing_messages[$message_id] ?? null;
        $message_ids = $message instanceof Container
            ? $message->getIds()
            : [$message_id];
        foreach ($message_ids as $message_id) {
            if (isset($this->outgoing_messages[$message_id])
                && !$this->outgoing_messages[$message_id]->canGarbageCollect()) {
                if ($datacenter) {
                    /** @var MTProtoOutgoingMessage */
                    $message = $this->outgoing_messages[$message_id];
                    $this->gotResponseForOutgoingMessage($message);
                    $message->setMsgId(null);
                    $message->setSeqNo(null);
                    EventLoop::queue(function () use ($datacenter, $message): void {
                        $this->API->datacenter->waitGetConnection($datacenter)
                            ->sendMessage($message);
                    });
                } else {
                    /** @var MTProtoOutgoingMessage */
                    $message = $this->outgoing_messages[$message_id];
                    if (!$message->hasSeqNo()) {
                        $this->gotResponseForOutgoingMessage($message);
                    }
                    EventLoop::queue($this->sendMessage(...), $message);
                }
            } else {
                $this->API->logger('Could not resend '.($this->outgoing_messages[$message_id] ?? $message_id));
            }
        }
    }
    /**
     * Call method and wait asynchronously for response.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     */
    public function methodCallAsyncRead(string $method, array $args)
    {
        $readFuture = $this->methodCallAsyncWrite($method, $args);
        return $readFuture->await();
    }
    private LocalKeyedMutex $abstractionQueueMutex;
    /**
     * Call method and make sure it is asynchronously sent (generator).
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     */
    public function methodCallAsyncWrite(string $method, array $args): WrappedFuture
    {
        $cancellation = $args['cancellation'] ?? null;
        $cancellation?->throwIfRequested();
        if (isset($args['id']) && \is_array($args['id']) && isset($args['id']['_']) && isset($args['id']['dc_id']) && ($args['id']['_'] === 'inputBotInlineMessageID' || $args['id']['_'] === 'inputBotInlineMessageID64') && $this->datacenter != $args['id']['dc_id']) {
            return $this->API->methodCallAsyncWrite($method, $args, $args['id']['dc_id']);
        }
        $file = \in_array($method, ['upload.saveFilePart', 'upload.saveBigFilePart', 'upload.getFile', 'upload.getCdnFile'], true);
        if ($file && !$this->isMedia() && $this->API->datacenter->has(-$this->datacenter)) {
            $this->API->logger('Using media DC');
            return $this->API->methodCallAsyncWrite($method, $args, -$this->datacenter);
        }

        if (isset($args['message']) && \is_string($args['message']) && mb_strlen($args['message'], 'UTF-8') > ($this->API->getConfig())['message_length_max'] && mb_strlen($this->API->parseMode($args)['message'], 'UTF-8') > ($this->API->getConfig())['message_length_max']) {
            $peer = $args['peer'];
            $args = $this->API->splitToChunks($args);
            $promises = [];
            $queueId = $method.' '.$this->API->getId($peer);

            $promises = [];
            foreach ($args as $sub) {
                $sub['queueId'] = $queueId;
                $sub = $this->API->botAPIToMTProto($sub);
                $this->methodAbstractions($method, $sub);
                $promises[] = async($this->methodCallAsyncWrite(...), $method, $sub);
            }

            return new WrappedFuture(async(static fn () => array_map(
                static fn (WrappedFuture $f) => $f->await(),
                await($promises)
            )));
        }

        $queueId = $args['queueId'] ?? null;
        $prev = null;
        $lock = null;
        if ($queueId !== null) {
            $lock = $this->abstractionQueueMutex->acquire($queueId);
            if (isset($this->callQueue[$queueId])
                && !($prev = $this->callQueue[$queueId])->hasReply()
            ) {
                $this->API->logger("$method to queue with ID $queueId", Logger::ULTRA_VERBOSE);
            } else {
                $prev = null;
                $this->API->logger("$method is the first in the queue with ID $queueId", Logger::ULTRA_VERBOSE);
            }
        }

        $args = $this->API->botAPIToMTProto($args);

        $response = new DeferredFuture;
        $this->methodAbstractions($method, $args);
        if (\in_array($method, ['messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService'], true)) {
            $args['method'] = $method;
            $args = $this->API->getSecretChatController($args['peer'])->encryptSecretMessage($args, $response->getFuture());
        }

        $methodInfo = $this->API->getTL()->getMethods()->findByMethod($method);
        if (!$methodInfo) {
            throw new Exception("Could not find method $method!");
        }
        $encrypted = $methodInfo['encrypted'];
        if (!$encrypted && $this->shared->hasTempAuthKey()) {
            $encrypted = true;
        }
        $message = new MTProtoOutgoingMessage(
            connection: $this,
            body: $args,
            constructor: $method,
            type: $methodInfo['type'],
            subtype: $methodInfo['subtype'] ?? null,
            isMethod: true,
            unencrypted: !$encrypted,
            fileRelated: $file,
            previousQueuedMessage: $prev,
            floodWaitLimit: $args['floodWaitLimit'] ?? null,
            resultDeferred: $response,
            cancellation: $cancellation,
            takeoutId: $args['takeoutId'] ?? null,
            businessConnectionId: $args['businessConnectionId'] ?? null,
        );
        if ($queueId !== null) {
            $this->callQueue[$queueId] = $message;
            $lock->release();
        }
        if (isset($args['madelineMsgId'])) {
            $message->setMsgId($args['madelineMsgId']);
        }
        $this->sendMessage($message);
        $this->checker->resume();
        return new WrappedFuture($response->getFuture());
    }
    /**
     * Send object and make sure it is asynchronously sent (generator).
     *
     * @param string $object Object name
     * @param array  $args   Arguments
     */
    public function objectCall(string $object, array $args, ?DeferredFuture $promise = null): void
    {
        $this->sendMessage(
            new MTProtoOutgoingMessage(
                connection: $this,
                body: $args,
                constructor: $object,
                type: '',
                isMethod: false,
                unencrypted: false,
                resultDeferred: $promise
            ),
        );
    }
}
