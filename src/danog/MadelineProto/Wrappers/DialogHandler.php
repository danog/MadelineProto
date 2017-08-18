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
    public function get_dialogs($force = true)
    {
        if (!isset($this->dialog_params['offset_date']) || $force || is_null($this->dialog_params['offset_date'])) {
            $this->dialog_params = ['limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' =>  ['_' => 'inputPeerEmpty'], 'count' => 0];
        }
        $this->updates_state['sync_loading'] = true;
        $res = ['dialogs' => [0], 'count' => 1];
        $datacenter = $this->datacenter->curdc;
        $peers = [];
        while ($this->dialog_params['count'] < $res['count']) {
            \danog\MadelineProto\Logger::log(['Getting dialogs...']);
            $res = $this->method_call('messages.getDialogs', $this->dialog_params, ['datacenter' => $datacenter, 'FloodWaitLimit' => 100]);
            foreach ($res['dialogs'] as $dialog) {
                if (!in_array($dialog['peer'], $peers)) {
                    $peers[] = $dialog['peer'];
                }
            }
            $this->dialog_params['count'] += count($res['dialogs']);
            $this->dialog_params['offset_date'] = end($res['messages'])['date'];
            $this->dialog_params['offset_peer'] = end($res['dialogs'])['peer'];
            $this->dialog_params['offset_id'] = end($res['messages'])['id'];
            if (!isset($res['count'])) {
                break;
            }
        }

        $this->updates_state['sync_loading'] = false;

        return $peers;
    }
}
