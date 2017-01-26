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
 * Manages updates.
 */
trait UpdateHandler
{
    public $updates_state = ['pending_seq_updates' => [], 'pending_pts_updates' => [], 'sync_loading' => true, 'seq' => 0, 'pts' => 0, 'date' => 0];
    public $channels_state = [];
    public $updates = [];
    public $updates_key = 0;
    private $getting_state = false;
    public $full_chats;

    public function full_chat_last_updated($id)
    {
        $id = $this->get_info($id)['bot_api_id'];

        return isset($this->full_chats[$id]['last_update']) ? $this->full_chats[$id]['last_update'] : 0;
    }

    public function pwr_update_handler($update)
    {
        if (isset($update['message']['to_id']) && time() - $this->full_chat_last_updated($update['message']['to_id']) <= 600) {
            try {
                $full_chat = $this->get_pwr_chat($update['message']['to_id']);
                $full_chat['last_update'] = time();
                $this->full_chats[$full_chat['id']] = $full_chat;
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            }
        }
        if (isset($update['message']['from_id']) && time() - $this->full_chat_last_updated($update['message']['from_id']) <= 600) {
            try {
                $full_chat = $this->get_pwr_chat($update['message']['from_id']);
                $full_chat['last_update'] = time();
                $this->full_chats[$full_chat['id']] = $full_chat;
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            }
        }
        if (isset($this->settings['pwr']['update_handler'])) {
            $this->settings['pwr']['update_handler']($update);
        }
    }

    public function get_updates_update_handler($update)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $this->updates[$this->updates_key++] = $update;
        //\danog\MadelineProto\Logger::log('Stored ', $update);
    }

    public function get_updates($params = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $time = microtime(true);
        $this->force_get_updates_difference();
        $default_params = ['offset' => 0, 'limit' => null, 'timeout' => 0];
        foreach ($default_params as $key => $default) {
            if (!isset($params[$key])) {
                $params[$key] = $default;
            }
        }
        $params['timeout'] = (int) ($params['timeout'] * 1000000 - (microtime(true) - $time));
        usleep($params['timeout'] > 0 ? $params['timeout'] : 0);
        if (empty($this->updates)) {
            return [];
        }
        if ($params['offset'] < 0) {
            $params['offset'] = array_reverse(array_keys($this->updates))[abs($params['offset']) - 1];
        }
        $updates = [];
        foreach ($this->updates as $key => $value) {
            if ($params['offset'] > $key) {
                unset($this->updates[$key]);
            } elseif ($params['limit'] == null || count($updates) < $params['limit']) {
                $updates[] = ['update_id' => $key, 'update' => $value];
            }
        }

        return $updates;
    }

    public function &get_channel_state($channel, $pts = 0)
    {
        if (!isset($this->channels_state[$channel])) {
            $this->channels_state[$channel] = ['pts' => $pts, 'pending_pts_updates' => [], 'sync_loading' => false];
        }

        return $this->channels_state[$channel];
    }

    public function set_channel_state($channel, $data)
    {
        $this->get_channel_state($channel)['pts'] = (!isset($data['pts']) || $data['pts'] == 0) ? $this->get_channel_state($channel)['pts'] : $data['pts'];
    }

    public function get_channel_difference($channel)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if (!$this->get_channel_state($channel)['sync_loading']) {
            $this->get_channel_state($channel)['sync_loading'] = true;
            $this->get_channel_state($channel)['pending_pts_updates'] = [];
        }
        try {
            $input = $this->get_info('channel#'.$channel)['InputChannel'];
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }
        $difference = $this->method_call('updates.getChannelDifference', ['channel' => $input, 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $this->get_channel_state($channel)['pts'], 'limit' => 30]);
        \danog\MadelineProto\Logger::log('Got '.$difference['_'], \danog\MadelineProto\Logger::VERBOSE);
        switch ($difference['_']) {
            case 'updates.channelDifferenceEmpty':
                $this->set_channel_state($channel, $difference);
                break;
            case 'updates.channelDifference':
                $this->handle_update_messages($difference['new_messages'], $channel);
                $this->handle_multiple_update($difference['other_updates'], [], $channel);
                $this->set_channel_state($channel, $difference);
                if (!$difference['final']) {
                    unset($difference);
                    unset($input);
                    $this->get_channel_difference($channel);
                }
                break;
            case 'updates.channelDifferenceTooLong':
                //unset($this->channels_state[$channel]);
                //unset($this->chats[$this->get_info('channel#'.$channel)['bot_api_id']]);
                $this->handle_update_messages($difference['messages'], $channel);
                $this->set_channel_state($channel, $difference);
                unset($difference);
                unset($input);
                $this->get_channel_difference($channel);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                break;
        }
        $this->get_channel_state($channel)['sync_loading'] = false;
    }

    public function set_update_state($data)
    {
        $this->get_update_state()['pts'] = (!isset($data['pts']) || $data['pts'] == 0) ? $this->get_update_state()['pts'] : $data['pts'];
        $this->get_update_state()['seq'] = (!isset($data['seq']) || $data['seq'] == 0) ? $this->get_update_state()['seq'] : $data['seq'];
        $this->get_update_state()['date'] = (!isset($data['date']) || $data['date'] < $this->get_update_state()['date']) ? $this->get_update_state()['date'] : $data['date'];

        return $this->get_update_state();
    }

    public function &get_update_state()
    {
        return $this->updates_state;
    }

    public function force_get_updates_difference()
    {
        if (!$this->get_update_state()['sync_loading']) {
            $this->get_updates_difference();
        }
    }

    public function get_updates_difference()
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if (!$this->get_update_state()['sync_loading']) {
            $this->get_update_state()['sync_loading'] = true;
            $this->get_update_state()['pending_pts_updates'] = [];
            $this->get_update_state()['pending_seq_updates'] = [];
        }

        $difference = $this->method_call('updates.getDifference', ['pts' => $this->get_update_state()['pts'], 'date' => $this->get_update_state()['date'], 'qts' => -1]);
        \danog\MadelineProto\Logger::log('Got '.$difference['_'], \danog\MadelineProto\Logger::VERBOSE);
        switch ($difference['_']) {
            case 'updates.differenceEmpty':
                $this->set_update_state($difference);
                break;
            case 'updates.difference':
                $this->handle_multiple_update($difference['other_updates']);
                $this->handle_update_messages($difference['new_messages']);
                $this->set_update_state($difference['state']);
                break;
            case 'updates.differenceSlice':
                $this->handle_multiple_update($difference['other_updates']);
                $this->handle_update_messages($difference['new_messages']);
                $this->set_update_state($difference['intermediate_state']);
                unset($difference);
                $this->get_updates_difference();
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                break;
        }
        $this->get_update_state()['sync_loading'] = false;
    }

    public function get_updates_state()
    {
        $this->updates_state['sync_loading'] = false;
        $this->getting_state = true;
        $this->set_update_state($this->method_call('updates.getState'));
        $this->getting_state = false;
        $this->handle_pending_updates();
    }

    public function handle_update($update, $options = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        \danog\MadelineProto\Logger::log('Handling an update of type '.$update['_'].'...', \danog\MadelineProto\Logger::VERBOSE);
        //var_dump($update, $options);

        $channel_id = false;
        switch ($update['_']) {
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                if ($update['message']['_'] == 'messageEmpty') {
                    \danog\MadelineProto\Logger::log('Got message empty, not saving', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    return false;
                }
                $channel_id = $update['message']['to_id']['channel_id'];
                break;
            case 'updateDeleteChannelMessages':
                $channel_id = $update['channel_id'];
                break;
            case 'updateChannelTooLong':
                $channel_id = $update['channel_id'];
                \danog\MadelineProto\Logger::log('Got channel too long update, getting difference...', \danog\MadelineProto\Logger::VERBOSE);
                if (!isset($this->channels_state[$channel_id]) && !isset($update['pts'])) {
                    \danog\MadelineProto\Logger::log('I do not have the channel in the states and the pts is not set.', \danog\MadelineProto\Logger::ERROR);

                    return;
                }
                break;
        }
        if ($channel_id === false) {
            $cur_state = &$this->get_update_state();
        } else {
            $cur_state = &$this->get_channel_state($channel_id, (isset($update['pts']) ? $update['pts'] : 0) - (isset($update['pts_count']) ? $update['pts_count'] : 0));
        }

        if ($cur_state['sync_loading']) {
            \danog\MadelineProto\Logger::log('Sync loading, not handling update', \danog\MadelineProto\Logger::NOTICE);

            return false;
        }

        switch ($update['_']) {
            case 'updateChannelTooLong':
                $this->get_channel_difference($channel_id);

                return false;
            case 'updateNewMessage':
            case 'updateEditMessage':
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                if ((isset($update['message']['from_id']) && !$this->peer_isset($update['message']['from_id'])) ||
                    !$this->peer_isset($update['message']['to_id']) ||
                    (isset($update['message']['via_bot_id']) && !$this->peer_isset($update['message']['via_bot_id'])) ||
                    (isset($update['message']['entities']) && !$this->entities_peer_isset($update['message']['entities'])) ||
                    (isset($update['message']['fwd_from']) && !$this->fwd_peer_isset($update['message']['fwd_from']))) {
                    \danog\MadelineProto\Logger::log('Not enough data for message update, getting difference...', \danog\MadelineProto\Logger::VERBOSE);

                    if ($channel_id !== false && $this->peer_isset('-100'.$channel_id)) {
                        $this->get_channel_difference($channel_id);
                    } else {
                        $this->force_get_updates_difference();
                    }

                    return false;
                }
                break;
            default:
                if ($channel_id !== false && !$this->peer_isset('channel#'.$channel_id)) {
                    \danog\MadelineProto\Logger::log('Skipping update, I do not have the channel id '.$channel_id, \danog\MadelineProto\Logger::ERROR);

                    return false;
                }
                break;
        }
        $pop_pts = false;
        $pop_seq = false;

        if (isset($update['pts'])) {
            $new_pts = $cur_state['pts'] + (isset($update['pts_count']) ? $update['pts_count'] : 0);
            if ($new_pts < $update['pts']) {
                \danog\MadelineProto\Logger::log('Pts hole. current pts: '.$cur_state['pts'].', pts count: '.(isset($update['pts_count']) ? $update['pts_count'] : 0).', new pts: '.$new_pts.' < update pts: '.$update['pts'].', channel id: '.$channel_id, \danog\MadelineProto\Logger::ERROR);

                $this->cur_state['pending_pts_updates'][] = $update;

                if ($channel_id !== false && $this->peer_isset('-100'.$channel_id)) {
                    $this->get_channel_difference($channel_id);
                } else {
                    $this->get_updates_difference();
                }

                return false;
            }
            if ($update['pts'] > $cur_state['pts']) {
                $cur_state['pts'] = $update['pts'];
                $pop_pts = true;
            } elseif (isset($update['pts_count'])) {
                \danog\MadelineProto\Logger::log('Duplicate update. current pts: '.$cur_state['pts'].' + pts count: '.(isset($update['pts_count']) ? $update['pts_count'] : 0).' = new pts: '.$new_pts.'. update pts: '.$update['pts'].' <= current pts '.$cur_state['pts'].', channel id: '.$channel_id, \danog\MadelineProto\Logger::ERROR);

                return false;
            }
            if ($channel_id !== false && isset($options['date']) && $this->get_update_state()['date'] < $options['date']) {
                $this->get_update_state()['date'] = $options['date'];
            }
        } elseif ($channel_id === false && isset($options['seq']) && $options['seq'] > 0) {
            $seq = $options['seq'];
            $seq_start = isset($options['seq_start']) ? $options['seq_start'] : $options['seq'];
            if ($seq_start != $cur_state['seq'] + 1 && $seq_start > $cur_state['seq']) {
                \danog\MadelineProto\Logger::log('Seq hole. seq_start: '.$seq_start.' != cur seq: '.$cur_state['seq'].' + 1', \danog\MadelineProto\Logger::ERROR);

                if (!isset($cur_state['pending_seq_updates'][$seq_start])) {
                    $cur_state['pending_seq_updates'][$seq_start] = ['seq' => $seq, 'date' => $options['date'], 'updates' => []];
                }
                $cur_state['pending_seq_updates'][$seq_start]['updates'][] = $update;
                $this->get_updates_difference();

                return false;
            }

            if ($cur_state['seq'] != $seq) {
                $cur_state['seq'] = $seq;
                if (isset($options['date']) && $cur_state['date'] < $options['date']) {
                    $cur_state['date'] = $options['date'];
                }
                $pop_seq = true;
            }
        }

        $this->save_update($update);

        if ($pop_pts) {
            $this->pop_pending_pts_update($channel_id);
        } elseif ($pop_seq) {
            $this->pop_pending_seq_update();
        }
    }

    public function pop_pending_seq_update()
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $next_seq = $this->get_update_state()['seq'] + 1;
        if (empty($this->get_update_state()['pending_seq_updates'][$next_seq]['updates'])) {
            return false;
        }
        foreach ($this->get_update_state()['pending_seq_updates'][$next_seq]['updates'] as $update) {
            $this->save_update($update);
        }
        $this->get_update_state()['seq'] = $this->get_update_state()['pending_seq_updates'][$next_seq]['seq'];
        if (isset($this->get_update_state()['pending_seq_updates'][$next_seq]['date']) && $this->get_update_state()['date'] < $this->get_update_state()['pending_seq_updates'][$next_seq]['date']) {
            $this->get_update_state()['date'] = $this->get_update_state()['pending_seq_updates'][$next_seq]['date'];
        }
        unset($this->get_update_state()['pending_seq_updates'][$next_seq]);
        $this->pop_pending_seq_update();

        return true;
    }

    public function pop_pending_pts_update($channel_id)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($channel_id === false) {
            $cur_state = &$this->get_update_state();
        } else {
            $cur_state = &$this->get_channel_state($channel_id);
        }
        if (empty($cur_state['pending_pts_updates'])) {
            return false;
        }
        sort($cur_state['pending_pts_updates']);
        $cur_pts = $cur_state['pts'];
        $good_pts = false;
        $good_index = false;
        foreach ($cur_state['pending_pts_updates'] as $i => $update) {
            $cur_pts += $update['pts_count'];
            if ($cur_pts >= $update['pts']) {
                $good_pts = $update['pts'];
                $good_index = $i;
            }
        }
        if (!$good_pts) {
            return false;
        }
        $cur_state['pts'] = $good_pts;
        for ($i = 0; $i <= $good_index; $i++) {
            $this->save_update($cur_state['pending_pts_updates'][$i]);
        }
        array_splice($cur_state['pending_pts_updates'], 0, $good_index + 1);
    }

    public function handle_multiple_update($updates, $options = [], $channel = false)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($channel === false) {
            foreach ($updates as $update) {
                switch ($update['_']) {
                    case 'updateChannelTooLong':
                    case 'updateNewChannelMessage':
                    case 'updateEditChannelMessage':
                        $this->handle_update($update, $options);
                        continue 2;
                }
                $this->save_update($update);
            }
        } else {
            foreach ($updates as $update) {
                $this->save_update($update);
            }
        }
    }

    public function handle_update_messages($messages, $channel = false)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        foreach ($messages as $message) {
            $this->save_update(['_' => $channel == false ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => $channel == false ? $this->get_update_state()['pts'] : $this->get_channel_state($channel)['pts'], 'pts_count' => 0]);
        }
    }

    public function save_update($update)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if (isset($update['message']['from_id']) && $update['message']['from_id'] == $this->datacenter->authorization['user']['id']) {
            $update['message']['out'] = true;
        }
        \danog\MadelineProto\Logger::log('Saving an update of type '.$update['_'].'...', \danog\MadelineProto\Logger::VERBOSE);
        if (isset($this->settings['pwr']['strict']) && $this->settings['pwr']['strict']) {
            $this->pwr_update_handler($update);
        } else {
            $this->settings['updates']['callback']($update);
        }
    }
}
