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
    private $calls = [];

    public function request_call($user)
    {
        $user = $this->get_info($user);
        if (!isset($user['InputUser'])) {
            throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
        }
        $user = $user['InputUser'];
        \danog\MadelineProto\Logger::log(['Calling '.$user['user_id'].'...'], \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating a...'], \danog\MadelineProto\Logger::VERBOSE);
        $a = \phpseclib\Math\BigInteger::randomRange($this->two, $dh_config['p']->subtract($this->two));
        \danog\MadelineProto\Logger::log(['Generating g_a...'], \danog\MadelineProto\Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        $this->check_G($g_a, $dh_config['p']);

        $res = $this->method_call('phone.requestCall', ['user_id' => $user, 'g_a_hash' => hash('sha256', $g_a->toBytes(), true), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => $this->settings['tl_schema']['layer'], 'max_layer' => $this->settings['tl_schema']['layer']]], ['datacenter' => $this->datacenter->curdc]);
        $this->calls[$res['phone_call']['id']] = ['status' => self::REQUESTED, 'a' => $a, 'g_a' => $g_a];
        $this->handle_pending_updates();
        $this->get_updates_difference();

        return $res['phone_call']['id'];
    }

    public function accept_call($params)
    {
        if ($this->settings['calls']['accept_calls'] === false) {
            return false;
        }
        if ($this->is_array($this->settings['calls']['accept_calls']) && !$this->in_array($this->settings['calls']['accept_calls'])) {
            return false;
        }
        if ($params['protocol']['udp_p2p'] && !$this->settings['calls']['allow_p2p']) {
            return false;
        }
        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating b...'], \danog\MadelineProto\Logger::VERBOSE);
        $b = \phpseclib\Math\BigInteger::randomRange($this->two, $dh_config['p']->subtract($this->two));
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        $this->check_G($g_b, $dh_config['p']);
        $this->calls[$res['phone_call']['id']] = ['status' => self::ACCEPTED, 'b' => $b, 'g_a_hash' => $params['g_a_hash']];
        $res = $this->method_call('phone.acceptCall', ['user_id' => $user, 'g_b' => $g_b->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => $this->settings['tl_schema']['layer'], 'max_layer' => $this->settings['tl_schema']['layer']]], ['datacenter' => $this->datacenter->curdc]);
        $this->handle_pending_updates();
        $this->get_updates_difference();
    }

    public function confirm_call($params)
    {
        if ($this->call_status($params['id']) !== self::REQUESTED) {
            \danog\MadelineProto\Logger::log(['Could not find and confirm call '.$params['id']]);

            return false;
        }
        $dh_config = $this->get_dh_config();
        $params['g_b'] = new \phpseclib\Math\BigInteger($params['g_b'], 256);
        $this->check_G($params['g_b'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_b']->powMod($this->calls[$params['id']]['a'], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        $res = $this->method_call('phone.confirmCall', ['user_id' => $user, 'g_a' => $this->calls[$params['id']]['g_a']->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => $this->settings['tl_schema']['layer'], 'max_layer' => $this->settings['tl_schema']['layer']]], ['datacenter' => $this->datacenter->curdc]);
        $key['visualization'] = '';
        $length = new \phpseclib\Math\BigInteger(count(self::EMOJIS));
        foreach (str_split(strrev(substr(hash('sha256', $this->calls[$params['id']]['g_a']->toBytes().$key['auth_key'], true), 20)), 8) as $number) {
            $key['visualization'] .= self::EMOJIS[(int) ((new \phpseclib\Math\BigInteger($number, -256))->divide($length)[1]->toString())];
        }

        $this->calls[$params['id']] = ['status' => self::READY, 'key' => $key, 'admin' => true, 'user_id' => $params['participant_id'], 'InputPhoneCall' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'in_seq_no_x' => 0, 'out_seq_no_x' => 1, 'layer' => $this->settings['tl_scheme']['layer'],  'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'protocol' => $params['protocol']];
        $this->handle_pending_updates();
    }

    public function complete_call($params)
    {
        if ($this->call_status($params['id']) !== self::ACCEPTED) {
            \danog\MadelineProto\Logger::log(['Could not find and confirm call '.$params['id']]);

            return false;
        }
        $dh_config = $this->get_dh_config();
        if (hash('sha256', $key['g_a_or_b'], true) !== $this->calls[$params['id']]['g_a_hash']) {
            throw new \danog\MadelineProto\SecurityException('Invalid g_a!');
        }
        $params['g_a_or_b'] = new \phpseclib\Math\BigInteger($params['g_a_or_b'], 256);
        $this->check_G($params['g_a_or_b'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        if ($key['fingerprint'] !== $params['key_fingerprint']) {
            throw new \danog\MadelineProto\SecurityException('Invalid key fingerprint!');
        }
        $key['visualization'] = '';
        $length = new \phpseclib\Math\BigInteger(count($self::EMOJIS));
        foreach (str_split(strrev(substr(hash('sha256', $params['g_a_or_b']->toBytes().$key['auth_key'], true), 20)), 8) as $number) {
            $key['visualization'] .= $self::EMOJIS[(int) ((new \phpseclib\Math\BigInteger($number, -256))->divide($length)[1]->toString())];
        }

        $this->calls[$params['id']] = ['status' => self::READY, 'key' => $key, 'admin' => false, 'user_id' => $params['admin_id'], 'InputPhoneCall' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'in_seq_no_x' => 1, 'out_seq_no_x' => 0, 'layer' => $this->settings['tl_scheme']['layer'],  'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'protocol' => $params['protocol']];
    }

    public function call_status($id)
    {
        if (isset($this->calls[$id])) {
            return $this->calls[$id]['status'];
        }

        return -1;
    }

    public function get_call($chat)
    {
        return $this->calls[$chat];
    }
}
