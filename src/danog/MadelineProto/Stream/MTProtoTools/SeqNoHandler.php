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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\MTProtoTools;

/**
 * Manages sequence number.
 */
trait SeqNoHandler
{
    use \danog\MadelineProto\MTProtoTools\SeqNoHandler;

    public function generate_out_seq_no($content_related)
    {
        $in = $content_related ? 1 : 0;
        $value = $this->session_out_seq_no;
        $this->session_out_seq_no += $in;
        //$this->API->logger->logger("OUT: $value + $in = ".$this->session_out_seq_no);
        return $value * 2 + $in;
    }

    public function check_in_seq_no($current_msg_id)
    {
        $type = isset($this->incoming_messages[$current_msg_id]['content']['_']) ? $this->incoming_messages[$current_msg_id]['content']['_'] : '-';
        if (isset($this->incoming_messages[$current_msg_id]['seq_no']) && ($seq_no = $this->generate_in_seq_no($this->content_related($this->incoming_messages[$current_msg_id]['content']))) !== $this->incoming_messages[$current_msg_id]['seq_no']) {
            $this->API->logger->logger('SECURITY WARNING: Seqno mismatch (should be '.$seq_no.', is '.$this->incoming_messages[$current_msg_id]['seq_no'].', '.$type.')', \danog\MadelineProto\Logger::ERROR);
        } elseif (isset($seq_no)) {
            $this->API->logger->logger('Seqno OK (should be '.$seq_no.', is '.$this->incoming_messages[$current_msg_id]['seq_no'].', '.$type.')', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        }
    }

    public function generate_in_seq_no($content_related)
    {
        $in = $content_related ? 1 : 0;
        $value = $this->session_in_seq_no;
        $this->session_in_seq_no += $in;
        //$this->API->logger->logger("IN: $value + $in = ".$this->session_in_seq_no);
        return $value * 2 + $in;
    }
}
