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

namespace danog\MadelineProto\SecretChats;

/**
 * Manages secret chats.
 *
 * https://core.telegram.org/api/end-to-end
 */
trait AuthKeyHandler
{
    protected $temp_requested_secret_chats = [];
    protected $secret_chats = [];

    public function accept_secret_chat($params)
    {
        $this->should_serialize = true;
        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating b...'], \danog\MadelineProto\Logger::VERBOSE);
        $b = new \phpseclib\Math\BigInteger($this->random(256), 256);
        $params['g_a'] = new \phpseclib\Math\BigInteger($params['g_a'], 256);
        $this->check_G($params['g_a'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        $key['visualization_orig'] = substr(sha1($key['auth_key'], true), 16);
        $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
        $this->secret_chats[$params['id']] = ['key' => $key, 'admin' => false, 'user_id' => $params['admin_id'], 'InputEncryptedChat' => ['_' => 'inputEncryptedChat', 'chat_id' => $params['id'], 'access_hash' => $params['access_hash']], 'in_seq_no_x' => 1, 'out_seq_no_x' => 0, 'in_seq_no' => 0, 'out_seq_no' => 0, 'layer' => 8, 'ttl' => 0, 'ttr' => 100, 'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'rekeying' => [0]];
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        $this->check_G($g_b, $dh_config['p']);
        $this->notify_layer($params['id']);
        $this->handle_pending_updates();
    }

    public function request_secret_chat($user)
    {
        $this->should_serialize = true;
        $user = $this->get_info($user);
        if (!isset($user['InputUser'])) {
            throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
        }
        $user = $user['InputUser'];
        \danog\MadelineProto\Logger::log(['Creating secret chat with '.$user['user_id'].'...'], \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating a...'], \danog\MadelineProto\Logger::VERBOSE);
        $a = new \phpseclib\Math\BigInteger($this->random(256), 256);
        \danog\MadelineProto\Logger::log(['Generating g_a...'], \danog\MadelineProto\Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        $this->check_G($g_a, $dh_config['p']);
        $res = $this->method_call('messages.requestEncryption', ['user_id' => $user, 'g_a' => $g_a->toBytes()], ['datacenter' => $this->datacenter->curdc]);
        $this->temp_requested_secret_chats[$res['id']] = $a;
        $this->handle_pending_updates();
        $this->get_updates_difference();

        return $res['id'];
    }

    public function complete_secret_chat($params)
    {
        if ($this->secret_chat_status($params['id']) !== 1) {
            \danog\MadelineProto\Logger::log(['Could not find and complete secret chat '.$params['id']]);

            return false;
        }
        $this->should_serialize = true;
        $dh_config = $this->get_dh_config();
        $params['g_a_or_b'] = new \phpseclib\Math\BigInteger($params['g_a_or_b'], 256);
        $this->check_G($params['g_a_or_b'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_a_or_b']->powMod($this->temp_requested_secret_chats[$params['id']], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        unset($this->temp_requested_secret_chats[$params['id']]);
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);

        if ($key['fingerprint'] !== $params['key_fingerprint']) {
            $this->method_call('messages.discardEncryption', ['chat_id' => $params['id']], ['datacenter' => $this->datacenter->curdc]);
            throw new \danog\MadelineProto\SecurityException('Invalid key fingerprint!');
        }
        $key['visualization_orig'] = substr(sha1($key['auth_key'], true), 16);
        $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
        $this->secret_chats[$params['id']] = ['key' => $key, 'admin' => true, 'user_id' => $params['participant_id'], 'InputEncryptedChat' => ['chat_id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputEncryptedChat'], 'in_seq_no_x' => 0, 'out_seq_no_x' => 1, 'in_seq_no' => 0, 'out_seq_no' => 0, 'layer' => 8, 'ttl' => 0, 'ttr' => 100, 'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'rekeying' => [0]];
        $this->notify_layer($params['id']);
        $this->handle_pending_updates();
    }

    public function notify_layer($chat)
    {
        $this->method_call('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionNotifyLayer', 'layer' => $this->encrypted_layer]]], ['datacenter' => $this->datacenter->curdc]);
    }

    protected $temp_rekeyed_secret_chats = [];

    public function rekey($chat)
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 0) {
            return;
        }
        $this->should_serialize = true;
        \danog\MadelineProto\Logger::log(['Rekeying secret chat '.$chat.'...'], \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating a...'], \danog\MadelineProto\Logger::VERBOSE);
        $a = new \phpseclib\Math\BigInteger($this->random(256), 256);
        \danog\MadelineProto\Logger::log(['Generating g_a...'], \danog\MadelineProto\Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        $this->check_G($g_a, $dh_config['p']);
        $e = $this->random(8);
        $this->temp_rekeyed_secret_chats[$e] = $a;
        $this->secret_chats[$chat]['rekeying'] = [1, $e];
        $this->method_call('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionRequestKey', 'g_a' => $g_a->toBytes(), 'exchange_id' => $e]]], ['datacenter' => $this->datacenter->curdc]);
        $this->handle_pending_updates();
        $this->get_updates_difference();

        return $e;
    }

    public function accept_rekey($chat, $params)
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 0) {
            $my = $this->temp_rekeyed_secret_chats[$this->secret_chats[$chat]['rekeying'][1]];
            if ($my['exchange_id'] > $params['exchange_id']) {
                return;
            }
            if ($my['exchange_id'] === $params['exchange_id']) {
                $this->secret_chats[$chat]['rekeying'] = [0];
                $this->rekey($chat);

                return;
            }
        }
        $this->should_serialize = true;
        \danog\MadelineProto\Logger::log(['Accepting rekeying of secret chat '.$chat.'...'], \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating b...'], \danog\MadelineProto\Logger::VERBOSE);
        $b = new \phpseclib\Math\BigInteger($this->random(256), 256);
        $params['g_a'] = new \phpseclib\Math\BigInteger($params['g_a'], 256);
        $this->check_G($params['g_a'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);

        $key['visualization_orig'] = $this->secret_chats[$chat]['key']['visualization_orig'];
        $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
        $this->temp_rekeyed_secret_chats[$params['exchange_id']] = $key;
        $this->secret_chats[$chat]['rekeying'] = [2, $params['exchange_id']];
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        $this->check_G($g_b, $dh_config['p']);
        $this->method_call('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAcceptKey', 'g_b' => $g_b->toBytes(), 'exchange_id' => $params['exchange_id'], 'key_fingerprint' => $key['fingerprint']]]], ['datacenter' => $this->datacenter->curdc]);
        $this->handle_pending_updates();
        $this->get_updates_difference();
    }

    public function commit_rekey($chat, $params)
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 1) {
            return;
        }
        $this->should_serialize = true;
        \danog\MadelineProto\Logger::log(['Committing rekeying of secret chat '.$chat.'...'], \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = $this->get_dh_config();
        $params['g_b'] = new \phpseclib\Math\BigInteger($params['g_b'], 256);
        $this->check_G($params['g_b'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_b']->powMod($this->temp_rekeyed_secret_chats[$params['exchange_id']], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        $key['visualization_orig'] = $this->secret_chats[$chat]['key']['visualization_orig'];
        $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
        if ($key['fingerprint'] !== $params['key_fingerprint']) {
            $this->method_call('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]], ['datacenter' => $this->datacenter->curdc]);
            throw new \danog\MadelineProto\SecurityException('Invalid key fingerprint!');
        }
        $this->method_call('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionCommitKey', 'exchange_id' => $params['exchange_id'], 'key_fingerprint' => $key['fingerprint']]]], ['datacenter' => $this->datacenter->curdc]);
        unset($this->temp_rekeyed_secret_chats[$chat]);
        $this->secret_chats[$chat]['rekeying'] = [0];
        $this->secret_chats[$chat]['old_key'] = $this->secret_chats[$chat]['key'];
        $this->secret_chats[$chat]['key'] = $key;
        $this->secret_chats[$chat]['ttr'] = 100;
        $this->secret_chats[$chat]['updated'] = time();

        $this->handle_pending_updates();
        $this->get_updates_difference();
    }

    public function complete_rekey($chat, $params)
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 2) {
            return;
        }
        if ($this->temp_rekeyed_secret_chats['fingerprint'] !== $params['key_fingerprint']) {
            $this->method_call('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]], ['datacenter' => $this->datacenter->curdc]);
            throw new \danog\MadelineProto\SecurityException('Invalid key fingerprint!');
        }
        $this->should_serialize = true;
        \danog\MadelineProto\Logger::log(['Completing rekeying of secret chat '.$chat.'...'], \danog\MadelineProto\Logger::VERBOSE);
        $this->secret_chats[$chat]['rekeying'] = [0];
        $this->secret_chats[$chat]['old_key'] = $this->secret_chats[$chat]['key'];
        $this->secret_chats[$chat]['key'] = $this->temp_rekeyed_secret_chats[$chat];
        $this->secret_chats[$chat]['ttr'] = 100;
        $this->secret_chats[$chat]['updated'] = time();
        unset($this->temp_rekeyed_secret_chats[$params['exchange_id']]);
        $this->method_call('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionNoop']]], ['datacenter' => $this->datacenter->curdc]);
    }

    public function secret_chat_status($chat)
    {
        if (isset($this->secret_chats[$chat])) {
            return 2;
        }
        if (isset($this->temp_requested_secret_chats[$chat])) {
            return 1;
        }

        return 0;
    }

    public function get_secret_chat($chat)
    {
        return $this->secret_chats[$chat];
    }
}
