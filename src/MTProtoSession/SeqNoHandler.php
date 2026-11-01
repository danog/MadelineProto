<?php

declare(strict_types=1);

/**
 * SeqNoHandler module.
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

use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;

/**
 * Manages sequence number.
 *
 * @internal
 */
trait SeqNoHandler
{
    public int $session_out_seq_no = 0;
    public ?string $session_id = null;
    /**
     * @psalm-external-mutation-free
     */
    public function generateOutSeqNo(bool $contentRelated): int
    {
        $in = $contentRelated ? 1 : 0;
        $value = $this->session_out_seq_no;
        $this->session_out_seq_no += $in;
        //$this->API->logger("OUT: $value + $in = ".$this->session_out_seq_no);
        return $value * 2 + $in;
    }
    /**
     * Check the parity of the seqno of an incoming message, which says whether it's content-related.
     *
     * Its value isn't checked against a counter of the received messages: the seqno counts the messages the
     * server created before it, and the server can send a message before a container it created earlier (a
     * standalone rpc_result followed by a container with the rpc_result and msgs_ack that precede it). Neither
     * TDLib nor tdesktop check more than the parity.
     *
     * @psalm-external-mutation-free
     */
    public function checkInSeqNo(MTProtoIncomingMessage $message): void
    {
        if ($message->hasSeqNo() && ($message->getSeqNo() & 1) !== ($message->contentRelated ? 1 : 0)) {
            $this->API->logger("The seqno {$message->getSeqNo()} of $message has the wrong parity", Logger::WARNING);
        }
    }
}
