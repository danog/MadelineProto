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
 * Manages message ids.
 */
trait MsgIdHandler
{
    public function check_message_id($new_message_id, $outgoing, $container = false)
    {
        $min_message_id = ((int) ((time() + $this->datacenter->time_delta - 300) << 32));
        if ($min_message_id > $new_message_id) {
            \danog\MadelineProto\Logger::log('Given message id ('.$new_message_id.') is too old compared to the min value ('.$min_message_id.').', \danog\MadelineProto\Logger::WARNING);
        }
        /*
        if (((int) ((time() + $this->datacenter->time_delta + 30) << 32)) < $new_message_id) {
            throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is too new.');
        }
        */
        if ($outgoing) {
            if ($new_message_id % 4 != 0) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is not divisible by 4.');
            }
            $keys = array_keys($this->datacenter->outgoing_messages);
            asort($keys);
            if ($new_message_id <= end($keys)) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is lower than or equal than the current limit ('.end($keys).').', 1);
            }
            if (count($this->datacenter->outgoing_messages) > $this->settings['msg_array_limit']['outgoing']) {
                reset($this->datacenter->outgoing_messages);
                unset($this->datacenter->outgoing_messages[key($this->datacenter->outgoing_messages)]);
            }
            $this->datacenter->outgoing_messages[$new_message_id] = [];
        } else {
            if ($new_message_id % 4 != 1 && $new_message_id % 4 != 3) {
                throw new \danog\MadelineProto\Exception('message id mod 4 != 1 or 3');
            }
            $keys = array_keys($this->datacenter->incoming_messages);
            if ($container) {
                asort($keys);
                if ($new_message_id >= end($keys)) {
                    \danog\MadelineProto\Logger::log('WARNING: Given message id ('.$new_message_id.') is bigger than or equal than the current limit ('.end($keys).').', \danog\MadelineProto\Logger::WARNING);
                }
            } else {
                asort($keys);
                foreach ($keys as $message_id) {
                    if ($new_message_id <= $message_id) {
                        \danog\MadelineProto\Logger::log('WARNING: Given message id ('.$new_message_id.') is lower than or equal than the current limit ('.$message_id.').', \danog\MadelineProto\Logger::WARNING);
                    }
                }
            }
            if (count($this->datacenter->incoming_messages) > $this->settings['msg_array_limit']['incoming']) {
                reset($this->datacenter->incoming_messages);
                unset($this->datacenter->incoming_messages[key($this->datacenter->incoming_messages)]);
            }
            $this->datacenter->incoming_messages[$new_message_id] = [];
            ksort($this->datacenter->incoming_messages);
        }
    }

    public function generate_message_id()
    {
        $int_message_id = (int) ((time() + $this->datacenter->time_delta) << 32);
        /*
        $int_message_id = (int) (
            ((int) ($ms_time / 1000) << 32) |
            ($this->posmod($ms_time, 1000) << 22) |
            rand(0, 524288) << 2
        );
        */

        $keys = array_keys($this->datacenter->outgoing_messages);
        asort($keys);
        $keys = end($keys);
        if ($int_message_id <= $keys) {
            $int_message_id = $keys + 4;
        }
        $this->check_message_id($int_message_id, true);

        return $int_message_id;
    }
}
