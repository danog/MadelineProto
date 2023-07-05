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
    const STATE_PENDING = 0;
    /**
     * The message was sent.
     */
    const STATE_SENT = 1;
    /**
     * The message was acked.
     */
    const STATE_ACKED = 2;
    /**
     * We got a reply to the message.
     */
    const STATE_REPLIED = self::STATE_ACKED | 4;

    /**
     * State of message.
     *
     * @var self::STATE_*
     */
    private int $state = self::STATE_PENDING;
    /**
     * Constructor name.
     */
    private string $constructor;
    /**
     * Constructor type.
     */
    private string $type;

    /**
     * Whether this is a method.
     */
    private bool $method;
    /**
     * Resolution deferred.
     */
    private ?DeferredFuture $resultDeferred = null;
    /**
     * Send deferred.
     *
     * @var ?DeferredFuture<null>
     */
    private ?DeferredFuture $sendDeferred = null;

    /**
     * Whether this is an unencrypted message.
     */
    private bool $unencrypted;

    /**
     * Message body.
     *
     * @var array|(callable(): array)|null
     */
    private $body = null;

    /**
     * Serialized body.
     */
    private ?string $serializedBody = null;

    /**
     * Whether this message is related to a user, as in getting a successful reply means we have auth.
     */
    private bool $userRelated = false;
    /**
     * Whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
     */
    private bool $fileRelated = false;

    /**
     * Custom flood wait limit for this bot.
     */
    private ?int $floodWaitLimit = null;

    /**
     * Whether we should try converting the result to a bot API object.
     */
    private bool $botAPI = false;

    /**
     * Whether we should refresh references upon serialization of this message.
     */
    private bool $refreshReferences = false;

    /**
     * Queue ID.
     */
    private ?string $queueId = null;

    /**
     * When was this message sent.
     */
    private ?int $sent = null;

    /**
     * Number of times this message was sent.
     */
    private int $tries = 0;

    private ?Cancellation $cancellation = null;

    /**
     * Create outgoing message.
     *
     * @param array|callable(): array $body Body
     * @param string                  $constructor Constructor name
     * @param string                  $type        Constructor type
     * @param boolean                 $method      Is this a method?
     * @param boolean                 $unencrypted Is this an unencrypted message?
     * @param null|DeferredFuture         $deferred    Response deferred
     */
    public function __construct(array|callable $body, string $constructor, string $type, bool $method, bool $unencrypted, ?DeferredFuture $deferred = null, ?Cancellation $cancellation = null)
    {
        $this->body = $body;
        $this->constructor = $constructor;
        $this->type = $type;
        $this->method = $method;
        $this->unencrypted = $unencrypted;
        if ($deferred) {
            $this->resultDeferred = $deferred;
        }

        $this->contentRelated = !isset(MTProtoMessage::NOT_CONTENT_RELATED[$constructor]);
        $this->cancellation = $cancellation;
        $cancellation?->subscribe(fn (CancelledException $e) => $this->reply(fn () => throw $e));
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
        if ($this->state & self::STATE_REPLIED) {
            //throw new Exception("Trying to resend already replied message $this!");
        }
        $this->state |= self::STATE_SENT;
        $this->sent = \time();
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
        return \is_callable($this->body) ? ($this->body)() : $this->body;
    }

    /**
     * Get message body or empty array.
     */
    public function getBodyOrEmpty(): array
    {
        return \is_array($this->body) ? $this->body : [];
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
     * Get constructor name.
     */
    public function getConstructor(): string
    {
        return $this->constructor;
    }

    /**
     * Get constructor type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get whether this is a method.
     */
    public function isMethod(): bool
    {
        return $this->method;
    }

    /**
     * Get whether this is an unencrypted message.
     */
    public function isUnencrypted(): bool
    {
        return $this->unencrypted;
    }
    /**
     * Get whether this is an encrypted message.
     */
    public function isEncrypted(): bool
    {
        return !$this->unencrypted;
    }

    /**
     * Get whether this message is related to a user, as in getting a successful reply means we have auth.
     */
    public function isUserRelated(): bool
    {
        return $this->userRelated;
    }

    /**
     * Get whether we should refresh references upon serialization of this message.
     */
    public function shouldRefreshReferences(): bool
    {
        return $this->refreshReferences;
    }

    /**
     * Get queue ID.
     */
    public function getQueueId(): ?string
    {
        return $this->queueId;
    }
    /**
     * Get whether we have a queue ID.
     */
    public function hasQueue(): bool
    {
        return $this->queueId !== null;
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
     * Set whether this message is related to a user, as in getting a successful reply means we have auth.
     *
     * @param bool $userRelated Whether this message is related to a user, as in getting a successful reply means we have auth.
     */
    public function setUserRelated(bool $userRelated): self
    {
        $this->userRelated = $userRelated;

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
     * Set queue ID.
     *
     * @param null|string $queueId Queue ID.
     */
    public function setQueueId(?string $queueId): self
    {
        $this->queueId = $queueId;

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
            $state = 'sent '.(\time() - $this->sent).' seconds ago';
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
     * Reset sent time to trigger resending.
     */
    public function resetSent(): self
    {
        $this->sent = 0;

        return $this;
    }

    /**
     * Get whether we should try converting the result to a bot API object.
     */
    public function getBotAPI(): bool
    {
        return $this->botAPI;
    }

    /**
     * Set whether we should try converting the result to a bot API object.
     *
     * @param bool $botAPI Whether we should try converting the result to a bot API object
     */
    public function setBotAPI(bool $botAPI): self
    {
        $this->botAPI = $botAPI;

        return $this;
    }

    /**
     * Get whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
     */
    public function isFileRelated(): bool
    {
        return $this->fileRelated;
    }

    /**
     * Set whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
     *
     * @param bool $fileRelated Whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
     */
    public function setFileRelated(bool $fileRelated): self
    {
        $this->fileRelated = $fileRelated;

        return $this;
    }

    /**
     * Get custom flood wait limit for this bot.
     */
    public function getFloodWaitLimit(): ?int
    {
        return $this->floodWaitLimit;
    }

    /**
     * Set custom flood wait limit for this bot.
     *
     * @param null|int $floodWaitLimit Custom flood wait limit for this bot
     */
    public function setFloodWaitLimit(?int $floodWaitLimit): self
    {
        $this->floodWaitLimit = $floodWaitLimit;

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
