<?php

/**
 * AckHandler module.
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

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages acknowledgement of messages.
 */
trait AckHandler
{
    public function ack_outgoing_message_id($message_id, $datacenter)
    {
        // The server acknowledges that it received my message
        if (!isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id])) {
            $this->logger->logger("WARNING: Couldn't find message id ".$message_id.' in the array of outgoing messages. Maybe try to increase its size?', \danog\MadelineProto\Logger::WARNING);

            return false;
        }
        //$this->logger->logger("Ack-ed ".$this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['_']." with message ID $message_id on DC $datacenter");
        /*
        if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['body'])) {
            unset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['body']);
        }
        if (isset($this->datacenter->sockets[$datacenter]->new_outgoing[$message_id])) {
            unset($this->datacenter->sockets[$datacenter]->new_outgoing[$message_id]);
        }*/
        return true;
    }

    public function got_response_for_outgoing_message_id($message_id, $datacenter)
    {
        // The server acknowledges that it received my message
        if (!isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id])) {
            $this->logger->logger("WARNING: Couldn't find message id ".$message_id.' in the array of outgoing messages. Maybe try to increase its size?', \danog\MadelineProto\Logger::WARNING);

            return false;
        }
        if (isset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['body'])) {
            unset($this->datacenter->sockets[$datacenter]->outgoing_messages[$message_id]['body']);
        }
        if (isset($this->datacenter->sockets[$datacenter]->new_outgoing[$message_id])) {
            unset($this->datacenter->sockets[$datacenter]->new_outgoing[$message_id]);
        }

        return true;
    }

    public function ack_incoming_message_id($message_id, $datacenter)
    {
        // I let the server know that I received its message
        if (!isset($this->datacenter->sockets[$datacenter]->incoming_messages[$message_id])) {
            $this->logger->logger("WARNING: Couldn't find message id ".$message_id.' in the array of incoming messages. Maybe try to increase its size?', \danog\MadelineProto\Logger::WARNING);
        }
        /*if ($this->datacenter->sockets[$datacenter]->temp_auth_key['id'] === null || $this->datacenter->sockets[$datacenter]->temp_auth_key['id'] === "\0\0\0\0\0\0\0\0") {
        // || (isset($this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['ack']) && $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['ack'])) {
        return;
        }*/
        $this->datacenter->sockets[$datacenter]->ack_queue[$message_id] = $message_id;

        return true;
    }
}
