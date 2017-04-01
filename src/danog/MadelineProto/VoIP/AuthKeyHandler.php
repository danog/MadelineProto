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

namespace danog\MadelineProto\VoIP;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 */
trait AuthKeyHandler
{
    private $temp_requested_calls = [];
    private $calls = [];

    public function accept_call($params)
    {
        $dh_config = $this->get_dh_config();
        $phone_config = $this->method_call('phone.getCallConfig');
        $b = new \phpseclib\Math\BigInteger($this->random(256), 256);
        $params['g_a'] = new \phpseclib\Math\BigInteger($params['g_a'], 256);
        $this->check_G($params['g_a'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        $key['visualization_orig'] = substr(sha1($key['auth_key'], true), 16);
        $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
        $this->calls[$params['id']] = ['key' => $key, 'admin' => false, 'user_id' => $params['admin_id'], 'InputPhoneCall' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'in_seq_no_x' => 0, 'out_seq_no_x' => 1, 'layer' => 65, 'ttr' => 100, 'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'rekeying' => [0], 'protocol' => $params['protocol'], 'connection' => $params['connection'], 'alternative_connections' => $params['alternative_connections']];
        //$this->calls[$params['id']] = ['key' => $key, 'admin' => false, 'user_id' => $params['admin_id'], 'InputEncryptedChat' => ['_' => 'inputEncryptedChat', 'chat_id' => $params['id'], 'access_hash' => $params['access_hash']], 'in_seq_no_x' => 1, 'out_seq_no_x' => 0, 'layer' => 8, 'ttl' => PHP_INT_MAX, 'ttr' => 100, 'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'rekeying' => [0]];
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        $this->check_G($g_b, $dh_config['p']);
        $this->handle_pending_updates();
    }

    public function request_call($user)
    {
        $user = $this->get_info($user)['InputUser'];
        \danog\MadelineProto\Logger::log(['Calling '.$user['user_id'].'...'], \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = $this->get_dh_config();
        $phone_config = $this->method_call('phone.getCallConfig', [], ['datacenter' => $this->datacenter->curdc]);
        \danog\MadelineProto\Logger::log(['Generating a...'], \danog\MadelineProto\Logger::VERBOSE);
        $a = new \phpseclib\Math\BigInteger($this->random(256), 256);
        \danog\MadelineProto\Logger::log(['Generating g_a...'], \danog\MadelineProto\Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        $this->check_G($g_a, $dh_config['p']);
        $res = $this->method_call('phone.requestCall', ['user_id' => $user, 'g_a_hash' => hash('sha256', $g_a->toBytes(), true), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => $this->settings['tl_schema']['layer'], 'max_layer' => $this->settings['tl_schema']['layer']]], ['datacenter' => $this->datacenter->curdc]);
        $this->temp_requested_calls[$res['phone_call']['id']] = $a;
        $this->handle_pending_updates();
        $this->get_updates_difference();

        return $res['phone_call']['id'];
    }

    public function complete_call($params)
    {
        if ($this->call_status($params['id']) !== 1) {
            \danog\MadelineProto\Logger::log(['Could not find and complete secret chat '.$params['id']]);

            return false;
        }
        $dh_config = $this->get_dh_config();
        $params['g_a_or_b'] = new \phpseclib\Math\BigInteger($params['g_a_or_b'], 256);
        $this->check_G($params['g_a_or_b'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_a_or_b']->powMod($this->temp_requested_calls[$params['id']], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        unset($this->temp_requested_calls[$params['id']]);
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        if ($key['fingerprint'] !== $params['key_fingerprint']) {
            throw new \danog\MadelineProto\SecurityException('Invalid key fingerprint!');
        }
        $key['visualization_orig'] = substr(sha1($key['auth_key'], true), 16);
        $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
        $this->calls[$params['id']] = ['key' => $key, 'admin' => true, 'user_id' => $params['participant_id'], 'InputPhoneCall' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'in_seq_no_x' => 0, 'out_seq_no_x' => 1, 'layer' => 65, 'ttr' => 100, 'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'rekeying' => [0], 'protocol' => $params['protocol'], 'connection' => $params['connection'], 'alternative_connections' => $params['alternative_connections']];
        $this->handle_pending_updates();
    }

    public function call_status($id)
    {
        if (isset($this->calls[$id])) {
            return 2;
        }
        if (isset($this->temp_requested_calls[$id])) {
            return 1;
        }

        return 0;
    }

    public function get_secret_chat($chat)
    {
        return $this->calls[$chat];
    }

}
