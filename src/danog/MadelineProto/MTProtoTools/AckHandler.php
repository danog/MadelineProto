<?php
/*
Copyright 2016 Daniil Gentili
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
 * Manages acknowledgement of messages.
 */
class AckHandler extends \danog\MadelineProto\PrimeModule
{
    public function ack_outgoing_message_id($message_id)
    {
        // The server acknowledges that it received my message
        if (!isset($this->outgoing_messages[$message_id])) {
            throw new \danog\MadelineProto\Exception("Couldn't find message id ".$message_id.' in the array of outgoing messages. Maybe try to increase its size?');
        }
        $this->outgoing_messages[$message_id]['ack'] = true;
    }

    public function ack_incoming_message_id($message_id)
    {
        if ($this->datacenter->temp_auth_key['id'] === null || $this->datacenter->temp_auth_key['id'] == $this->string2bin('\x00\x00\x00\x00\x00\x00\x00\x00')) {
            return;
        }
        // I let the server know that I received its message
        if (!isset($this->incoming_messages[$message_id])) {
            throw new \danog\MadelineProto\Exception("Couldn't find message id ".$message_id.' in the array of incoming message ids. Maybe try to increase its size?');
        }
        $this->object_call('msgs_ack', ['msg_ids' => [$message_id]]);
        $this->incoming_messages[$message_id]['ack'] = true;
    }
}
