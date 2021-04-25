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
use Amp\Promise;
use danog\MadelineProto\Tools;

/**
 * Incoming message.
 *
 * @internal
 */
class IncomingMessage extends Message
{
    /**
     * We have received this message.
     */
    const STATE_RECEIVED = 4;
    /**
     * We have acknowledged this message.
     */
    const STATE_ACKED = 8;
    /**
     * We have read the contents of this message.
     */
    const STATE_READ = 128;

    /**
     * Response field map.
     */
    private const RESPONSE_ID_MAP = [
        'rpc_result' => 'req_msg_id',
        'future_salts' => 'req_msg_id',
        'msgs_state_info' => 'req_msg_id',
        'bad_server_salt' => 'bad_msg_id',
        'bad_msg_notification' => 'bad_msg_id',
        'pong' => 'msg_id',
    ];
    /**
     * State.
     */
    private int $state = self::STATE_RECEIVED;
    /**
     * Receive date.
     */
    private int $received;
    /**
     * Deserialized response content.
     */
    private array $content;
    /**
     * Was present in container.
     */
    private bool $fromContainer;

    /**
     * DB side effects to be resolved before using the content.
     *
     * @var Promise[]
     */
    private $sideEffects = [];

    /**
     * Constructor.
     *
     * @param array   $content       Content
     * @param boolean $fromContainer Whether this message was in a container
     */
    public function __construct(array $content, string $msgId, bool $fromContainer = false)
    {
        $this->content = $content;
        $this->fromContainer = $fromContainer;
        $this->msgId = $msgId;

        $this->received = \time();

        $this->contentRelated = !isset(Message::NOT_CONTENT_RELATED[$content['_']]);
        if (!$this->contentRelated) {
            $this->state |= 16; // message not requiring acknowledgment
        }
    }
    /**
     * Get deserialized response content.
     *
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Get was present in container.
     *
     * @return bool
     */
    public function isFromContainer(): bool
    {
        return $this->fromContainer;
    }

    /**
     * Get log line.
     *
     * @param int|string $dc DC ID
     *
     * @return string
     */
    public function log($dc): string
    {
        if ($this->fromContainer) {
            return "Inside of container, received {$this->content['_']} from DC $dc";
        }
        return "Received {$this->content['_']} from DC $dc";
    }

    /**
     * Get message type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->content['_'];
    }

    /**
     * Get message type.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->content['_'];
    }

    /**
     * We have acked this message.
     *
     * @return void
     */
    public function ack(): void
    {
        $this->state |= self::STATE_ACKED;
    }
    /**
     * Read this message, clearing its contents.
     *
     * @return array
     */
    public function read(): array
    {
        $this->state |= self::STATE_READ;
        $content = $this->content;
        $this->content = ['_' => $content['_']];
        return $content;
    }

    /**
     * Check if this message can be garbage collected.
     *
     * @return boolean
     */
    public function canGarbageCollect(): bool
    {
        return (bool) ($this->state & self::STATE_READ);
    }

    /**
     * Get ID of message to which this message replies.
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->content[self::RESPONSE_ID_MAP[$this->content['_']]];
    }
    /**
     * Get state.
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Set DB side effects to be resolved before using the content.
     *
     * @param Promise[] $sideEffects DB side effects to be resolved before using the content
     *
     * @return self
     */
    public function setSideEffects(array $sideEffects): self
    {
        $this->sideEffects = $sideEffects;

        return $this;
    }

    /**
     * Get DB side effects to be resolved before using the specified content.
     *
     * @template T
     *
     * @param T $return Return value
     *
     * @psalm-return ?Promise<T>
     */
    public function getSideEffects($return): ?Promise
    {
        if (!$this->sideEffects) {
            return null;
        }

        $deferred = new Deferred;
        $result = $deferred->promise();

        $pending = \count($this->sideEffects);

        foreach ($this->sideEffects as $promise) {
            $promise = Tools::call($promise);
            $promise->onResolve(function ($exception, $value) use (&$deferred, &$pending, $return) {
                if ($pending === 0) {
                    return;
                }

                if ($exception) {
                    $pending = 0;
                    $deferred->fail($exception);
                    $deferred = null;
                    return;
                }

                if (0 === --$pending) {
                    $deferred->resolve($return);
                }
            });
        }
        $this->sideEffects = [];
        return $result;
    }


    /**
     * Get receive date.
     *
     * @return int
     */
    public function getReceived(): int
    {
        return $this->received;
    }
}
