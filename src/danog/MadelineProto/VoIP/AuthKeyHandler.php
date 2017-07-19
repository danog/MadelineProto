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

    public function request_call($user, $class)
    {
        foreach ($this->calls as $id => $controller) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($this->calls[$id]);
            }
        }

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
        $res = $this->method_call('phone.requestCall', ['user_id' => $user, 'g_a_hash' => hash('sha256', $g_a->toBytes(), true), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_p2p' => true, 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => 65]], ['datacenter' => $this->datacenter->curdc]);
        $this->calls[$res['phone_call']['id']] = $controller = new \danog\MadelineProto\VoIP(true, time(), $user['user_id'], $res['phone_call']['id'], $this, \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED, $res['phone_call']['protocol']);
        $controller->storage = ['a' => $a, 'g_a' => str_pad($g_a->toBytes(), 256, chr(0), \STR_PAD_LEFT)];

        $this->handle_pending_updates();
        $this->get_updates_difference();
        return $controller;
    }

    public function accept_call($params)
    {
        foreach ($this->calls as $id => $controller) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($this->calls[$id]);
            }
        }
        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating b...'], \danog\MadelineProto\Logger::VERBOSE);
        $b = \phpseclib\Math\BigInteger::randomRange($this->two, $dh_config['p']->subtract($this->two));
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        $this->check_G($g_b, $dh_config['p']);
        $res = $this->method_call('phone.acceptCall', ['peer' => ['_' => 'inputPhoneCall', 'id' => $params['id'], 'access_hash' => $params['access_hash']], 'g_b' => $g_b->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'udp_p2p' => true, 'min_layer' => 65, 'max_layer' => 65]], ['datacenter' => $this->datacenter->curdc]);
        $this->calls[$res['phone_call']['id']] = ['status' => \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED, 'b' => $b, 'g_a_hash' => $params['g_a_hash']];
        $this->handle_pending_updates();
        $this->get_updates_difference();
    }

    public function confirm_call($params)
    {
        foreach ($this->calls as $id => $controller) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($this->calls[$id]);
            }
        }
        if ($this->calls[$params['id']]->getCallState() !== \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED) {
            \danog\MadelineProto\Logger::log(['Could not find and confirm call '.$params['id']]);

            return false;
        }
        $dh_config = $this->get_dh_config();
        $params['g_b'] = new \phpseclib\Math\BigInteger($params['g_b'], 256);
        $this->check_G($params['g_b'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_b']->powMod($this->calls[$params['id']]->storage['a'], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        $res = $this->method_call('phone.confirmCall', ['key_fingerprint' => $key['fingerprint'], 'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'g_a' => $this->calls[$params['id']]->storage['g_a'], 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => 65]], ['datacenter' => $this->datacenter->curdc])['phone_call'];
        $key['visualization'] = '';
        $length = new \phpseclib\Math\BigInteger(count($this->emojis));
        foreach (str_split(hash('sha256', $key['auth_key'].$this->calls[$params['id']]->storage['g_a'], true), 8) as $number) {
            var_dump((new \phpseclib\Math\BigInteger($number, -256))->toString());
            var_dump($this->emojis[(int) ((new \phpseclib\Math\BigInteger($number, -256))->divide($length)[1]->toString())]);
            $key['visualization'] .= $this->emojis[(int) ((new \phpseclib\Math\BigInteger($number, -256))->divide($length)[1]->toString())];
        }
        readline();
        $this->calls[$params['id']]->setCallState(\danog\MadelineProto\VoIP::CALL_STATE_READY);
        $this->calls[$params['id']]->storage = ['InputPhoneCall' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'protocol' => $params['protocol']];
        $this->calls[$params['id']]->setVisualization($key['visualization']);

        $this->calls[$params['id']]->configuration = array_merge([
            'config' => [
                'recv_timeout' => $this->config['call_receive_timeout_ms'] / 1000,
                'init_timeout' => $this->config['call_connect_timeout_ms'] / 1000,
                'data_saving'     => \danog\MadelineProto\VoIP::DATA_SAVING_NEVER, 
                'enable_NS'       => true,
                'enable_AEC'      => true,
                'enable_AGC'      => true,
                'log_file_path'   => $this->settings['calls']['log_file_path'],
                'stats_dump_file_path' => $this->settings['calls']['stats_dump_file_path']
            ],
            'auth_key' => $key['auth_key'],
            'network_type' => $this->settings['calls']['network_type'],
            'shared_config' => $this->method_call('phone.getCallConfig', [], ['datacenter' => $this->datacenter->curdc]),
            'endpoints' => array_merge([$res['connection']], $res['alternative_connections']),
        ], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();
        $this->calls[$params['id']]->startTheMagic();
        while ($this->calls[$params['id']]->getState() !== \danog\MadelineProto\VoIP::STATE_ESTABLISHED);
        while ($this->calls[$params['id']]->getOutputState() < \danog\MadelineProto\VoIP::AUDIO_STATE_CONFIGURED);

        $this->calls[$params['id']]->play('../Little Swing.raw')->then('output.raw');

        $this->handle_pending_updates();
    }

    public function complete_call($params)
    {
        foreach ($this->calls as $id => $controller) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($this->calls[$id]);
            }
        }
        if ($this->call_status($params['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED) {
            \danog\MadelineProto\Logger::log(['Could not find and confirm call '.$params['id']]);

            return false;
        }
        $dh_config = $this->get_dh_config();
        if (hash('sha256', $params['g_a_or_b'], true) != $this->calls[$params['id']]['g_a_hash']) {
            throw new \danog\MadelineProto\SecurityException('Invalid g_a!');
        }
        $params['g_a_or_b'] = new \phpseclib\Math\BigInteger($params['g_a_or_b'], 256);
        $this->check_G($params['g_a_or_b'], $dh_config['p']);
        $key = ['auth_key' => str_pad($params['g_a_or_b']->powMod($this->calls[$params['id']]['b'], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
        if ($key['fingerprint'] != $params['key_fingerprint']) {
            //            throw new \danog\MadelineProto\SecurityException('Invalid key fingerprint!');
        }
        $key['visualization'] = '';
        $length = new \phpseclib\Math\BigInteger(count($this->emojis));
        foreach (str_split(strrev(substr(hash('sha256', $params['g_a_or_b']->toBytes().$key['auth_key'], true), 20)), 8) as $number) {
            $key['visualization'] .= $this->emojis[(int) ((new \phpseclib\Math\BigInteger($number, -256))->divide($length)[1]->toString())];
        }

        $this->calls[$params['id']] = ['status' => \danog\MadelineProto\VoIP::CALL_STATE_READY, 'key' => $key, 'admin' => false, 'user_id' => $params['admin_id'], 'InputPhoneCall' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'in_seq_no_x' => 1, 'out_seq_no_x' => 0, 'layer' => 65,  'updated' => time(), 'incoming' => [], 'outgoing' => [], 'created' => time(), 'protocol' => $params['protocol'], 'callbacks' => $this->get_incoming_call_callbacks()];
        $this->calls[$params['id']] = new \danog\MadelineProto\VoIP($this->calls[$params['id']]['callbacks']['set_state'], $this->calls[$params['id']]['callbacks']['incoming'], $this->calls[$params['id']]['callbacks']['outgoing'], $this, $this->calls[$params['id']]['InputPhoneCall']);
        $this->calls[$params['id']]->setEncryptionKey($key['auth_key'], false);
        $this->calls[$params['id']]->setNetworkType($this->settings['calls']['network_type']);
        $this->calls[$params['id']]->setConfig($this->config['call_receive_timeout_ms'] / 1000, $this->config['call_connect_timeout_ms'] / 1000, \danog\MadelineProto\VoIP::CALL_STATE_DATA_SAVING_NEVER, true, true, true, $this->settings['calls']['log_file_path'], $this->settings['calls']['stats_dump_file_path']);
        $this->calls[$params['id']]->setSharedConfig($this->method_call('phone.getCallConfig', [], ['datacenter' => $this->datacenter->curdc]));
        $this->calls[$params['id']]->setRemoteEndpoints(array_merge([$params['connection']], $params['alternative_connections']), $params['protocol']['udp_p2p']);
        $this->calls[$params['id']]->start();
        $this->calls[$params['id']]->connect();
        while ($this->calls[$params['id']]->getState() !== \danog\MadelineProto\VoIP::STATE_ESTABLISHED);
        while ($this->calls[$params['id']]->getOutputState() === \danog\MadelineProto\VoIP::AUDIO_STATE_NONE);
        while ($this->calls[$params['id']]->getInputState() === \danog\MadelineProto\VoIP::AUDIO_STATE_NONE);

        $this->calls[$params['id']]->play('Little Swing.raw')->then('output.raw');
    }

    public function call_status($id)
    {
        foreach ($this->calls as $id => $controller) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($this->calls[$id]);
            }
        }
        if (isset($this->calls[$id])) {
            return $this->calls[$id]->getCallState();
        }

        return -1;
    }

    public function get_call($call)
    {
        foreach ($this->calls as $id => $controller) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($this->calls[$id]);
            }
        }
        return $this->calls[$call];
    }

    public function discard_call($call)
    {
        foreach ($this->calls as $id => $controller) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($this->calls[$id]);
            }
        }
        \danog\MadelineProto\Logger::log(['Discarding call '.$call.'...'], \danog\MadelineProto\Logger::VERBOSE);

        if (isset($this->calls[$call])) {
            if (isset($this->calls[$call]->storage['InputPhoneCall'])) {
                try {
                    $this->method_call('calls.discardCall', ['peer' => $this->calls[$call]->storage['InputPhoneCall']], ['datacenter' => $this->datacenter->curdc]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    if ($e->rpc !== 'CALL_ALREADY_DECLINED') {
                        throw $e;
                    }
                }
            }
            unset($this->calls[$call]);
        }
    }
}
