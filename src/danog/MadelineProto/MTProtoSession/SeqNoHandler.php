<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\MTProto;

/**
 * Manages sequence number.
 */
trait SeqNoHandler
{
    public $session_out_seq_no = 0;
    public $session_in_seq_no = 0;

    public $session_id;

    public function generateOutSeqNo($contentRelated)
    {
        $in = $contentRelated ? 1 : 0;
        $value = $this->session_out_seq_no;
        $this->session_out_seq_no += $in;
        //$this->API->logger->logger("OUT: $value + $in = ".$this->session_out_seq_no);
        return $value * 2 + $in;
    }

    public function checkInSeqNo($current_msg_id)
    {
        $type = isset($this->incoming_messages[$current_msg_id]['content']['_']) ? $this->incoming_messages[$current_msg_id]['content']['_'] : '-';
        if (isset($this->incoming_messages[$current_msg_id]['seq_no']) && ($seq_no = $this->generateInSeqNo($this->contentRelated($this->incoming_messages[$current_msg_id]['content']))) !== $this->incoming_messages[$current_msg_id]['seq_no']) {
            $this->API->logger->logger('SECURITY WARNING: Seqno mismatch (should be '.$seq_no.', is '.$this->incoming_messages[$current_msg_id]['seq_no'].', '.$type.')', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        } elseif (isset($seq_no)) {
            $this->API->logger->logger('Seqno OK (should be '.$seq_no.', is '.$this->incoming_messages[$current_msg_id]['seq_no'].', '.$type.')', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        }
    }

    public function generateInSeqNo($contentRelated)
    {
        $in = $contentRelated ? 1 : 0;
        $value = $this->session_in_seq_no;
        $this->session_in_seq_no += $in;
        //$this->API->logger->logger("IN: $value + $in = ".$this->session_in_seq_no);
        return $value * 2 + $in;
    }

    public function contentRelated($method)
    {
        $method = \is_array($method) && isset($method['_']) ? $method['_'] : $method;

        return \is_string($method) ? !\in_array($method, MTProto::NOT_CONTENT_RELATED) : true;
    }
}
