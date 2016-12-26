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
    public $channels_state = [];

    public $updates = [];

    public function get_updates_update_handler($update) {
        if (count($this->updates) > $this->settings['updates']['updates_array_limit']) {
            array_shift($this->updates);
        }
        $this->updates[] = $update;
        \danog\MadelineProto\Logger::log('Stored ', $update);
    }

    public function get_updates($offset, $limit = null, $timeout = 0) {
        sleep($timeout);
        $this->get_updates_difference();
        $result = array_slice($this->updates, $offset, $limit, true);
        $updates = [];
        foreach ($result as $key => $value) {
            $updates[] = ['update_id' => $key, 'update' => $value];
            unset($this->updates[$key]);
        }
        return $updates;
    }

    public function &get_channel_state($channel, $pts = 0) {
        if (!isset($this->channels_state[$channel])) {
            $this->channels_state[$channel] = ['pts' => $pts, 'pop_pts' => [], 'pending_seq_updates' =>[]];
        }
        return $this->channels_state[$channel];
    }
    public function update_channel_state($channel, $data)
    {
        $this->get_channel_state($channel);

        $this->channels_state[$channel]['pts'] = (!isset($data['pts']) || $data['pts'] == 0) ? $this->get_channel_state($channel)['pts'] : $data['pts'];
    }

    public function get_channel_difference($channel)
    {
        $this->get_channel_state($channel);

        $difference = $this->method_call('updates.getChannelDifference', ['channel' => $this->get_info('channel#'.$channel)['inputType'], 'filter' => ['_' => 'channelMessagesFilterEmpty'],'pts' => $this->get_channel_state($channel)['pts'], 'limit' => 30]);
        switch ($difference['_']) {
            case 'updates.channelDifferenceEmpty':
                $this->update_channel_state($difference);
                break;
            case 'updates.difference':
                $this->handle_update_messages($difference['new_messages'], $channel);
                $this->handle_multiple_update($difference['other_updates']);
                $this->update_channel_state($difference);
                if (!$difference['final']) {
                    $this->get_channel_difference($channel);
                }
                break;
            case 'updates.differenceTooLong':
                unset($this->channels_state[$channel]);
                \danog\MadelineProto\Logger::log('Got updates.differenceTooLong: ', $difference);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference));
                break;
        }
    }
    public function update_state($data)
    {
        if (empty($this->updates_state)) {
            $this->updates_state = ['date' => 0, 'pts' => 0, 'seq' => 0, 'pending_seq_updates' => []];
        }

        $this->updates_state['pts'] = (!isset($data['pts']) || $data['pts'] == 0) ? $this->updates_state['pts'] : $data['pts'];
        $this->updates_state['seq'] = (!isset($data['seq']) || $data['seq'] == 0) ? $this->updates_state['seq'] : $data['seq'];
        $this->updates_state['date'] = (!isset($data['date']) || $data['date'] < $this->updates_state['date']) ? $this->updates_state['date'] : $data['date'];
    }


    public function get_updates_difference()
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
                $this->handle_update_messages($difference['new_messages']);
                $this->handle_multiple_update($difference['other_updates']);
                $this->update_state($difference['state']);
                break;
            case 'updates.differenceSlice':
                $this->handle_update_messages($difference['new_messages']);
                $this->handle_multiple_update($difference['other_updates']);
                $this->update_state($difference['intermediate_state']);
                $this->get_updates_difference();
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
                $this->get_updates_difference();
                break;
            case 'updateShortMessage':
            case 'updateShortChatMessage':
//            case 'updateShortSentMessage':
                $fromID = isset($updates['from_id']) ? $updates['from_id'] : ($updates['out'] ? $this->datacenter->authorization['user']['id'] : $updates['user_id']);
                $toID = isset($updates['chat_id'])
                    ? $updates['chat_id']
                    : ($updates['out'] ? $updates['user_id'] : $this->datacenter->authorization['user']['id']);

                $message = $updates;
                $message['_'] = 'message';
                $message['to_id'] = $toID;
                $message['from_id'] = $this->get_info($fromID)['Peer'];
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->handle_update($update, ['date' => $updates['date']]);
                break;
            case 'updateShort':
                $this->handle_update($updates['update'], ['date' => $updates['date']]);
                break;
            case 'updatesCombined':
                $this->add_users($updates['users']);
                $this->add_chats($updates['chats']);
                $this->handle_multiple_update($updates['updates'], ['date' => $updates['date'], 'seq' => $updates['seq'], 'seq_start' => $updates['seq_start']]);
                break;
                
            case 'updates':
                $this->add_users($updates['users']);
                $this->add_chats($updates['chats']);
                $this->handle_multiple_update($updates['updates'], ['date' => $updates['date'], 'seq' => $updates['seq']]);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update received: '.var_export($updates));
                break;
        }
    }

    public function handle_update($update, $options = [])
    {
        $channel_id = false;
        switch ($update['_']) {
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                $channel_id = $update['message']['to_id']['channel_id'];
                break;
            case 'updateDeleteChannelMessages':
                $channel_id = $update['channel_id'];
                break;
            case 'updateChannelTooLong':
                $channel_id = $update['channel_id'];
                if (!isset($this->channels_state[$channel_id])) {
                    return false;
                }
                return $this->get_channel_difference($channel_id);
                break;
        }

        $this->save_update($update);
        /*
        switch ($update['_']) {
            case 'updateNewMessage':
            case 'updateEditMessage':
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                $message = $update['message'];
                if (isset($message['from_id']) && !isset($this->chats[$message['from_id']]) ||
                    isset($message['fwd_from']['from_id']) && !isset($this->chats[$message['fwd_from']['from_id']]) ||
                    isset($message['fwd_from']['channel_id']) && !isset($this->chats[(int)('-100'.$message['fwd_from']['channel_id'])]) ||
                    !isset($this->get_info($message['to_id'])['bot_api_info'])) {

                    \danog\MadelineProto\Logger::log('Not enough data for message update');
                    
                    if ($channel_id !== false && isset($this->chats[$channel_id])) {
                        $this->get_channel_difference($channel_id);
                    } else {
                        $this->get_updates_difference();
                    }
                    return false;
                    
                }
                break;
            default:
                if ($channel_id !== false && isset($this->chats[$channel_id])) {
                    return false;
                }
                break;
        }
        $pop_pts = false;
        if ($update['pts']) {
            $new_pts = $cur_state['pts'] + (isset($update['pts_count']) ? $update['pts_count'] : 0);
            if ($new_pts < $update['pts']) {
                \danog\MadelineProto\Logger::log('Pts hole', $cur_state, $update, $this->get_info($channel_id));
                
                $this->cur_state['pop_pts'][] = $update;

                if ($channel_id && isset($this->chats[$channel_id])) {
                    $this->get_channel_difference($channel_id);
                } else {
                    $this->get_updates_difference();
                }

                return false;
            }
            if ($new_pts > $update['pts']) {
                $cur_state['pts'] = $update['pts'];
                $pop_pts = true;
            } else if ($update['pts_count']) {
                return false;
            }
        } else if (!$channel_id && isset($options['seq']) && $options['seq'] > 0) {
            $seq = $options['seq'];
            $seq_start = isset($options['seq_start']) ? $options['seq_start'] : $options['seq'];


            if ($seq_start != $cur_state['seq'] + 1) {
                if ($seq_start > $cur_state['seq']) {
                    \danog\MadelineProto\Logger::('Seq hole', $cur_state);

                    if (!isset($cur_state['pending_seq_updates'][$seq_start])) {
                        $cur_state['pending_seq_updates'][$seq_start] = ['seq' => $seq, 'date': $options['date'], 'updates' => []];
                    }
                    $cur_state['pending_seq_updates'][$seq_start][] = $update;

                    if (!$cur_state.syncPending.seqAwaiting ||
                    $cur_state.syncPending.seqAwaiting < $seq_start) {
                    $cur_state.syncPending.seqAwaiting = $seq_start
                }
                return false;
            }
        }


        if (curState.seq != seq) {
          curState.seq = seq
          if (options.date && curState.date < options.date) {
            curState.date = options.date
          }
          popSeq = true
        }
      }

      saveUpdate(update)

      if (popPts) {
        popPendingPtsUpdate(channelID)
      }
      else if (popSeq) {
        popPendingSeqUpdate()
}*/
    }

    public function handle_multiple_update($updates, $options = [])
    {
        foreach ($updates as $update) {
            $this->handle_update($update, $options);
        }
    }

    public function handle_update_messages($messages, $channel = false)
    {
        foreach ($messages as $message) {
            $this->save_update(['_' => $channel == false ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => $channel == false ? $this->updates_state['pts'] : $this->get_channel_state($channel)['pts'], 'pts_count' => 0]);
        }
    }

    public function save_update($update) {
        $this->settings['updates']['callback']($update);
    }
}
