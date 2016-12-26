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
                    if (!isset($this->chats[$user['id']])) {
                        $this->chats[$user['id']] = $user;
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
                    if (!isset($this->chats[-$chat['id']])) {
                        $this->should_serialize = true;
                        $this->chats[-$chat['id']] = $chat;
                    }

                case 'chatForbidden':
                case 'channelEmpty':
                    break;
                case 'channel':
                    if (!isset($this->chats[(int) ('-100'.$chat['id'])])) {
                        $this->should_serialize = true;
                        $this->chats[(int) ('-100'.$chat['id'])] = $chat;
                    }
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid chat provided at key '.$key.': '.var_export($chat, true));
                    break;
            }
        }
        $this->should_serialize = true;
    }


    public function get_info($id, $recursive = true)
    {
        if (is_array($id)) {
            switch ($id['_']) {
                case 'inputPeerSelf':
                case 'inputPeerSelf':
                    $id = $this->datacenter->authorization['user']['id'];
                    break;
                case 'user':
                    $id = $id['id'];
                    break;
                case 'inputPeerUser':
                case 'inputUser':
                case 'peerUser':
                    $id = $id['user_id'];
                    break;

                case 'chat':
                    $id = -$id['id'];
                    break;
                case 'inputPeerChat':
                case 'peerChat':
                    $id = -$id['chat_id'];
                    break;

                case 'channel':
                    $id = '-100'.$id['id'];
                    break;
                case 'inputPeerChannel':
                case 'inputChannel':
                case 'peerChannel':
                    $id = '-100'.$id['channel_id'];
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid constructor given ' . var_export($id, true));
                    break;
            }

        }
        
        if (preg_match('/^channel#/', $id)) $id = str_replace('channel#', '-100', $id);
        if (preg_match('/^chat#/', $id)) $id = str_replace('chat#', '-', $id);
        if (preg_match('/^user#/', $id)) $id = str_replace('user#', '', $id);

        if (is_numeric($id)) {
            $id = (int)$id;
            if (isset($this->chats[$id])) {
                return $this->gen_all($this->chats[$id]);
            }
            debug_print_backtrace();
//            if ($recursive) {
//            }
            throw new \danog\MadelineProto\Exception("Couldn't find peer by provided chat id ".$id);
        }
        $id = str_replace('@', '', $id);
        foreach ($this->chats as $chat) {
            if (isset($chat['username']) && $chat['username'] == $id) {
                return $this->gen_all($chat);
            }
        }
        if ($recursive) {
            $this->resolve_username($id);

            return $this->get_info($id, false);
        }
        throw new \danog\MadelineProto\Exception("Couldn't find peer by provided username ".$id);
    }

    public function gen_all($constructor) {
        switch ($constructor['_']) {
            case 'user':
                $inputPeer = $constructor['self'] ? ['_' => 'inputPeerSelf'] : ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $inputType = $constructor['self'] ? ['_' => 'inputUserSelf'] : ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $Peer = ['_' => 'peerUser', 'user_id' => $constructor['id']];
                $id = $constructor['id'];
                $bot_api_id = $constructor['id'];
                break;
            case 'chat':
                $inputPeer = ['_' => 'inputPeerChat', 'chat_id' => $constructor['id']];
                $inputType = [];
                $Peer = ['_' => 'peerChat', 'chat_id' => $constructor['id']];
                $id = $constructor['id'];
                $bot_api_id = -$constructor['id'];
                break;
            case 'channel':
                $inputPeer = ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $inputType = ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $Peer = ['_' => 'peerChannel', 'channel_id' => $constructor['id']];
                $id = $constructor['id'];
                $bot_api_id = (int)('-100'.$constructor['id']);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Invalid constructor given ' . var_export($constructor, true));
                break;
        }
        
        return ['constructor' => $constructor, 'inputPeer' => $inputPeer, 'inputType' => $inputType, 'Peer' => $Peer, 'id' => $id, 'botApiId' => $bot_api_id];
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
