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

/**
 * Incoming message.
 *
 * @internal
 */
final class MTProtoIncomingMessage extends MTProtoMessage
{
    /**
     * We have received this message.
     */
    public const STATE_RECEIVED = 4;
    /**
     * We have acknowledged this message.
     */
    public const STATE_ACKED = 8;
    /**
     * We have read the contents of this message.
     */
    public const STATE_READ = 128;

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
    public readonly int $received;
    /**
     * Deserialized response content.
     */
    private array $content;

    /**
     * Constructor.
     *
     * @param array   $content       Content
     * @param boolean $fromContainer Whether this message was in a container
     */
    public function __construct(array $content, int $msgId, public readonly bool $unencrypted, public readonly bool $fromContainer = false)
    {
        $this->content = $content;
        $this->msgId = $msgId;

        $this->received = hrtime(true);

        parent::__construct(!isset(MTProtoMessage::NOT_CONTENT_RELATED[$content['_']]));
        if (!$this->contentRelated) {
            $this->state |= 16; // message not requiring acknowledgment
        }
    }

    /**
     * Get my message ID.
     */
    public function getMsgId(): int
    {
        \assert($this->msgId !== null);
        return $this->msgId;
    }

    /**
     * Get deserialized response content.
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Get log line.
     *
     * @param int $dc DC ID
     */
    public function log(int $dc): string
    {
        if ($this->fromContainer) {
            return "Inside of container, received {$this->content['_']} from DC $dc";
        }
        return "Received {$this->content['_']} from DC $dc";
    }

    /**
     * Get message type.
     */
    public function getPredicate(): string
    {
        return $this->content['_'];
    }

    /**
     * Get message type.
     */
    public function __toString(): string
    {
        return "incoming message {$this->content['_']}";
    }

    /**
     * We have acked this message.
     */
    public function ack(): void
    {
        $this->state |= self::STATE_ACKED;
    }
    /**
     * Read this message, clearing its contents.
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
     */
    public function canGarbageCollect(): bool
    {
        return (bool) ($this->state & self::STATE_READ);
    }

    /**
     * Get ID of message to which this message replies.
     */
    public function getRequestId(): int
    {
        return $this->content[self::RESPONSE_ID_MAP[$this->content['_']]];
    }
    /**
     * Get state.
     */
    public function getState(): int
    {
        return $this->state;
    }
}
