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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\MTProto\IncomingMessage;

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
    public function checkInSeqNo(IncomingMessage $message): void
    {
        if ($message->hasSeqNo()) {
            $seq_no = $this->generateInSeqNo($message->isContentRelated());
            if ($seq_no !== $message->getSeqNo()) {
                if ($message->isContentRelated()) {
                    $this->session_in_seq_no -= 1;
                }
                $this->API->logger->logger('SECURITY WARNING: Seqno mismatch (should be '.$seq_no.', is '.$message->getSeqNo().", $message)", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            }
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
    public function contentRelated($method): bool
    {
        $method = \is_array($method) && isset($method['_']) ? $method['_'] : $method;
        return \is_string($method) ? !\in_array($method, [
            //'rpc_result',
            //'rpc_error',
            'rpc_drop_answer',
            'rpc_answer_unknown',
            'rpc_answer_dropped_running',
            'rpc_answer_dropped',
            'get_future_salts',
            'future_salt',
            'future_salts',
            'ping',
            'pong',
            'ping_delay_disconnect',
            'destroy_session',
            'destroy_session_ok',
            'destroy_session_none',
            //'new_session_created',
            'msg_container',
            'msg_copy',
            'gzip_packed',
            'http_wait',
            'msgs_ack',
            'bad_msg_notification',
            'bad_server_salt',
            'msgs_state_req',
            'msgs_state_info',
            'msgs_all_info',
            'msg_detailed_info',
            'msg_new_detailed_info',
            'msg_resend_req',
            'msg_resend_ans_req',
        ]) : true;
    }
}
