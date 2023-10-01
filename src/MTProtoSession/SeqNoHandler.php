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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
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
    public int $session_in_seq_no = 0;
    public ?string $session_id = null;
    public function generateOutSeqNo(bool $contentRelated): int
    {
        $in = $contentRelated ? 1 : 0;
        $value = $this->session_out_seq_no;
        $this->session_out_seq_no += $in;
        //$this->API->logger("OUT: $value + $in = ".$this->session_out_seq_no);
        return $value * 2 + $in;
    }
    public function checkInSeqNo(MTProtoIncomingMessage $message): void
    {
        if ($message->hasSeqNo()) {
            $in = $message->contentRelated ? 1 : 0;
            $value = $this->session_in_seq_no;
            $this->session_in_seq_no += $in;
            $value *= 2;
            //$this->API->logger("IN $message ({$message->getMsgId()}): $value + $in = {$message->getSeqNo()}");
            $seq_no = $value + $in;
            if ($seq_no !== $message->getSeqNo()) {
                $this->API->logger("SECURITY WARNING: Seqno mismatch (should be $seq_no, is {$message->getSeqNo()}, $message)", Logger::ULTRA_VERBOSE);
            }
        }
    }
}
