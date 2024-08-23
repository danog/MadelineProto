<?php

declare(strict_types=1);

/**
 * Outgoing message.
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

namespace danog\MadelineProto\MTProto;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Future;
use danog\MadelineProto\Connection;
use danog\MadelineProto\Exception;
use Revolt\EventLoop;
use Throwable;

use function time;

/**
 * Outgoing message.
 *
 * @internal
 */
class MTProtoOutgoingMessage extends MTProtoMessage
{
    /**
     * The message was created.
     */
    public const STATE_PENDING = 0;
    /**
     * The message was sent.
     */
    public const STATE_SENT = 1;
    /**
     * The message was acked.
     */
    public const STATE_ACKED = 2;
    /**
     * We got a reply to the message.
     */
    public const STATE_REPLIED = self::STATE_ACKED | 4;

    /**
     * State of message.
     *
     * @var self::STATE_*
     */
    private int $state = self::STATE_PENDING;

    /**
     * Send deferred.
     *
     * @var ?DeferredFuture<null>
     */
    private ?DeferredFuture $sendDeferred = null;

    /**
     * Serialized body.
     */
    private ?string $serializedBody = null;

    /**
     * Whether we should refresh references upon serialization of this message.
     */
    private bool $refreshReferences = false;

    /**
     * When was this message sent.
     */
    private ?int $sent = null;

    /**
     * Number of times this message was sent.
     */
    private int $tries = 0;

    /**
     * Whether this message is related to a user, as in getting a successful reply means we have auth.
     */
    public readonly bool $userRelated;

    /**
     * Create outgoing message.
     *
     * @param array $body        Body
     * @param string                  $constructor Constructor name
     * @param string                  $type        Constructor type
     * @param boolean                 $isMethod    Is this a method?
     * @param boolean                 $unencrypted Is this an unencrypted message?
     */
    public function __construct(
        private readonly Connection $connection,
        private ?array $body,
        public readonly string $constructor,
        public readonly string $type,
        public readonly bool $isMethod,
        public readonly bool $unencrypted,
        public readonly ?string $subtype = null,
        /**
         * Whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
         */
        public readonly bool $fileRelated = false,
        /**
         * Previous queued message.
         */
        public readonly ?self $previousQueuedMessage = null,
        /**
         * Custom flood wait limit for this message.
         */
        public readonly ?int $floodWaitLimit = null,
        public readonly ?int $takeoutId = null,
        public readonly ?string $businessConnectionId = null,
        private ?DeferredFuture $resultDeferred = null,
        public readonly ?Cancellation $cancellation = null
    ) {
        $this->userRelated = $constructor === 'users.getUsers' && $body === ['id' => [['_' => 'inputUserSelf']]] || $constructor === 'auth.exportAuthorization' || $constructor === 'updates.getDifference';

        parent::__construct(!isset(MTProtoMessage::NOT_CONTENT_RELATED[$constructor]));

        $cancellation?->subscribe(function (CancelledException $e): void {
            if ($this->hasReply()) {
                return;
            }
            if (!$this->wasSent()) {
                $this->reply(static fn () => throw $e);
                return;
            }
            $this->reply(static fn () => throw $e);

            $this->connection->requestResponse?->inc([
                'method' => $this->constructor,
                'error_message' => 'Request Timeout',
                'error_code' => '408',
            ]);

            if ($this->hasMsgId()) {
                $this->connection->API->logger("Cancelling $this...");
                $this->connection->API->logger($this->connection->methodCallAsyncRead(
                    'rpc_drop_answer',
                    ['req_msg_id' => $this->getMsgId()]
                ));
            }
        });
    }

    /**
     * Whether cancellation is requested.
     */
    public function isCancellationRequested(): bool
    {
        return $this->cancellation?->isRequested() ?? false;
    }

    /**
     * Signal that we're trying to send the message.
     */
    public function trySend(): void
    {
        if (!isset($this->sendDeferred)) {
            $this->sendDeferred = new DeferredFuture;
        }
        $this->tries++;
    }
    /**
     * Signal that the message was sent.
     */
    public function sent(): void
    {
        if ($this->sent === null && $this->isMethod) {
            $this->connection->inFlightGauge?->inc([
                'method' => $this->constructor,
            ]);
        }
        $this->state |= self::STATE_SENT;
        $this->sent = hrtime(true);
        if (isset($this->sendDeferred)) {
            $sendDeferred = $this->sendDeferred;
            $this->sendDeferred = null;
            $sendDeferred->complete();
        }
    }
    /**
     * Set reply to message.
     *
     * @param mixed|(callable(): Throwable) $result
     */
    public function reply($result): void
    {
        if ($this->state & self::STATE_REPLIED) {
            //throw new Exception("Trying to double reply to message $this!");
            // It can happen, no big deal
            return;
        }
        if (!($this->state & self::STATE_SENT)) {
            $this->sent();
        }

        if ($this->isMethod) {
            $this->connection->inFlightGauge?->dec([
                'method' => $this->constructor,
            ]);
            if (!\is_callable($result)) {
                $this->connection->requestLatencies?->observe(
                    hrtime(true) - $this->sent,
                    ['method' => $this->constructor]
                );
            }
        }

        $this->serializedBody = null;
        $this->body = null;

        $this->state |= self::STATE_REPLIED;
        if ($this->resultDeferred) { // Sometimes can get an RPC error for constructors
            $promise = $this->resultDeferred;
            $this->resultDeferred = null;
            EventLoop::queue($promise->complete(...), $result);
        }
    }

    /**
     * ACK message.
     */
    public function ack(): void
    {
        $this->state |= self::STATE_ACKED;
    }
    /**
     * Get state of message.
     *
     * @return self::STATE_*
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Get message body.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get message body or empty array.
     */
    public function getBodyOrEmpty(): array
    {
        return (array) $this->body;
    }
    /**
     * Check if we have a body.
     */
    public function hasBody(): bool
    {
        return $this->body !== null;
    }

    /**
     * Get serialized body.
     */
    public function getSerializedBody(): ?string
    {
        return $this->serializedBody;
    }
    /**
     * Check if we have a serialized body.
     */
    public function hasSerializedBody(): bool
    {
        return $this->serializedBody !== null;
    }

    /**
     * Get number of times this message was sent.
     */
    public function getTries(): int
    {
        return $this->tries;
    }

    /**
     * Get whether we should refresh references upon serialization of this message.
     */
    public function shouldRefreshReferences(): bool
    {
        return $this->refreshReferences;
    }

    /**
     * Set serialized body.
     *
     * @param string $serializedBody Serialized body.
     */
    public function setSerializedBody(string $serializedBody): self
    {
        $this->serializedBody = $serializedBody;

        return $this;
    }

    /**
     * Set whether we should refresh references upon serialization of this message.
     *
     * @param bool $refreshReferences Whether we should refresh references upon serialization of this message.
     */
    public function setRefreshReferences(bool $refreshReferences): self
    {
        $this->refreshReferences = $refreshReferences;

        return $this;
    }

    /**
     * Get when was this message sent.
     */
    public function getSent(): ?int
    {
        return $this->sent;
    }

    /**
     * Check if the message was sent.
     */
    public function wasSent(): bool
    {
        return (bool) ($this->state & self::STATE_SENT);
    }
    /**
     * Check if the message has a reply.
     */
    public function hasReply(): bool
    {
        return (bool) ($this->state & self::STATE_REPLIED);
    }
    /**
     * Check if can garbage collect this message.
     */
    public function canGarbageCollect(): bool
    {
        if ($this->state & self::STATE_REPLIED) {
            return true;
        }
        if (!$this->hasPromise()) {
            return true;
        }
        return false;
    }
    /**
     * For logging.
     */
    public function __toString(): string
    {
        if ($this->state & self::STATE_REPLIED) {
            $state = 'acked (by reply)';
        } elseif ($this->state & self::STATE_ACKED) {
            $state = 'acked';
        } elseif ($this->state & self::STATE_SENT) {
            $state = 'sent '.((hrtime(true) - $this->sent) / 1_000_000_000).' seconds ago';
        } else {
            $state = 'pending';
        }
        if ($this->msgId) {
            return "{$this->constructor} with message ID {$this->msgId} $state";
        }
        return "{$this->constructor} $state";
    }

    /**
     * Wait for message to be sent.
     *
     * @return Future<null>
     */
    public function getSendPromise(): Future
    {
        if (!$this->sendDeferred) {
            throw new Exception("Message was already sent, can't get send promise!");
        }
        return $this->sendDeferred->getFuture();
    }

    /**
     * Check if we have a promise.
     */
    public function hasPromise(): bool
    {
        return $this->resultDeferred !== null;
    }

    /**
     * Get the promise.
     */
    public function getResultPromise(): Future
    {
        \assert($this->resultDeferred !== null);
        return $this->resultDeferred->getFuture();
    }

    /**
     * Reset sent time to trigger resending.
     */
    public function resetSent(): self
    {
        $this->sent = 0;

        return $this;
    }

    /**
     * Set when was this message sent.
     *
     * @param int $sent When was this message sent.
     */
    public function setSent(int $sent): self
    {
        $this->sent = $sent;

        return $this;
    }
}
