<?php

declare(strict_types=1);

/**
 * Message.
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
 * MTProto message.
 *
 * @internal
 */
abstract class MTProtoMessage
{
    public const NOT_CONTENT_RELATED = [
        'rpc_drop_answer' => true,
        'rpc_answer_unknown' => true,
        'rpc_answer_dropped_running' => true,
        'rpc_answer_dropped' => true,
        'get_future_salts' => true,
        'future_salt' => true,
        'future_salts' => true,
        'ping' => true,
        'pong' => true,
        'ping_delay_disconnect' => true,
        'destroy_session' => true,
        'destroy_session_ok' => true,
        'destroy_session_none' => true,
        //'new_session_created' => true,
        'msg_container' => true,
        'msg_copy' => true,
        'gzip_packed' => true,
        'http_wait' => true,
        'msgs_ack' => true,
        'bad_msg_notification' => true,
        'bad_server_salt' => true,
        'msgs_state_req' => true,
        'msgs_state_info' => true,
        'msgs_all_info' => true,
        'msg_detailed_info' => true,
        'msg_new_detailed_info' => true,
        'msg_resend_req' => true,
        'msg_resend_ans_req' => true,
    ];
    /**
     * My message ID.
     */
    protected ?int $msgId = null;

    /**
     * Sequence number.
     */
    protected ?int $seqNo = null;

    public function __construct(
        /**
         * Whether constructor is content related.
         */
        public readonly bool $contentRelated
    ) {
    }

    /**
     * Get my message ID.
     */
    public function getMsgId(): ?int
    {
        return $this->msgId;
    }

    /**
     * Set my message ID.
     */
    public function setMsgId(?int $msgId): self
    {
        $this->msgId = $msgId;

        return $this;
    }

    /**
     * Check if we have a message ID.
     */
    public function hasMsgId(): bool
    {
        return $this->msgId !== null;
    }

    /**
     * Get sequence number.
     */
    public function getSeqNo(): ?int
    {
        return $this->seqNo;
    }

    /**
     * Has sequence number.
     */
    public function hasSeqNo(): bool
    {
        return isset($this->seqNo);
    }

    /**
     * Set sequence number.
     *
     * @param null|int $seqNo Sequence number
     */
    public function setSeqNo(?int $seqNo): self
    {
        $this->seqNo = $seqNo;

        return $this;
    }

    /**
     * Check whether this message can be garbage collected.
     */
    abstract public function canGarbageCollect(): bool;
}
