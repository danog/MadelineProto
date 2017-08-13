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
 * Manages acknowledgement of messages.
 */
trait AckHandler
{
    public function ack_outgoing_message_id($message_id, $datacenter)
    {
        // The server acknowledges that it received my message
        //var_dump($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]);
        if (!isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id])) {
            \danog\MadelineProto\Logger::log(["WARNING: Couldn't find message id ".$message_id.' in the array of outgoing messages. Maybe try to increase its size?'], \danog\MadelineProto\Logger::WARNING);

            return false;
        }

        return $this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['ack'] = true;
    }

    public function ack_incoming_message_id($message_id, $datacenter)
    {
        // I let the server know that I received its message
        if (!isset($this->datacenter->sockets[$datacenter]->incoming_messages[$message_id])) {
            \danog\MadelineProto\Logger::log(["WARNING: Couldn't find message id ".$message_id.' in the array of incomgoing messages. Maybe try to increase its size?'], \danog\MadelineProto\Logger::WARNING);
            //throw new \danog\MadelineProto\Exception("Couldn't find message id ".$message_id.' in the array of incoming message ids. Maybe try to increase its size?');
        }
        if ($this->datacenter->sockets[$datacenter]->temp_auth_key['id'] === null || $this->datacenter->sockets[$datacenter]->temp_auth_key['id'] === "\0\0\0\0\0\0\0\0" || (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['ack']) && $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['ack'])) {
            return;
        }

        $this->object_call('msgs_ack', ['msg_ids' => [$message_id]], ['datacenter' => $datacenter]);

        return $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['ack'] = true;
    }
}
