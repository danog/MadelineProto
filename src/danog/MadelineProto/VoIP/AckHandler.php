<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\VoIP;

trait AckHandler
{
    public function seqgt($s1, $s2)
    {
        return $s1 > $s2;
    }
    public function received_packet($last_ack_id, $packet_seq_no, $ack_mask)
    {
        if ($this->seqgt($packet_seq_no, $this->session_in_seq_no)) {
            $diff = $packet_seq_no - $this->session_in_seq_no;
            if ($diff > 31) {
                $this->received_timestamp_map = \array_fill(0, 32, 0);
            } else {
                $remaining = 32-$diff;
                for ($x = 0; $x < $remaining; $x++) {
                    $this->received_timestamp_map[$diff+$x] = $this->received_timestamp_map[$x];
                }
                for ($x = 1; $x < $diff; $x++) {
                    $this->received_timestamp_map[$x] = 0;
                }
                $this->received_timestamp_map[0] = \microtime(true);
            }
            $this->session_in_seq_no = $packet_seq_no;
        } elseif (($diff = $this->session_in_seq_no - $packet_seq_no) < 32) {
            if (!$this->received_timestamp_map[$diff]) {
                \danog\MadelineProto\Logger::log("Got duplicate $packet_seq_no");
                return false;
            }
            $this->received_timestamp_map[$diff] = \microtime(true);
        } else {
            \danog\MadelineProto\Logger::log("Packet $packet_seq_no is out of order and too late");
            return false;
        }
        if ($this->seqgt($last_ack_id, $this->session_out_seq_no)) {
            $diff = $last_ack_id - $this->session_out_seq_no;
            if ($diff > 31) {
                $this->remote_ack_timestamp_map = \array_fill(0, 32, 0);
            } else {
                $remaining = 32-$diff;
                for ($x = 0; $x < $remaining; $x++) {
                    $this->remote_ack_timestamp_map[$diff+$x] = $this->remote_ack_timestamp_map[$x];
                }
                for ($x = 1; $x < $diff; $x++) {
                    $this->remote_ack_timestamp_map[$x] = 0;
                }
                $this->remote_ack_timestamp_map[0] = \microtime(true);
            }
            $this->session_out_seq_no = $last_ack_id;

            for ($x = 1; $x < 32; $x++) {
                if (!$this->remote_ack_timestamp_map[$x] && ($ack_mask >> 32-$x) & 1) {
                    $this->remote_ack_timestamp_map[$x] = \microtime(true);
                }
            }
        }
        return true;
    }
}
