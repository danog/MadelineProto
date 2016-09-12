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

    public function check_message_id($new_message_id, $outgoing)
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
            $this->outgoing_message_ids[] = $new_message_id;
            if (count($this->outgoing_message_ids) > $this->settings['authorization']['message_ids_limit']) {
                array_shift($this->outgoing_message_ids);
            }
        } else {
            if ($new_message_id % 4 != 1 && $new_message_id % 4 != 3) {
                throw new Exception('message id mod 4 != 1 or 3');
            }
            foreach ($this->incoming_message_ids as $message_id) {
                if ($new_message_id <= $message_id) {
                    throw new Exception('Given message id ('.$new_message_id.') is lower than or equal than the current limit ('.$message_id.').');
                }
            }
            $this->incoming_message_ids[] = $new_message_id;
            if (count($this->incoming_message_ids) > $this->settings['authorization']['message_ids_limit']) {
                array_shift($this->incoming_message_ids);
            }
        }
    }

}
