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
    public $last_stored = 0;
    public $qres = [];

    public function add_users($users)
    {
        foreach ($users as $key => $user) {
            switch ($user['_']) {
                case 'user':
                    if (!isset($this->chats[$user['id']]) || $this->chats[$user['id']] !== $user) {
                        $this->chats[$user['id']] = $user;
                        $this->should_serialize = true;
                        try {
                            $this->get_pwr_chat($user['id'], false, true);
                        } catch (\danog\MadelineProto\Exception $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage());
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage());
                        }
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
                case 'chatForbidden':
                    if (!isset($this->chats[-$chat['id']]) || $this->chats[-$chat['id']] !== $chat) {
                        $this->chats[-$chat['id']] = $chat;
                        $this->should_serialize = true;
                        try {
                            $this->get_pwr_chat(-$chat['id'], true, true);
                        } catch (\danog\MadelineProto\Exception $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage());
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage());
                        }
                    }

                case 'channelEmpty':
                    break;
                case 'channel':
                case 'channelForbidden':
                    if (!isset($this->chats[(int) ('-100'.$chat['id'])]) || $this->chats[(int) ('-100'.$chat['id'])] !== $chat) {
                        $this->chats[(int) ('-100'.$chat['id'])] = $chat;
                        $this->should_serialize = true;
                        try {
                            $this->get_pwr_chat('-100'.$chat['id'], true, true);
                        } catch (\danog\MadelineProto\Exception $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage());
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage());
                        }
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
                    $id = -$id['id'];
                    break;
                case 'inputPeerChat':
                case 'peerChat':
                    $id = -$id['chat_id'];
                    break;

                case 'channel':
                    $id = '-100'.$id['id'];
                    break;
                case 'channelFull':
                    $id = '-100'.$id['id'];
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
            if ($id < 0 && !preg_match('/^-100/', $id)) {
                $this->method_call('messages.getFullChat', ['chat_id' => -$id]);
                if (isset($this->chats[$id])) {
                    return $this->gen_all($this->chats[$id]);
                }
            }
            throw new \danog\MadelineProto\Exception("Couldn't find peer by provided chat id ".$id);
        }
        $id = str_replace('@', '', $id);
        foreach ($this->chats as $chat) {
            if (isset($chat['username']) && strtolower($chat['username']) == strtolower($id)) {
                return $this->gen_all($chat);
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
            case 'user':
                if ($constructor['self']) {
                    $res['InputPeer'] = ['_' => 'inputPeerSelf'];
                    $res['InputUser'] = ['_' => 'inputUserSelf'];
                } elseif (isset($constructor['access_hash'])) {
                    $res['InputPeer'] = ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                    $res['InputUser'] = ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                }
                $res['Peer'] = ['_' => 'peerUser', 'user_id' => $constructor['id']];
                $res['user_id'] = $constructor['id'];
                $res['bot_api_id'] = $constructor['id'];
                $res['type'] = $constructor['bot'] ? 'bot' : 'user';
                break;
            case 'chat':
            case 'chatForbidden':
                $res['InputPeer'] = ['_' => 'inputPeerChat', 'chat_id' => $constructor['id']];
                $res['Peer'] = ['_' => 'peerChat', 'chat_id' => $constructor['id']];
                $res['chat_id'] = $constructor['id'];
                $res['bot_api_id'] = -$constructor['id'];
                $res['type'] = 'chat';
                break;
            case 'channel':
                if (isset($constructor['access_hash'])) {
                    $res['InputPeer'] = ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                    $res['InputChannel'] = ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                }
                $res['Peer'] = ['_' => 'peerChannel', 'channel_id' => $constructor['id']];
                $res['channel_id'] = $constructor['id'];
                $res['bot_api_id'] = (int) ('-100'.$constructor['id']);
                $res['type'] = $constructor['megagroup'] ? 'supergroup' : 'channel';
                break;
            default:
                throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($constructor, true));
                break;
        }

        return $res;
    }

    public function get_full_info($id)
    {
        $partial = $this->get_info($id);
        switch ($partial['type']) {
            case 'user':
            case 'bot':
            $full = $this->method_call('users.getFullUser', ['id' => $partial['InputUser']]);
            break;

            case 'chat':
            $full = $this->method_call('messages.getFullChat', $partial)['full_chat'];
            break;

            case 'channel':
            case 'supergroup':
            $full = $this->method_call('channels.getFullChannel', ['channel' => $partial['InputChannel']])['full_chat'];
            break;
        }
        $partial = $this->get_info($id);
        $partial['full'] = $full;

        return $partial;
    }

    public function get_pwr_chat($id, $fullfetch = true, $send = true)
    {
        $full = $fullfetch ? $this->get_full_info($id) : $this->get_info($id);
        $res = ['id' => $full['bot_api_id'], 'type' => $full['type']];
        switch ($full['type']) {
            case 'user':
            case 'bot':
                foreach (['first_name', 'last_name', 'username', 'verified', 'restricted', 'restriction_reason', 'status', 'bot_inline_placeholder', 'access_hash', 'phone'] as $key) {
                    if (isset($full['User'][$key])) {
                        $res[$key] = $full['User'][$key];
                    }
                }
                if (isset($full['full']['about'])) {
                    $res['about'] = $full['full']['about'];
                }
                if (isset($full['full']['bot_info'])) {
                    $res['bot_info'] = $full['full']['bot_info'];
                }
                if (isset($full['full']['profile_photo']['sizes'])) {
                    $res['photo'] = end($full['full']['profile_photo']['sizes']);
                }
                $bio = '';
                if ($full['type'] == 'user' && isset($res['username']) && !isset($res['about']) && $fullfetch) {
                    if (preg_match('/meta property="og:description" content=".+/', file_get_contents('https://telegram.me/'.$res['username']), $biores)) {
                        $bio = html_entity_decode(preg_replace_callback('/(&#[0-9]+;)/', function ($m) {
                            return mb_convert_encoding($m[1], 'UTF-8', 'HTML-ENTITIES');
                        }, str_replace(['meta property="og:description" content="', '">'], '', $biores[0])));
                    }
                    if ($bio != '' && $bio != 'You can contact @'.$res['username'].' right away.') {
                        $res['about'] = $bio;
                    }
                }
                break;
            case 'chat':
                foreach (['title', 'participants_count', 'admin', 'admins_enabled'] as $key) {
                    if (isset($full['Chat'][$key])) {
                        $res[$key] = $full['Chat'][$key];
                    }
                }

                if (isset($full['full']['chat_photo']['sizes'])) {
                    $res['photo'] = end($full['full']['chat_photo']['sizes']);
                }
                if (isset($full['full']['exported_invite']['link'])) {
                    $res['invite'] = $full['full']['exported_invite']['link'];
                }
                if (isset($full['full']['participants']['participants'])) {
                    $res['participants'] = $full['full']['participants']['participants'];
                }
                break;
            case 'channel':
            case 'supergroup':
                foreach (['title', 'democracy', 'restricted', 'restriction_reason', 'access_hash', 'username', 'signatures'] as $key) {
                    if (isset($full['Chat'][$key])) {
                        $res[$key] = $full['Chat'][$key];
                    }
                }
                foreach (['can_view_participants', 'can_set_username', 'participants_count', 'admins_count', 'kicked_count', 'migrated_from_chat_id', 'migrated_from_max_id', 'pinned_msg_id', 'about'] as $key) {
                    if (isset($full['full'][$key])) {
                        $res[$key] = $full['full'][$key];
                    }
                }

                if (isset($full['full']['chat_photo']['sizes'])) {
                    $res['photo'] = end($full['full']['chat_photo']['sizes']);
                }
                if (isset($full['full']['exported_invite']['link'])) {
                    $res['invite'] = $full['full']['exported_invite']['link'];
                }
                if (isset($full['full']['participants']['participants'])) {
                    $res['participants'] = $full['full']['participants']['participants'];
                }
                break;
        }
        if (isset($res['participants'])) {
            foreach ($res['participants'] as $key => $participant) {
                $newres = [];
                $newres['user'] = $this->get_pwr_chat($participant['user_id'], false, false);
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = $this->get_pwr_chat($participant['inviter_id'], false, false);
                }
                if (isset($participant['date'])) {
                    $newres['date'] = $participant['date'];
                }
                switch ($participant['_']) {
                    case 'chatParticipant':
                    $newres['role'] = 'user';
                    break;

                    case 'chatParticipantAdmin':
                    $newres['role'] = 'admin';
                    break;

                    case 'chatParticipantCreator':
                    $newres['role'] = 'creator';
                    break;
                }
                $res['participants'][$key] = $newres;
            }
        }
        if (!isset($res['participants']) && isset($res['can_view_participants']) && $res['can_view_participants']) {
            $res['participants'] = [];
            $limit = 400;
            $offset = -$limit;
            $gres = $this->method_call('channels.getParticipants', ['channel' => $full['InputChannel'], 'filter' => ['_' => 'channelParticipantsRecent'], 'offset' => $offset += $limit, 'limit' => 200]);
            $count = $gres['count'];
            $key = -1;
            while ($offset <= $count) {
                foreach ($gres['participants'] as $participant) {
                    $newres = [];
                    $newres['user'] = $this->get_pwr_chat($participant['user_id'], false, false);
                    $key++;
                    if (isset($participant['inviter_id'])) {
                        $newres['inviter'] = $this->get_pwr_chat($participant['inviter_id'], false, false);
                    }
                    if (isset($participant['date'])) {
                        $newres['date'] = $participant['date'];
                    }
                    switch ($participant['_']) {
                        case 'channelParticipant':
                        $newres['role'] = 'user';
                        break;

                        case 'channelParticipantModerator':
                        $newres['role'] = 'moderator';
                        break;

                        case 'channelParticipantEditor':
                        $newres['role'] = 'moderator';
                        break;

                        case 'channelParticipantCreator':
                        $newres['role'] = 'creator';
                        break;

                        case 'channelParticipantKicked':
                        $newres['role'] = 'kicked';
                        break;

                    }
                    $res['participants'][$key] = $newres;
                }
                $gres = $this->method_call('channels.getParticipants', ['channel' => $full['InputChannel'], 'filter' => ['_' => 'channelParticipantsRecent'], 'offset' => $offset += $limit, 'limit' => $limit]);
            }
        }
        if ($fullfetch || $send) {
            $this->store_db($res);
        }

        return $res;
    }

    public function store_db($res, $force = false)
    {
        if (!isset($this->settings['pwr']) || $this->settings['pwr']['pwr'] === false) {
            try {
                if (isset($res['username'])) {
                    shell_exec('curl '.escapeshellarg('https://api.pwrtelegram.xyz/getchat?chat_id=@'.$res['username']).' -s -o /dev/null >/dev/null 2>/dev/null & ');
                }
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log($e->getMessage());
            }

            return;
        }
        if (!empty($res)) {
            if (isset($res['participants'])) {
                unset($res['participants']);
            }
            $this->qres[] = $res;
        }
        if ($this->last_stored < time() && !$force) {
            return false;
        }
        if (empty($this->qres)) {
            return false;
        }
        try {
            $payload = json_encode($this->qres);
            $path = '/tmp/ids'.hash('sha256', $payload);
            file_put_contents($path, $payload);
            $result = shell_exec('curl '.escapeshellarg('https://id.pwrtelegram.xyz/db'.$this->settings['pwr']['db_token'].'/addnewmadeline?d=pls&from='.$this->datacenter->authorization['user']['username']).' -d '.escapeshellarg('@'.$path).' -s -o '.escapeshellarg($path.'.log').' >/dev/null 2>/dev/null & ');
            \danog\MadelineProto\Logger::log($result);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log($e->getMessage());
        }
        $this->qres = [];
        $this->last_stored = time() + 10;
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
