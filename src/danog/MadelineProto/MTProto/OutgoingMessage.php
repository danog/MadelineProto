<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProto;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use danog\MadelineProto\Exception;
use danog\MadelineProto\MTProtoSession\MsgIdHandler;

/**
 * Outgoing message.
 *
 * @internal
 */
class OutgoingMessage extends Message
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
     * @var int
     * @psalm-var self::STATE_*
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
    private ?Deferred $promise = null;
    /**
     * Send deferred.
     */
    private ?Deferred $sendPromise = null;


    /**
     * Whether this is an unencrypted message.
     */
    private bool $unencrypted;

    /**
     * Message body.
     *
     * @var \Generator|array|null
     */
    private $body;

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
    private int $sent = 0;

    /**
     * Number of times this message was sent.
     */
    private int $tries = 0;

    /**
     * Create outgoing message.
     *
     * @param \Generator|array  $body        Body
     * @param string            $constructor Constructor name
     * @param string            $type        Constructor type
     * @param boolean           $method      Is this a method?
     * @param boolean           $unencrypted Is this an unencrypted message?
     */
    public function __construct($body, string $constructor, string $type, bool $method, bool $unencrypted)
    {
        $this->body = $body;
        $this->constructor = $constructor;
        $this->type = $type;
        $this->method = $method;
        $this->unencrypted = $unencrypted;
        if ($method) {
            $this->promise = new Deferred;
        }

        $this->contentRelated = !isset(Message::NOT_CONTENT_RELATED[$constructor]);
    }

    /**
     * Signal that we're trying to send the message.
     *
     * @return void
     */
    public function trySend(): void
    {
        if (!isset($this->sendPromise)) {
            $this->sendPromise = new Deferred;
        }
        $this->tries++;
    }
    /**
     * Signal that the message was sent.
     *
     * @return void
     */
    public function sent(): void
    {
        if ($this->state & self::STATE_REPLIED) {
            //throw new Exception("Trying to resend already replied message $this!");
        }
        $this->state |= self::STATE_SENT;
        $this->sent = \time();
        if (isset($this->sendPromise)) {
            $sendPromise = $this->sendPromise;
            $this->sendPromise = null;
            $sendPromise->resolve($this->promise ?? true);
        }
    }
    /**
     * Set reply to message.
     *
     * @param Promise|mixed $result
     * @return void
     */
    public function reply($result): void
    {
        if ($this->state & self::STATE_REPLIED) {
            throw new Exception("Trying to double reply to message $this!");
        }
        $this->serializedBody = null;
        $this->body = null;

        $this->state |= self::STATE_REPLIED;
        if ($this->promise) { // Sometimes can get an RPC error for constructors
            $promise = $this->promise;
            $this->promise = null;
            Loop::defer(fn () => $promise->resolve($result));
        }
    }

    /**
     * ACK message.
     *
     * @return void
     */
    public function ack(): void
    {
        $this->state |= self::STATE_ACKED;
    }
    /**
     * Get state of message.
     *
     * @return int
     * @psalm-return self::STATE_*
     */
    public function getState(): int
    {
        return $this->state;
    }


    /**
     * Get message body.
     *
     * @return \Generator
     */
    public function getBody(): \Generator
    {
        return $this->body instanceof \Generator ? yield from $this->body : $this->body;
    }

    /**
     * Get message body or empty array.
     *
     * @return array
     */
    public function getBodyOrEmpty(): array
    {
        return \is_array($this->body) ? $this->body : [];
    }
    /**
     * Check if we have a body.
     *
     * @return boolean
     */
    public function hasBody(): bool
    {
        return $this->body !== null;
    }

    /**
     * Get serialized body.
     *
     * @return ?string
     */
    public function getSerializedBody(): ?string
    {
        return $this->serializedBody;
    }
    /**
     * Check if we have a serialized body.
     *
     * @return boolean
     */
    public function hasSerializedBody(): bool
    {
        return $this->serializedBody !== null;
    }

    /**
     * Get number of times this message was sent.
     *
     * @return int
     */
    public function getTries(): int
    {
        return $this->tries;
    }

    /**
     * Get constructor name.
     *
     * @return string
     */
    public function getConstructor(): string
    {
        return $this->constructor;
    }

    /**
     * Get constructor type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get whether this is a method.
     *
     * @return bool
     */
    public function isMethod(): bool
    {
        return $this->method;
    }

    /**
     * Get whether this is an unencrypted message.
     *
     * @return bool
     */
    public function isUnencrypted(): bool
    {
        return $this->unencrypted;
    }
    /**
     * Get whether this is an encrypted message.
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return !$this->unencrypted;
    }

    /**
     * Get whether this message is related to a user, as in getting a successful reply means we have auth.
     *
     * @return bool
     */
    public function isUserRelated(): bool
    {
        return $this->userRelated;
    }

    /**
     * Get whether we should refresh references upon serialization of this message.
     *
     * @return bool
     */
    public function shouldRefreshReferences(): bool
    {
        return $this->refreshReferences;
    }

    /**
     * Get queue ID.
     *
     * @return ?string
     */
    public function getQueueId(): ?string
    {
        return $this->queueId;
    }
    /**
     * Get whether we have a queue ID.
     *
     * @return bool
     */
    public function hasQueue(): bool
    {
        return $this->queueId !== null;
    }

    /**
     * Set serialized body.
     *
     * @param string $serializedBody Serialized body.
     *
     * @return self
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
     *
     * @return self
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
     *
     * @return self
     */
    public function setRefreshReferences(bool $refreshReferences): self
    {
        $this->refreshReferences = $refreshReferences;

        return $this;
    }

    /**
     * Set queue ID.
     *
     * @param ?string $queueId Queue ID.
     *
     * @return self
     */
    public function setQueueId(?string $queueId): self
    {
        $this->queueId = $queueId;

        return $this;
    }

    /**
     * Get when was this message sent.
     *
     * @return int
     */
    public function getSent(): int
    {
        return $this->sent;
    }

    /**
     * Check if the message was sent.
     *
     * @return boolean
     */
    public function wasSent(): bool
    {
        return (bool) ($this->state & self::STATE_SENT);
    }
    /**
     * Check if can garbage collect this message.
     *
     * @return boolean
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
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->msgId) {
            $msgId = MsgIdHandler::toString($this->msgId);
            return "{$this->constructor} with message ID $msgId";
        }
        return $this->constructor;
    }

    /**
     * Set resolution deferred.
     *
     * @param Deferred $promise Resolution deferred.
     *
     * @return self
     */
    public function setPromise(Deferred $promise): self
    {
        $this->promise = $promise;

        return $this;
    }

    /**
     * Wait for message to be sent.
     *
     * @return Promise
     */
    public function getSendPromise(): Promise
    {
        if (!$this->sendPromise) {
            throw new Exception("Message was already sent, can't get send promise!");
        }
        return $this->sendPromise->promise();
    }

    /**
     * Check if we have a promise.
     *
     * @return bool
     */
    public function hasPromise(): bool
    {
        return $this->promise !== null;
    }

    /**
     * Reset sent time to trigger resending.
     *
     * @return self
     */
    public function resetSent(): self
    {
        $this->sent = 0;

        return $this;
    }

    /**
     * Get whether we should try converting the result to a bot API object.
     *
     * @return bool
     */
    public function getBotAPI(): bool
    {
        return $this->botAPI;
    }

    /**
     * Set whether we should try converting the result to a bot API object.
     *
     * @param bool $botAPI Whether we should try converting the result to a bot API object
     *
     * @return self
     */
    public function setBotAPI(bool $botAPI): self
    {
        $this->botAPI = $botAPI;

        return $this;
    }

    /**
     * Get whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
     *
     * @return bool
     */
    public function isFileRelated(): bool
    {
        return $this->fileRelated;
    }

    /**
     * Set whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
     *
     * @param bool $fileRelated Whether this message is related to a file upload, as in getting a redirect should redirect to a media server.
     *
     * @return self
     */
    public function setFileRelated(bool $fileRelated): self
    {
        $this->fileRelated = $fileRelated;

        return $this;
    }

    /**
     * Get custom flood wait limit for this bot.
     *
     * @return ?int
     */
    public function getFloodWaitLimit(): ?int
    {
        return $this->floodWaitLimit;
    }

    /**
     * Set custom flood wait limit for this bot.
     *
     * @param ?int $floodWaitLimit Custom flood wait limit for this bot
     *
     * @return self
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
     *
     * @return self
     */
    public function setSent(int $sent): self
    {
        $this->sent = $sent;

        return $this;
    }
}
