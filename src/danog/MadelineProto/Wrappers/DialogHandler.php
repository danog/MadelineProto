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

namespace danog\MadelineProto\Wrappers;

trait DialogHandler
{
    public function get_dialogs($force = false)
    {
        if (!isset($this->dialog_params['offset_date']) || $force) {
            $this->dialog_params = ['limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' =>  ['_' => 'inputPeerEmpty']];
        }
        $this->getting_state = true;
        $res = ['dialogs' => [0]];
        $datacenter = $this->datacenter->curdc;
        $count = 0;
        while (count($res['dialogs'])) {
            \danog\MadelineProto\Logger::log(['Getting dialogs...']);
            $res = $this->method_call('messages.getDialogs', $this->dialog_params, ['datacenter' => $datacenter, 'FloodWaitLimit' => 100]);
            $count += count($res['dialogs']);
            $old_params = $this->dialog_params;
            $this->dialog_params['offset_date'] = end($res['messages'])['date'];
            $this->dialog_params['offset_peer'] = end($res['dialogs'])['peer'];
            $this->dialog_params['offset_id'] = end($res['messages'])['id'];
            if ($this->dialog_params === $old_params) {
                break;
            }
        }

        $this->getting_state = false;
    }
}
