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
 * Manages updates.
 */
trait UpdateHandler
{
    public $updates_state = [];

    public function update_state($data, $chat_id = 0)
    {
        if (!isset($this->updates_state[$chat_id])) {
            $this->updates_state[$chat_id] = ['date' => 0, 'pts' => 0, 'seq' => 0];
        }

        $this->updates_state[$chat_id]['pts'] = (!isset($data['pts']) || $data['pts'] == 0) ? $this->updates_state[$chat_id]['pts'] : $data['pts'];
        $this->updates_state[$chat_id]['seq'] = (!isset($data['seq']) || $data['seq'] == 0) ? $this->updates_state[$chat_id]['seq'] : $data['seq'];
        $this->updates_state[$chat_id]['date'] = (!isset($data['date']) || $data['date'] < $this->updates_state[$chat_id]['date']) ? $this->updates_state[$chat_id]['date'] : $data['date'];
    }

    public function get_updates_state()
    {
        if (empty($this->updates_state)) {
            return $this->update_state($this->method_call('updates.getState'));
        }
        $difference = $this->method_call('updates.getDifference', ['pts' => $this->updates_state['pts'], 'date' => $this->updates_state['date'], 'qts' => -1]);
        switch ($difference['_']) {
            case 'updates.differenceEmpty':
                $this->update_state($difference);
                break;
            case 'updates.difference':
                $this->add_users($difference['users']);
                $this->add_chats($difference['chats']);
                $this->handle_update_messages($difference['new_messages']);
                $this->handle_multiple_update($difference['other_updates']);
                $this->update_state($difference['state']);
                break;
            case 'updates.differenceSlice':
                $this->add_users($difference['users']);
                $this->add_chats($difference['chats']);
                $this->handle_update_messages($difference['new_messages']);
                $this->handle_multiple_update($difference['other_updates']);
                $this->update_state($difference['intermediate_state']);
                $this->get_updates_state();
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference));
                break;
        }
    }

    public function handle_updates($updates)
    {
        switch ($updates['_']) {
            case 'updatesTooLong':
                $this->get_updates_state();
                break;
            case 'updateShortMessage':
            case 'updateShortChatMessage':
            case 'updateShortSentMessage':
                $update = ['_' => 'updateNewMessage'];
                $this->handle_update_messages($update, ['date' => $updates['date']]);
                break;
            case 'updateShort':
                $this->handle_update($updates['update'], ['date' => $updates['date']]);
                break;
            case 'updatesCombined':
                $this->add_users($updates['users']);
                $this->add_chats($updates['chats']);
                $this->handle_multiple_update($updates['updates']);
                break;
            case 'updates':
                $this->add_users($updates['users']);
                $this->add_chats($updates['chats']);
                $this->handle_multiple_update($updates['updates']);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update received: '.var_export($updates));
                break;
        }
    }

    public function handle_update($update)
    {
        var_dump($update);
    }

    public function handle_multiple_update($updates)
    {
        foreach ($updates as $update) {
            $this->handle_update($update);
        }
    }

    public function handle_update_messages($messages)
    {
        foreach ($messages as $message) {
            $this->handle_update(['_' => 'updateNewMessage', 'message' => $message, 'pts' => $this->updates_state[0]['pts'], 'pts_count' => 0]);
        }
    }
}
