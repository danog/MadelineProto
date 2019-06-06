<?php

/**
 * AuthKeyHandler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
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
        return $this->wait($this->request_call_async($user));
    }

    public function accept_call($user)
    {
        return $this->wait($this->accept_call_async($user));
    }

    public function discard_call($call, $reason, $rating = [], $need_debug = true)
    {
        return $this->wait($this->discard_call_async($call, $reason, $rating, $need_debug));
    }

    public function request_call_async($user)
    {
        if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception(['extension', 'libtgvoip']);
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        $user = yield $this->get_info_async($user);
        if (!isset($user['InputUser']) || $user['InputUser']['_'] === 'inputUserSelf') {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['peer_not_in_db']);
        }
        $user = $user['InputUser'];
        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['calling_user'], $user['user_id']), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = yield $this->get_dh_config_async();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['generating_a'], \danog\MadelineProto\Logger::VERBOSE);
        $a = \phpseclib\Math\BigInteger::randomRange(\danog\MadelineProto\Magic::$two, $dh_config['p']->subtract(\danog\MadelineProto\Magic::$two));
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['generating_g_a'], \danog\MadelineProto\Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        $this->check_G($g_a, $dh_config['p']);
        $controller = new \danog\MadelineProto\VoIP(true, $user['user_id'], $this, \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED);
        $controller->storage = ['a' => $a, 'g_a' => str_pad($g_a->toBytes(), 256, chr(0), \STR_PAD_LEFT)];
        $res = yield $this->method_call_async_read('phone.requestCall', ['user_id' => $user, 'g_a_hash' => hash('sha256', $g_a->toBytes(), true), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_p2p' => true, 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => \danog\MadelineProto\VoIP::getConnectionMaxLayer()]], ['datacenter' => $this->datacenter->curdc]);
        $controller->setCall($res['phone_call']);
        $this->calls[$res['phone_call']['id']] = $controller;
        yield $this->updaters[false]->resume();

        return $controller;
    }

    public function accept_call_async($call)
    {
        if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception();
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        if ($this->call_status($call['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED) {
            $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_error_1'], $call['id']));

            return false;
        }
        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['accepting_call'], $this->calls[$call['id']]->getOtherID()), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = yield $this->get_dh_config_async();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['generating_b'], \danog\MadelineProto\Logger::VERBOSE);
        $b = \phpseclib\Math\BigInteger::randomRange(\danog\MadelineProto\Magic::$two, $dh_config['p']->subtract(\danog\MadelineProto\Magic::$two));
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        $this->check_G($g_b, $dh_config['p']);

        try {
            $res = yield $this->method_call_async_read('phone.acceptCall', ['peer' => $call, 'g_b' => $g_b->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'udp_p2p' => true, 'min_layer' => 65, 'max_layer' => \danog\MadelineProto\VoIP::getConnectionMaxLayer()]], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_already_accepted'], $call['id']));

                return true;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['call_already_declined']);
                yield $this->discard_call_async($call['id'], 'phoneCallDiscardReasonHangup');

                return false;
            }

            throw $e;
        }
        $this->calls[$res['phone_call']['id']]->storage['b'] = $b;
        yield $this->updaters[false]->resume();

        return true;
    }

    public function confirm_call_async($params)
    {
        if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception(['extension', 'libtgvoip']);
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        if ($this->call_status($params['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED) {
            $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_error_2'], $params['id']));

            return false;
        }
        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_confirming'], $this->calls[$params['id']]->getOtherID()), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = yield $this->get_dh_config_async();
        $params['g_b'] = new \phpseclib\Math\BigInteger($params['g_b'], 256);
        $this->check_G($params['g_b'], $dh_config['p']);
        $key = str_pad($params['g_b']->powMod($this->calls[$params['id']]->storage['a'], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT);
        $res = (yield $this->method_call_async_read('phone.confirmCall', ['key_fingerprint' => substr(sha1($key, true), -8), 'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'g_a' => $this->calls[$params['id']]->storage['g_a'], 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => \danog\MadelineProto\VoIP::getConnectionMaxLayer()]], ['datacenter' => $this->datacenter->curdc]))['phone_call'];
        $visualization = [];
        $length = new \phpseclib\Math\BigInteger(count(\danog\MadelineProto\Magic::$emojis));
        foreach (str_split(hash('sha256', $key.str_pad($this->calls[$params['id']]->storage['g_a'], 256, chr(0), \STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = chr(ord($number[0]) & 0x7f);
            $visualization[] = \danog\MadelineProto\Magic::$emojis[(int) (new \phpseclib\Math\BigInteger((int) $number, 256))->divide($length)[1]->toString()];
        }
        $this->calls[$params['id']]->setVisualization($visualization);

        $this->calls[$params['id']]->configuration['endpoints'] = array_merge([$res['connection']], $res['alternative_connections'], $this->calls[$params['id']]->configuration['endpoints']);
        $this->calls[$params['id']]->configuration = array_merge(['recv_timeout' => $this->config['call_receive_timeout_ms'] / 1000, 'init_timeout' => $this->config['call_connect_timeout_ms'] / 1000, 'data_saving' => \danog\MadelineProto\VoIP::DATA_SAVING_NEVER, 'enable_NS' => true, 'enable_AEC' => true, 'enable_AGC' => true, 'auth_key' => $key, 'auth_key_id' => substr(sha1($key, true), -8), 'call_id' => substr(hash('sha256', $key, true), -16), 'network_type' => \danog\MadelineProto\VoIP::NET_TYPE_ETHERNET], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();
        $res = $this->calls[$params['id']]->startTheMagic();

        return $res;
    }

    public function complete_call_async($params)
    {
        if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception(['extension', 'libtgvoip']);
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        if ($this->call_status($params['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED || !isset($this->calls[$params['id']]->storage['b'])) {
            $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_error_3'], $params['id']));

            return false;
        }
        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_completing'], $this->calls[$params['id']]->getOtherID()), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = yield $this->get_dh_config_async();
        if (hash('sha256', $params['g_a_or_b'], true) != $this->calls[$params['id']]->storage['g_a_hash']) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['invalid_g_a']);
        }
        $params['g_a_or_b'] = new \phpseclib\Math\BigInteger($params['g_a_or_b'], 256);
        $this->check_G($params['g_a_or_b'], $dh_config['p']);
        $key = str_pad($params['g_a_or_b']->powMod($this->calls[$params['id']]->storage['b'], $dh_config['p'])->toBytes(), 256, chr(0), \STR_PAD_LEFT);
        if (substr(sha1($key, true), -8) != $params['key_fingerprint']) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['fingerprint_invalid']);
        }
        $visualization = [];
        $length = new \phpseclib\Math\BigInteger(count(\danog\MadelineProto\Magic::$emojis));
        foreach (str_split(hash('sha256', $key.str_pad($params['g_a_or_b']->toBytes(), 256, chr(0), \STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = chr(ord($number[0]) & 0x7f);
            $visualization[] = \danog\MadelineProto\Magic::$emojis[(int) (new \phpseclib\Math\BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        $this->calls[$params['id']]->setVisualization($visualization);
        $this->calls[$params['id']]->configuration['endpoints'] = array_merge([$params['connection']], $params['alternative_connections'], $this->calls[$params['id']]->configuration['endpoints']);
        $this->calls[$params['id']]->configuration = array_merge(['recv_timeout' => $this->config['call_receive_timeout_ms'] / 1000, 'init_timeout' => $this->config['call_connect_timeout_ms'] / 1000, 'data_saving' => \danog\MadelineProto\VoIP::DATA_SAVING_NEVER, 'enable_NS' => true, 'enable_AEC' => true, 'enable_AGC' => true, 'auth_key' => $key, 'auth_key_id' => substr(sha1($key, true), -8), 'call_id' => substr(hash('sha256', $key, true), -16), 'network_type' => \danog\MadelineProto\VoIP::NET_TYPE_ETHERNET], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();

        return $this->calls[$params['id']]->startTheMagic();
    }

    public function call_status($id)
    {
        if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception(['extension', 'libtgvoip']);
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
        if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception(['extension', 'libtgvoip']);
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });

        return $this->calls[$call];
    }

    public function discard_call_async($call, $reason, $rating = [], $need_debug = true)
    {
        if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception(['extension', 'libtgvoip']);
        }
        if (!isset($this->calls[$call['id']])) {
            return;
        }
        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_discarding'], $call['id']), \danog\MadelineProto\Logger::VERBOSE);

        try {
            $res = yield $this->method_call_async_read('phone.discardCall', ['peer' => $call, 'duration' => time() - $this->calls[$call['id']]->whenCreated(), 'connection_id' => $this->calls[$call['id']]->getPreferredRelayID(), 'reason' => $reason], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if (!in_array($e->rpc, ['CALL_ALREADY_DECLINED', 'CALL_ALREADY_ACCEPTED'])) {
                throw $e;
            }
        }
        if (!empty($rating)) {
            $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_set_rating'], $call['id']), \danog\MadelineProto\Logger::VERBOSE);
            yield $this->method_call_async_read('phone.setCallRating', ['peer' => $call, 'rating' => $rating['rating'], 'comment' => $rating['comment']], ['datacenter' => $this->datacenter->curdc]);
        }
        if ($need_debug && isset($this->calls[$call['id']])) {
            $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['call_debug_saving'], $call['id']), \danog\MadelineProto\Logger::VERBOSE);
            yield $this->method_call_async_read('phone.saveCallDebug', ['peer' => $call, 'debug' => $this->calls[$call['id']]->getDebugLog()], ['datacenter' => $this->datacenter->curdc]);
        }
        $update = ['_' => 'updatePhoneCall', 'phone_call' => $this->calls[$call['id']]];
        if (isset($this->settings['pwr']['strict']) && $this->settings['pwr']['strict']) {
            $this->pwr_update_handler($update);
        } else {
            in_array($this->settings['updates']['callback'], [['danog\\MadelineProto\\API', 'get_updates_update_handler'], 'get_updates_update_handler']) ? $this->get_updates_update_handler($update) : $this->settings['updates']['callback']($update);
        }
        unset($this->calls[$call['id']]);
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
    }
}
