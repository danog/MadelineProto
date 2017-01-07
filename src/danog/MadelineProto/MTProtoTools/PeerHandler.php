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
 * Manages peers.
 */
trait PeerHandler
{
    public $chats = [];

    public function add_users($users)
    {
        foreach ($users as $key => $user) {
            switch ($user['_']) {
                case 'user':
                    if (!isset($this->chats[$user['id']]) || $this->chats[$user['id']]['user'] !== $user) {
                        //$this->method_call('users.getFullUser', ['id' => $user]);
                        $this->chats[$user['id']] = ['_' => 'userFull', 'user' => $user];
                        $this->should_serialize = true;
                    }
                case 'userEmpty':
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid user provided at key '.$key.': '.var_export($user, true));
                    break;
            }
        }
    }

    public function add_chats($chats)
    {
        foreach ($chats as $key => $chat) {
            switch ($chat['_']) {
                case 'chat':
                case 'chatEmpty':
                    if (!isset($this->chats[-$chat['id']]) || $this->chats[-$chat['id']]['chat'] !== $chat) {
                        //$this->method_call('messages.getFullChat', ['chat_id' => $chat['id']]);
                        $this->chats[-$chat['id']] = ['_' => 'chatFull', 'chat' => $chat];
                        $this->should_serialize = true;
                    }

                case 'chatForbidden':
                case 'channelEmpty':
                    break;
                case 'channel':
                    if (!isset($this->chats[(int) ('-100'.$chat['id'])]) || $this->chats[(int) ('-100'.$chat['id'])]['channel'] !== $chat) {
                        $this->chats[(int) ('-100'.$chat['id'])] = ['_' => 'channelFull', 'channel' => $chat];
                        $this->should_serialize = true;
                        //$this->method_call('channels.getFullChannel', ['channel' => $chat]);
                    }
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid chat provided at key '.$key.': '.var_export($chat, true));
                    break;
            }
        }
    }

    public function peer_isset($id)
    {
        try {
            return isset($this->chats[$this->get_info($id)['bot_api_id']]);
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }
    }

    public function entities_peer_isset($entities)
    {
        try {
            foreach ($entities as $entity) {
                if ($entity['_'] == 'messageEntityMentionName' || $entity['_'] == 'inputMessageEntityMentionName') {
                    if (!$this->peer_isset($entity['user_id'])) {
                        return false;
                    }
                }
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }

        return true;
    }

    public function fwd_peer_isset($fwd)
    {
        try {
            if (isset($fwd['user_id']) && !$this->peer_isset($fwd['user_id'])) {
                return false;
            }
            if (isset($fwd['channel_id']) && !$this->peer_isset('channel#'.$fwd['channel_id'])) {
                return false;
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }

        return true;
    }

    public function get_info($id, $recursive = true)
    {
        if (is_array($id)) {
            switch ($id['_']) {
                case 'inputUserSelf':
                case 'inputPeerSelf':
                    $id = $this->datacenter->authorization['user']['id'];
                    break;
                case 'user':
                    $id = $id['id'];
                    break;
                case 'userFull':
                    $id = $id['user']['id'];
                    break;
                case 'inputPeerUser':
                case 'inputUser':
                case 'peerUser':
                    $id = $id['user_id'];
                    break;

                case 'chat':
                    $id = -$id['id'];
                    break;
                case 'chatFull':
                    $id = -$id['chat']['id'];
                    break;
                case 'inputPeerChat':
                case 'peerChat':
                    $id = -$id['chat_id'];
                    break;

                case 'channel':
                    $id = '-100'.$id['id'];
                    break;
                case 'channelFull':
                    $id = '-100'.$id['channel']['id'];
                    break;

                case 'inputPeerChannel':
                case 'inputChannel':
                case 'peerChannel':
                    $id = '-100'.$id['channel_id'];
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($id, true));
                    break;
            }
        }

        if (preg_match('/^channel#/', $id)) {
            $id = str_replace('channel#', '-100', $id);
        }
        if (preg_match('/^chat#/', $id)) {
            $id = str_replace('chat#', '-', $id);
        }
        if (preg_match('/^user#/', $id)) {
            $id = str_replace('user#', '', $id);
        }

        if (is_numeric($id)) {
            $id = (int) $id;
            if (isset($this->chats[$id])) {
                return $this->gen_all($this->chats[$id]);
            }
            throw new \danog\MadelineProto\Exception("Couldn't find peer by provided chat id ".$id);
        }
        $id = str_replace('@', '', $id);
        foreach ($this->chats as $chat) {
            foreach (['user', 'chat', 'channel'] as $wut) {
                if (isset($chat[$wut]['username']) && strtolower($chat[$wut]['username']) == strtolower($id)) {
                    return $this->gen_all($chat);
                }
            }
        }
        if ($recursive) {
            $this->resolve_username($id);

            return $this->get_info($id, false);
        }
        throw new \danog\MadelineProto\Exception("Couldn't find peer by provided username ".$id);
    }

    public function gen_all($constructor)
    {
        $res = [$this->constructors->find_by_predicate($constructor['_'])['type'] => $constructor];
        switch ($constructor['_']) {
            case 'userFull':
                $res['User'] = $constructor['user'];
                if ($constructor['user']['self']) {
                    $res['InputPeer'] = ['_' => 'inputPeerSelf'];
                    $res['InputUser'] = ['_' => 'inputUserSelf'];
                } elseif (isset($constructor['user']['access_hash'])) {
                    $res['InputPeer'] = ['_' => 'inputPeerUser', 'user_id' => $constructor['user']['id'], 'access_hash' => $constructor['user']['access_hash']];
                    $res['InputUser'] = ['_' => 'inputUser', 'user_id' => $constructor['user']['id'], 'access_hash' => $constructor['user']['access_hash']];
                }
                $res['Peer'] = ['_' => 'peerUser', 'user_id' => $constructor['user']['id']];
                $res['user_id'] = $constructor['user']['id'];
                $res['bot_api_id'] = $constructor['user']['id'];
                break;
            case 'chatFull':
                $res['Chat'] = $constructor['chat'];
                $res['InputPeer'] = ['_' => 'inputPeerChat', 'chat_id' => $constructor['chat']['id']];
                $res['Peer'] = ['_' => 'peerChat', 'chat_id' => $constructor['chat']['id']];
                $res['chat_id'] = $constructor['chat']['id'];
                $res['bot_api_id'] = -$constructor['chat']['id'];
                break;
            case 'channelFull':
                $res['Channel'] = $constructor['channel'];
                if (isset($constructor['channel']['access_hash'])) {
                    $res['InputPeer'] = ['_' => 'inputPeerChannel', 'channel_id' => $constructor['channel']['id'], 'access_hash' => $constructor['channel']['access_hash']];
                    $res['InputChannel'] = ['_' => 'inputChannel', 'channel_id' => $constructor['channel']['id'], 'access_hash' => $constructor['channel']['access_hash']];
                }
                $res['Peer'] = ['_' => 'peerChannel', 'channel_id' => $constructor['channel']['id']];
                $res['channel_id'] = $constructor['channel']['id'];
                $res['bot_api_id'] = (int) ('-100'.$constructor['channel']['id']);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($constructor, true));
                break;
        }

        return $res;
    }

    public function resolve_username($username)
    {
        $res = $this->method_call('contacts.resolveUsername', ['username' => str_replace('@', '', $username)]);
        if ($res['_'] == 'contacts.resolvedPeer') {
            return $res;
        }
        throw new \danog\MadelineProto\Exception('resolve_username returned an unexpected constructor: '.var_export($username, true));
    }
}
