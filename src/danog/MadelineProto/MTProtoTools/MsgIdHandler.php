<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages message ids.
 */
class MsgIdHandler extends MessageHandler
{
    public function check_message_id($new_message_id, $outgoing, $container = false)
    {
        if (((int) ((time() + $this->timedelta - 300) * pow(2, 30)) * 4) > $new_message_id) {
            throw new Exception('Given message id ('.$new_message_id.') is too old.');
        }
        if (((int) ((time() + $this->timedelta + 30) * pow(2, 30)) * 4) < $new_message_id) {
            throw new Exception('Given message id ('.$new_message_id.') is too new.');
        }
        if ($outgoing) {
            if ($new_message_id % 4 != 0) {
                throw new Exception('Given message id ('.$new_message_id.') is not divisible by 4.');
            }
            $keys = array_keys($this->outgoing_messages);
            asort($keys);
            if ($new_message_id <= end($keys)) {
                throw new Exception('Given message id ('.$new_message_id.') is lower than or equal than the current limit ('.end($keys).').', 1);
            }
            $this->outgoing_messages[$new_message_id] = [];
            if (count($this->outgoing_messages) > $this->settings['msg_array_limit']['outgoing']) {
                array_shift($this->outgoing_messages);
            }
        } else {
            if ($new_message_id % 4 != 1 && $new_message_id % 4 != 3) {
                throw new Exception('message id mod 4 != 1 or 3');
            }
            $keys = array_keys($this->incoming_messages);
            if ($container) {
                asort($keys);
                if ($new_message_id >= end($keys)) {
                    throw new Exception('Given message id ('.$new_message_id.') is bigger than or equal than the current limit ('.end($keys).').');
                }
            } else {
                asort($keys);
                foreach ($keys as $message_id) {
                    if ($new_message_id <= $message_id) {
                        throw new Exception('Given message id ('.$new_message_id.') is lower than or equal than the current limit ('.$message_id.').');
                    }
                }
            }
            $this->incoming_messages[$new_message_id] = [];
            if (count($this->incoming_messages) > $this->settings['msg_array_limit']['incoming']) {
                array_shift($this->incoming_messages);
            }
            ksort($this->incoming_messages);
        }
    }

    public function generate_message_id()
    {
        $ms_time = (time() + $this->timedelta) * 1000;
        $int_message_id = (int) (
            ((int) ($ms_time / 1000) << 32) |
            ($this->posmod($ms_time, 1000) << 22) |
            rand(0, 524288) << 2
        );
        $keys = array_keys($this->outgoing_messages);
        asort($keys);
        if ($int_message_id <= end($keys)) {
            $int_message_id += 4;
        }
        $this->check_message_id($int_message_id, true);

        return $int_message_id;
    }
}
