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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProto;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Future;
use Closure;
use danog\MadelineProto\Connection;
use danog\MadelineProto\Exception;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function time;

/**
 * Outgoing message.
 *
 * @internal
 */
class MTProtoOutgoingMessage extends MTProtoMessage
{
    public self|LinkedList $next;
    public self|LinkedList $prev;
    protected ?Container $container = null;

    /**
     * The message was created.
     */
    private const STATE_PENDING = 0;
    /**
     * The message was sent.
     */
    private const STATE_SENT = 1;
    /**
     * The message was acked.
     */
    private const STATE_ACKED = 2;
    /**
     * We got a reply to the message.
     */
    private const STATE_REPLIED = self::STATE_ACKED | 4;

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
     * When was this message sent.
     */
    private ?int $sent = null;

    /**
     * Number of times this message was sent.
     */
    private int $tries = 0;

    private ?string $checkTimer = null;
    private readonly ?string $cancelSubscription;

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
        public Connection $connection,
        private ?array $body,
        public readonly string $constructor,
        public readonly string $type,
        public readonly bool $isMethod,
        public readonly bool $unencrypted,
        public ?SpecialMethodType $specialMethodType,
        public readonly ?Cancellation $cancellation,
        public readonly ?string $subtype = null,
        /**
         * Custom flood wait limit for this message.
         */
        public readonly ?int $floodWaitLimit = null,
        public readonly ?int $takeoutId = null,
        public readonly ?string $businessConnectionId = null,
        private ?DeferredFuture $resultDeferred = null,
    ) {
        parent::__construct(!isset(MTProtoMessage::NOT_CONTENT_RELATED[$constructor]));

        $weak = \WeakReference::create($this);
        $this->cancelSubscription = $cancellation?->subscribe(static function (CancelledException $e) use ($weak): void {
            $self = $weak->get();
            if ($self == null || $self->hasReply()) {
                return;
            }
            if (!$self->wasSent()) {
                $self->reply(static fn () => throw $e);
                return;
            }
            $self->reply(static fn () => throw $e);

            $self->connection->requestResponse?->inc([
                'method' => $self->constructor,
                'error_message' => 'Request Timeout',
                'error_code' => '408',
            ]);

            if ($self->hasMsgId() && $self->constructor !== 'rpc_drop_answer') {
                $self->connection->API->logger("Cancelling $self...");
                try {
                    $self->connection->API->logger($self->connection->methodCallAsyncRead(
                        'rpc_drop_answer',
                        ['req_msg_id' => $self->getMsgId()]
                    ));
                } catch (CancelledException) {
                }
            }
        });
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function __debugInfo(): array
    {
        if (!isset($this->next)) {
            $next = null;
        } elseif ($this->next instanceof MTProtoOutgoingMessage) {
            $next = $this->next;
        } else {
            $next = 'head|tail';
        }
        if (!isset($this->prev)) {
            $prev = null;
        } elseif ($this->prev instanceof MTProtoOutgoingMessage) {
            $prev = $this->prev;
        } else {
            $prev = 'head|tail';
        }
        return [(string) $this, 'objId' => spl_object_id($this), 'prev' => $prev, 'next' => $next];
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
    public function sent(bool $pending = true): void
    {
        if ($pending) {
            if ($this->unencrypted) {
                $this->connection->unencrypted_new_outgoing[$this->getMsgId()] = $this;
            } else {
                $this->connection->new_outgoing[$this->getMsgId()] = $this;
            }
        }
        if ($this->sent === null && $this->isMethod) {
            $this->connection->inFlightGauge?->inc([
                'method' => $this->constructor,
            ]);
        }
        $this->state |= self::STATE_SENT;
        if (!$this instanceof Container) {
            $this->unlink();
        }
        $this->sent = hrtime(true);
        if ($this->contentRelated && $pending) {
            $self = \WeakReference::create($this);
            $this->checkTimer = EventLoop::delay(
                $this->connection->API->getSettings()->getConnection()->getTimeout(),
                static function () use ($self): void {
                    $self->get()?->check();
                }
            );
        }
        if (isset($this->sendDeferred)) {
            $sendDeferred = $this->sendDeferred;
            $this->sendDeferred = null;
            $sendDeferred->complete();
        }
    }
    public function unlink(): void
    {
        if (isset($this->next)) {
            $this->next->prev = $this->prev;
            $this->prev->next = $this->next;
            unset($this->next, $this->prev);

            if ($this->unencrypted) {
                unset($this->connection->unencryptedPendingOutgoing->check_queue[$this]);
            } elseif ($this->specialMethodType === SpecialMethodType::UNAUTHED_METHOD) {
                unset($this->connection->uninitedPendingOutgoing->check_queue[$this]);
            } else {
                unset($this->connection->mainPendingOutgoing->check_queue[$this]);
            }
            $this->connection->pendingOutgoingGauge?->dec();
        }
        if ($this->checkTimer !== null) {
            EventLoop::cancel($this->checkTimer);
            $this->checkTimer = null;
        }
    }
    private function check(): void
    {
        if ($this->state & self::STATE_REPLIED) {
            return;
        }
        $shared = $this->connection->getShared();
        $settings = $shared->getSettings();
        $timeout = $settings->getTimeout();
        $self = \WeakReference::create($this);
        $this->checkTimer = EventLoop::delay(
            $timeout,
            static function () use ($self): void {
                $self->get()?->check();
            }
        );

        if ($this->msgId === null) {
            return;
        }
        if ($this->unencrypted) {
            $this->connection->unencryptedPendingOutgoing->check_queue[$this] = true;
        } elseif ($this->specialMethodType === SpecialMethodType::UNAUTHED_METHOD) {
            $this->connection->uninitedPendingOutgoing->check_queue[$this] = true;
        } else {
            $this->connection->mainPendingOutgoing->check_queue[$this] = true;
        }
        $this->connection->flush(true);
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
        $this->sent(false);
        if ($this->checkTimer !== null) {
            EventLoop::cancel($this->checkTimer);
            $this->checkTimer = null;
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
        if ($this->msgId !== null) {
            if ($this->unencrypted) {
                unset($this->connection->unencrypted_new_outgoing[$this->msgId]);
            } else {
                unset($this->connection->new_outgoing[$this->msgId]);
            }
        }
        if ($this->container !== null) {
            if ($this->unencrypted) {
                unset($this->connection->unencrypted_new_outgoing[$this->container->msgId]);
            } else {
                unset($this->connection->new_outgoing[$this->container->msgId]);
            }
        }

        $this->serializedBody = null;
        $this->body = null;

        $this->state |= self::STATE_REPLIED;
        $this->cancellation?->unsubscribe($this->cancelSubscription);
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
        if (!$this->resultDeferred) {
            $this->reply(null);
        }
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
     *
     * @psalm-mutation-free
     */
    public function getBodyOrEmpty(): array
    {
        return (array) $this->body;
    }
    /**
     * Check if we have a body.
     *
     * @psalm-mutation-free
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
     *
     * @psalm-mutation-free
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
     * Set serialized body.
     *
     * @param string $serializedBody Serialized body.
     *
     * @psalm-external-mutation-free
     */
    public function setSerializedBody(string $serializedBody): self
    {
        $this->serializedBody = $serializedBody;

        return $this;
    }

    public function refreshReferences(): Future
    {
        $this->serializedBody = null;
        // To avoid endless loops
        $this->specialMethodType = SpecialMethodType::FILEREF_RELATED;

        return async(function (): ?Closure {
            $this->connection->API->referenceDatabase->refreshNextEnable();
            $this->connection->API->getTL()->serializeMethod($this->constructor, $this->body);
            return $this->connection->API->referenceDatabase->refreshNextDisable();
        });
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
     *
     * @psalm-mutation-free
     */
    public function wasSent(): bool
    {
        return (bool) ($this->state & self::STATE_SENT);
    }
    /**
     * Check if the message has a reply.
     *
     * @psalm-mutation-free
     */
    public function hasReply(): bool
    {
        return (bool) ($this->state & self::STATE_REPLIED);
    }
    /**
     * For logging.
     *
     * @psalm-mutation-free
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
     *
     * @psalm-mutation-free
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
     *
     * @psalm-mutation-free
     */
    public function hasPromise(): bool
    {
        return $this->resultDeferred !== null;
    }

    /**
     * Get the promise.
     *
     * @psalm-mutation-free
     */
    public function getResultPromise(): Future
    {
        \assert($this->resultDeferred !== null);
        return $this->resultDeferred->getFuture();
    }

    /**
     * Reset sent time to trigger resending.
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
     */
    public function setSent(int $sent): self
    {
        $this->sent = $sent;

        return $this;
    }
}
