<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages sequence number.
 */
trait SeqNoHandler
{
    public function generate_seq_no($content_related = true)
    {
        $in = $content_related ? 1 : 0;
        $value = $this->datacenter->seq_no;
        $this->datacenter->seq_no += $in;

        return ($value * 2) + $in;
    }

    public function get_in_seq_no($chat)
    {
        return count($this->secret_chats[$chat]['incoming']);
    }

    public function get_out_seq_no($chat)
    {
        return count($this->secret_chats[$chat]['outgoing']);
    }
}
