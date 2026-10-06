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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use Amp\CompositeCancellation;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Sync\LocalKeyedMutex;
use Amp\TimeoutCancellation;
use Closure;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\Container;
use danog\MadelineProto\MTProto\LinkedList;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\TL\Exception;
use danog\MadelineProto\Tools;
use danog\MadelineProto\WrappedFuture;
use Revolt\EventLoop;

use function Amp\async;
use function Amp\Future\await;

/**
 * Manages method and object calls.
 *
 *
 * @property LinkedList $mainPendingOutgoing
 * @property LinkedList $uninitedPendingOutgoing
 * @property DataCenterConnection $shared
 * @property MTProto $API
 * @internal
 */
trait CallHandler
{
    /**
     * Recall method.
     */
    public function methodRecall(MTProtoOutgoingMessage $request, ?int $forceDatacenter = null, float|Future|null $defer = null): void
    {
        $id = $request->getMsgId();
        if ($request->unencrypted) {
            unset($this->unencrypted_new_outgoing[$id]);
        } else {
            unset($this->new_outgoing[$id]);
        }
        if ($request instanceof Container) {
            $this->ack_queue = array_merge($request->acks, $this->ack_queue);
            foreach ($request->msgs as $msg) {
                $this->methodRecall($msg, $forceDatacenter, $defer);
            }
            return;
        }
        if ($request->cancellation?->isRequested()) {
            return;
        }
        if (\is_float($defer)) {
            $d = new DeferredFuture;
            $id = EventLoop::delay($defer, $d->complete(...));
            $request->cancellation?->subscribe(static fn () => EventLoop::cancel($id));
            $defer = $d->getFuture();
        }
        $request->unlink();
        if ($defer) {
            $defer->catch($request->reply(...));
            $defer->map(function ($result) use ($request, $forceDatacenter): void {
                if ($result instanceof Closure) {
                    $request->reply($result);
                } else {
                    $this->methodRecall($request, $forceDatacenter);
                }
            });
            return;
        }
        $datacenter = $forceDatacenter ?? $this->datacenter;
        if ($forceDatacenter !== null) {
            /** @var MTProtoOutgoingMessage */
            $request->setMsgId(null);
            $request->setSeqNo(null);
        }
        if ($datacenter === $this->datacenter) {
            EventLoop::queue($this->sendMessage(...), $request);
        } else {
            EventLoop::queue(function () use ($datacenter, $request): void {
                $this->API->datacenter->waitGetConnection($datacenter)
                    ->sendMessage($request);
            });
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
        if (isset($args['message']) && \is_string($args['message']) && mb_strlen($args['message'], 'UTF-8') > ($this->API->getConfig())['message_length_max'] && mb_strlen($this->API->parseMode($args)['message'], 'UTF-8') > ($this->API->getConfig())['message_length_max']) {
            $peer = $args['peer'];
            $args = $this->API->splitToChunks($args);
            $promises = [];
            $queueId = $method.' '.$this->API->getId($peer);

            $promises = [];
            foreach ($args as $k => $sub) {
                if ($k > 0 && isset($sub['random_id']) && \is_int($sub['random_id'])) {
                    // Each message needs its own random ID, derived from the one of the call so that it's the same if the call is made again.
                    $sub['random_id'] = Tools::unpackSignedLong(substr(hash('sha256', pack('q', $sub['random_id']).pack('V', $k), true), 0, 8));
                }
                $sub['queueId'] = $queueId;
                $sub = $this->API->botAPIToMTProto($sub);
                $this->methodAbstractions($method, $sub);
                $promises[] = async($this->methodCallAsyncRead(...), $method, $sub);
            }

            return await($promises);
        }

        $queueId = $args['queueId'] ?? null;
        if ($queueId !== null) {
            $_ = $this->abstractionQueueMutex->acquire($queueId);
        }

        $readFuture = $this->methodCallAsyncWrite($method, $args);
        return $readFuture->await();
    }

    private LocalKeyedMutex $abstractionQueueMutex;
    private ?float $drop = null;
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
        $special = $args['specialMethodType'] ?? null;
        if ($special === SpecialMethodType::FILE_RELATED
            && !$this->shared->auth->isMedia
            && !$this->shared->auth->isCdn
            && $this->API->datacenter->has(-$this->datacenter)
        ) {
            $this->API->logger('Using media DC');
            return $this->API->methodCallAsyncWrite($method, $args, -$this->datacenter);
        }

        $args = $this->API->botAPIToMTProto($args);

        $response = new DeferredFuture;
        $this->methodAbstractions($method, $args);
        if (\in_array($method, ['messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService'], true)) {
            $args['method'] = $method;
            if (isset($args['peer'])) {
                $args = $this->API->getSecretChatController($args['peer'])->encryptSecretMessage($args, $response->getFuture());
            } else {
                if (!$this->API->getSettings()->getSchema()->getFuzzMode()) {
                    throw new Exception('No peer specified for encrypted message!');
                }
            }
        }

        $methodInfo = $this->API->getTL()->getMethods()->findByMethod($method);
        if (!$methodInfo) {
            throw new Exception("Could not find method $method!");
        }
        $encrypted = $methodInfo['encrypted'];
        $timeout = new TimeoutCancellation(
            $args['timeout'] ?? ($this->drop ??= (float) $this->API->getSettings()->getRpc()->getRpcDropTimeout()),
            "Timeout while waiting for $method"
        );
        $cancellation = $cancellation !== null
            ? new CompositeCancellation($cancellation, $timeout)
            : $timeout;
        $message = new MTProtoOutgoingMessage(
            connection: $this,
            body: $args,
            constructor: $method,
            type: $methodInfo['type'],
            subtype: $methodInfo['subtype'] ?? null,
            specialMethodType: $special,
            isMethod: true,
            unencrypted: !$encrypted,
            floodWaitLimit: $args['floodWaitLimit'] ?? null,
            resultDeferred: $response,
            cancellation: $cancellation,
            takeoutId: $args['takeoutId'] ?? null,
            businessConnectionId: $args['businessConnectionId'] ?? null,
        );
        if (isset($args['madelineMsgId'])) {
            $message->setMsgId($args['madelineMsgId']);
        }
        $this->sendMessage($message);
        $message->getSendPromise()->await($cancellation);
        return new WrappedFuture($response->getFuture());
    }
    /**
     * Send object.
     *
     * @param string $object Object name
     * @param array  $args   Arguments
     */
    public function objectCallAsync(string $object, array $args, ?DeferredFuture $promise = null): void
    {
        $cancellation = $args['cancellation'] ?? null;
        $cancellation?->throwIfRequested();
        $timeout = new TimeoutCancellation($this->drop ??= (float) $this->API->getSettings()->getRpc()->getRpcDropTimeout());
        $cancellation = $cancellation !== null
            ? new CompositeCancellation($cancellation, $timeout)
            : $timeout;
        $this->sendMessage(
            new MTProtoOutgoingMessage(
                connection: $this,
                body: $args,
                constructor: $object,
                type: '',
                isMethod: false,
                unencrypted: false,
                specialMethodType: null,
                resultDeferred: $promise,
                cancellation: $cancellation,
            ),
        );
    }
}
