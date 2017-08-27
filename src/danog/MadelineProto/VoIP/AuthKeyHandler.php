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
        if (!class_exists('\danog\MadelineProto\VoIP')) {
            throw new \danog\MadelineProto\Exception('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.');
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });

        $user = $this->get_info($user);
        if (!isset($user['InputUser']) || $user['InputUser']['_'] === 'inputUserSelf') {
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
        $this->calls[$res['phone_call']['id']] = $controller = new \danog\MadelineProto\VoIP(true, $user['user_id'], ['_' => 'inputPhoneCall', 'id' => $res['phone_call']['id'], 'access_hash' => $res['phone_call']['access_hash']], $this, \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED, $res['phone_call']['protocol']);
        $controller->storage = ['a' => $a, 'g_a' => str_pad($g_a->toBytes(), 256, chr(0), \STR_PAD_LEFT)];

        $this->handle_pending_updates();
        $this->get_updates_difference();

        return $controller;
    }

    public function accept_call($call)
    {
        if (!class_exists('\danog\MadelineProto\VoIP')) {
            throw new \danog\MadelineProto\Exception('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.');
        }

        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        if ($this->call_status($call['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED) {
            \danog\MadelineProto\Logger::log(['Could not find and accept call '.$call['id']]);

            return false;
        }
        \danog\MadelineProto\Logger::log(['Accepting call from '.$this->calls[$call['id']]->getOtherID().'...'], \danog\MadelineProto\Logger::VERBOSE);

        $dh_config = $this->get_dh_config();
        \danog\MadelineProto\Logger::log(['Generating b...'], \danog\MadelineProto\Logger::VERBOSE);
        $b = \phpseclib\Math\BigInteger::randomRange($this->two, $dh_config['p']->subtract($this->two));
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        $this->check_G($g_b, $dh_config['p']);

        try {
            $res = $this->method_call('phone.acceptCall', ['peer' => $call, 'g_b' => $g_b->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'udp_p2p' => true, 'min_layer' => 65, 'max_layer' => 65]], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                \danog\MadelineProto\Logger::log(['Call '.$call['id'].' already accepted']);

                return true;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                \danog\MadelineProto\Logger::log(['Call '.$call['id'].' already declined']);
                $this->calls[$call['id']]->discard();

                return false;
            }

            throw $e;
        }
        $this->calls[$res['phone_call']['id']]->storage['b'] = $b;

        $this->handle_pending_updates();
        $this->get_updates_difference();

        return true;
    }

    public function confirm_call($params)
    {
        if (!class_exists('\danog\MadelineProto\VoIP')) {
            throw new \danog\MadelineProto\Exception('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.');
        }

        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        if ($this->call_status($params['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED) {
            \danog\MadelineProto\Logger::log(['Could not find and confirm call '.$params['id']]);

            return false;
        }
        \danog\MadelineProto\Logger::log(['Confirming call from '.$this->calls[$params['id']]->getOtherID().'...'], \danog\MadelineProto\Logger::VERBOSE);

        $dh_config = $this->get_dh_config();
        $params['g_b'] = new \phpseclib\Math\BigInteger($params['g_b'], 256);
        $this->check_G($params['g_b'], $dh_config['p']);
        $key = str_pad($params['g_b']->powMod($this->calls[$params['id']]->storage['a'], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT);
        $res = $this->method_call('phone.confirmCall', ['key_fingerprint' => substr(sha1($key, true), -8), 'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'g_a' => $this->calls[$params['id']]->storage['g_a'], 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => 65]], ['datacenter' => $this->datacenter->curdc])['phone_call'];

        $visualization = [];
        $length = new \phpseclib\Math\BigInteger(count($this->emojis));
        foreach (str_split(hash('sha256', $key.str_pad($this->calls[$params['id']]->storage['g_a'], 256, chr(0), \STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = chr(ord($number[0]) & 0x7F);
            $visualization[] = $this->emojis[(int) ((new \phpseclib\Math\BigInteger($number, 256))->divide($length)[1]->toString())];
        }
        $this->calls[$params['id']]->setVisualization($visualization);

        $this->calls[$params['id']]->configuration['shared_config'] = array_merge($this->method_call('phone.getCallConfig', [], ['datacenter' => $this->datacenter->curdc]), $this->calls[$params['id']]->configuration['shared_config']);
        $this->calls[$params['id']]->configuration['endpoints'] = array_merge([$res['connection']], $res['alternative_connections'], $this->calls[$params['id']]->configuration['endpoints']);
        $this->calls[$params['id']]->configuration = array_merge([
            'recv_timeout'         => $this->config['call_receive_timeout_ms'] / 1000,
            'init_timeout'         => $this->config['call_connect_timeout_ms'] / 1000,
            'data_saving'          => \danog\MadelineProto\VoIP::DATA_SAVING_NEVER,
            'enable_NS'            => true,
            'enable_AEC'           => true,
            'enable_AGC'           => true,

            'auth_key'      => $key,
            'network_type'  => \danog\MadelineProto\VoIP::NET_TYPE_ETHERNET,
        ], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();
        $res = $this->calls[$params['id']]->startTheMagic();

        $this->handle_pending_updates();

        return $res;
    }

    public function complete_call($params)
    {
        if (!class_exists('\danog\MadelineProto\VoIP')) {
            throw new \danog\MadelineProto\Exception('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.');
        }

        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        if ($this->call_status($params['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED || !isset($this->calls[$params['id']]->storage['b'])) {
            \danog\MadelineProto\Logger::log(['Could not find and complete call '.$params['id']]);

            return false;
        }
        \danog\MadelineProto\Logger::log(['Completing call from '.$this->calls[$params['id']]->getOtherID().'...'], \danog\MadelineProto\Logger::VERBOSE);

        $dh_config = $this->get_dh_config();
        if (hash('sha256', $params['g_a_or_b'], true) != $this->calls[$params['id']]->storage['g_a_hash']) {
            throw new \danog\MadelineProto\SecurityException('Invalid g_a!');
        }
        $params['g_a_or_b'] = new \phpseclib\Math\BigInteger($params['g_a_or_b'], 256);
        $this->check_G($params['g_a_or_b'], $dh_config['p']);
        $key = str_pad($params['g_a_or_b']->powMod($this->calls[$params['id']]->storage['b'], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT);

        if (substr(sha1($key, true), -8) != $params['key_fingerprint']) {
            throw new \danog\MadelineProto\SecurityException('Invalid key fingerprint!');
        }
        $visualization = [];
        $length = new \phpseclib\Math\BigInteger(count($this->emojis));

        foreach (str_split(hash('sha256', $key.str_pad($params['g_a_or_b']->toBytes(), 256, chr(0), \STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = chr(ord($number[0]) & 0x7F);
            $visualization[] = $this->emojis[(int) ((new \phpseclib\Math\BigInteger($number, 256))->divide($length)[1]->toString())];
        }

        $this->calls[$params['id']]->setVisualization($visualization);
        $this->calls[$params['id']]->configuration['shared_config'] = array_merge($this->method_call('phone.getCallConfig', [], ['datacenter' => $this->datacenter->curdc]), $this->calls[$params['id']]->configuration['shared_config']);
        $this->calls[$params['id']]->configuration['endpoints'] = array_merge([$params['connection']], $params['alternative_connections'], $this->calls[$params['id']]->configuration['endpoints']);

        $this->calls[$params['id']]->configuration = array_merge([
            'recv_timeout'         => $this->config['call_receive_timeout_ms'] / 1000,
            'init_timeout'         => $this->config['call_connect_timeout_ms'] / 1000,
            'data_saving'          => \danog\MadelineProto\VoIP::DATA_SAVING_NEVER,
            'enable_NS'            => true,
            'enable_AEC'           => true,
            'enable_AGC'           => true,

            'auth_key'      => $key,
            'network_type'  => \danog\MadelineProto\VoIP::NET_TYPE_ETHERNET,
        ], $this->calls[$params['id']]->configuration);
        var_dump($this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();

        return $this->calls[$params['id']]->startTheMagic();
    }

    public function call_status($id)
    {
        if (!class_exists('\danog\MadelineProto\VoIP')) {
            throw new \danog\MadelineProto\Exception('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.');
        }

        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });

        if (isset($this->calls[$id])) {
            return $this->calls[$id]->getCallState();
        }

        return \danog\MadelineProto\VoIP::CALL_STATE_NONE;
    }

    public function get_call($call)
    {
        if (!class_exists('\danog\MadelineProto\VoIP')) {
            throw new \danog\MadelineProto\Exception('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.');
        }

        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });

        return $this->calls[$call];
    }

    public function discard_call($call, $reason, $rating = [], $need_debug = true)
    {
        if (!class_exists('\danog\MadelineProto\VoIP')) {
            throw new \danog\MadelineProto\Exception('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.');
        }
        if (!isset($this->calls[$call['id']])) {
            return;
        }
        \danog\MadelineProto\Logger::log(['Discarding call '.$call['id'].'...'], \danog\MadelineProto\Logger::VERBOSE);

        try {
            $res = $this->method_call('phone.discardCall', ['peer' => $call, 'duration' => time() - $this->calls[$call['id']]->whenCreated(), 'connection_id' => $this->calls[$call['id']]->getPreferredRelayID(), 'reason' => $reason], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc !== 'CALL_ALREADY_DECLINED') {
                throw $e;
            }
        }
        if (!empty($rating)) {
            \danog\MadelineProto\Logger::log(['Setting rating for call '.$call['id'].'...'], \danog\MadelineProto\Logger::VERBOSE);
            $this->method_call('phone.setCallRating', ['peer' => $call, 'rating' => $rating['rating'], 'comment' => $rating['comment']], ['datacenter' => $this->datacenter->curdc]);
        }
        if ($need_debug) {//} && isset($this->calls[$call['id']]->storage['not_modified'])) {
            \danog\MadelineProto\Logger::log(['Saving debug data for call '.$call['id'].'...'], \danog\MadelineProto\Logger::VERBOSE);
            $this->method_call('phone.saveCallDebug', ['peer' => $call, 'debug' => $this->calls[$call['id']]->getDebugLog()], ['datacenter' => $this->datacenter->curdc]);
        }
        $update = ['_' => 'updatePhoneCall', 'phone_call' => $this->calls[$call['id']]];
        if (isset($this->settings['pwr']['strict']) && $this->settings['pwr']['strict']) {
            $this->pwr_update_handler($update);
        } else {
            in_array($this->settings['updates']['callback'], [['danog\MadelineProto\API', 'get_updates_update_handler'], 'get_updates_update_handler']) ? $this->get_updates_update_handler($update) : $this->settings['updates']['callback']($update);
        }
        unset($this->calls[$call['id']]);
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
    }
}
