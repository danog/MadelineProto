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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\VoIP;

use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Tools;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 */
trait AuthKeyHandler
{
    private $calls = [];
    /**
     * Accept call from VoIP instance.
     *
     * @param \danog\MadelineProto\VoIP $instance Call instance
     * @param array                     $user     User
     *
     * @internal
     *
     * @return mixed
     */
    public function acceptCallFrom($instance, $user)
    {
        $promise = Tools::call((function () use ($instance, $user) {
            if (!$res = yield from $this->acceptCall($user)) {
                $instance->discard();
                return false;
            }
            return $instance;
        })());
        if ($this->wrapper && $this->wrapper->isAsync()) {
            return $promise;
        }
        return Tools::wait($promise);
    }
    /**
     * Undocumented function.
     *
     * @param \danog\MadelineProto\VoIP $instance   Call instance
     * @param array                     $call       Call info
     * @param array                     $reason     Discard reason
     * @param array                     $rating     Rating
     * @param boolean                   $need_debug Needs debug?
     *
     * @internal
     *
     * @return mixed
     */
    public function discardCallFrom($instance, $call, $reason, $rating = [], $need_debug = true)
    {
        $promise = Tools::call(function () use ($instance, $call, $reason, $rating, $need_debug) {
            if (!$res = yield from $this->discardCall($call, $reason, $rating, $need_debug)) {
                return false;
            }
            return $instance;
        });
        if ($this->wrapper && $this->wrapper->isAsync()) {
            return $promise;
        }
        return Tools::wait($promise);
    }
    /**
     * Request VoIP call.
     *
     * @param mixed $user User
     *
     * @return \Generator
     */
    public function requestCall($user): \Generator
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw \danog\MadelineProto\Exception::extension('libtgvoip');
        }
        $user = (yield from $this->getInfo($user));
        if (!isset($user['InputUser']) || $user['InputUser']['_'] === 'inputUserSelf') {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['peer_not_in_db']);
        }
        $user = $user['InputUser'];
        $this->logger->logger(\sprintf("Calling %s...", $user['user_id']), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = (yield from $this->getDhConfig());
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['generating_a'], \danog\MadelineProto\Logger::VERBOSE);
        $a = \phpseclib3\Math\BigInteger::randomRange(\danog\MadelineProto\Magic::$two, $dh_config['p']->subtract(\danog\MadelineProto\Magic::$two));
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['generating_g_a'], \danog\MadelineProto\Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        Crypt::checkG($g_a, $dh_config['p']);
        $controller = new \danog\MadelineProto\VoIP(true, $user['user_id'], $this, \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED);
        $controller->storage = ['a' => $a, 'g_a' => \str_pad($g_a->toBytes(), 256, \chr(0), \STR_PAD_LEFT)];
        $res = yield from $this->methodCallAsyncRead('phone.requestCall', ['user_id' => $user, 'g_a_hash' => \hash('sha256', $g_a->toBytes(), true), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_p2p' => true, 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => \danog\MadelineProto\VoIP::getConnectionMaxLayer()]]);
        $controller->setCall($res['phone_call']);
        $this->calls[$res['phone_call']['id']] = $controller;
        yield $this->updaters[UpdateLoop::GENERIC]->resume();
        return $controller;
    }
    /**
     * Accept call.
     *
     * @param array $call Call
     *
     * @return \Generator
     */
    public function acceptCall(array $call): \Generator
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new \danog\MadelineProto\Exception();
        }
        if ($this->callStatus($call['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED) {
            $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_error_1'], $call['id']));
            return false;
        }
        $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['accepting_call'], $this->calls[$call['id']]->getOtherID()), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = (yield from $this->getDhConfig());
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['generating_b'], \danog\MadelineProto\Logger::VERBOSE);
        $b = \phpseclib3\Math\BigInteger::randomRange(\danog\MadelineProto\Magic::$two, $dh_config['p']->subtract(\danog\MadelineProto\Magic::$two));
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        Crypt::checkG($g_b, $dh_config['p']);
        try {
            $res = yield from $this->methodCallAsyncRead('phone.acceptCall', ['peer' => ['id' => $call['id'], 'access_hash' => $call['access_hash'], '_' => 'inputPhoneCall'], 'g_b' => $g_b->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'udp_p2p' => true, 'min_layer' => 65, 'max_layer' => \danog\MadelineProto\VoIP::getConnectionMaxLayer()]]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_already_accepted'], $call['id']));
                return true;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['call_already_declined']);
                yield from $this->discardCall($call['id'], ['_' => 'phoneCallDiscardReasonHangup']);
                return false;
            }
            throw $e;
        }
        $this->calls[$res['phone_call']['id']]->storage['b'] = $b;
        yield $this->updaters[UpdateLoop::GENERIC]->resume();
        return true;
    }
    /**
     * Confirm call.
     *
     * @param array $params Params
     *
     * @return \Generator
     */
    public function confirmCall(array $params): \Generator
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw \danog\MadelineProto\Exception::extension('libtgvoip');
        }
        if ($this->callStatus($params['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_REQUESTED) {
            $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_error_2'], $params['id']));
            return false;
        }
        $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_confirming'], $this->calls[$params['id']]->getOtherID()), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = (yield from $this->getDhConfig());
        $params['g_b'] = new \phpseclib3\Math\BigInteger((string) $params['g_b'], 256);
        Crypt::checkG($params['g_b'], $dh_config['p']);
        $key = \str_pad($params['g_b']->powMod($this->calls[$params['id']]->storage['a'], $dh_config['p'])->toBytes(), 256, \chr(0), \STR_PAD_LEFT);
        try {
            $res = (yield from $this->methodCallAsyncRead('phone.confirmCall', ['key_fingerprint' => \substr(\sha1($key, true), -8), 'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'g_a' => $this->calls[$params['id']]->storage['g_a'], 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => \danog\MadelineProto\VoIP::getConnectionMaxLayer()]]))['phone_call'];
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_already_accepted'], $params['id']));
                return true;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['call_already_declined']);
                yield from $this->discardCall($params['id'], ['_' => 'phoneCallDiscardReasonHangup']);
                return false;
            }
            throw $e;
        }
        $this->calls[$params['id']]->setCall($res);
        $visualization = [];
        $length = new \phpseclib3\Math\BigInteger(\count(\danog\MadelineProto\Magic::$emojis));
        foreach (\str_split(\hash('sha256', $key.\str_pad($this->calls[$params['id']]->storage['g_a'], 256, \chr(0), \STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = \chr(\ord($number[0]) & 0x7f);
            $visualization[] = \danog\MadelineProto\Magic::$emojis[(int) (new \phpseclib3\Math\BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        $this->calls[$params['id']]->setVisualization($visualization);
        $this->calls[$params['id']]->configuration['endpoints'] = \array_merge($res['connections'], $this->calls[$params['id']]->configuration['endpoints']);
        $this->calls[$params['id']]->configuration = \array_merge(['recv_timeout' => $this->config['call_receive_timeout_ms'] / 1000, 'init_timeout' => $this->config['call_connect_timeout_ms'] / 1000, 'data_saving' => \danog\MadelineProto\VoIP::DATA_SAVING_NEVER, 'enable_NS' => true, 'enable_AEC' => true, 'enable_AGC' => true, 'auth_key' => $key, 'auth_key_id' => \substr(\sha1($key, true), -8), 'call_id' => \substr(\hash('sha256', $key, true), -16), 'network_type' => \danog\MadelineProto\VoIP::NET_TYPE_ETHERNET], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();
        $res = $this->calls[$params['id']]->startTheMagic();
        return $res;
    }
    /**
     * Complete call handshake.
     *
     * @param array $params Params
     *
     * @return \Generator
     */
    public function completeCall(array $params): \Generator
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw \danog\MadelineProto\Exception::extension('libtgvoip');
        }
        if ($this->callStatus($params['id']) !== \danog\MadelineProto\VoIP::CALL_STATE_ACCEPTED || !isset($this->calls[$params['id']]->storage['b'])) {
            $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_error_3'], $params['id']));
            return false;
        }
        $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_completing'], $this->calls[$params['id']]->getOtherID()), \danog\MadelineProto\Logger::VERBOSE);
        $dh_config = (yield from $this->getDhConfig());
        if (\hash('sha256', $params['g_a_or_b'], true) != $this->calls[$params['id']]->storage['g_a_hash']) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['invalid_g_a']);
        }
        $params['g_a_or_b'] = new \phpseclib3\Math\BigInteger((string) $params['g_a_or_b'], 256);
        Crypt::checkG($params['g_a_or_b'], $dh_config['p']);
        $key = \str_pad($params['g_a_or_b']->powMod($this->calls[$params['id']]->storage['b'], $dh_config['p'])->toBytes(), 256, \chr(0), \STR_PAD_LEFT);
        if (\substr(\sha1($key, true), -8) != $params['key_fingerprint']) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['fingerprint_invalid']);
        }
        $visualization = [];
        $length = new \phpseclib3\Math\BigInteger(\count(\danog\MadelineProto\Magic::$emojis));
        foreach (\str_split(\hash('sha256', $key.\str_pad($params['g_a_or_b']->toBytes(), 256, \chr(0), \STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = \chr(\ord($number[0]) & 0x7f);
            $visualization[] = \danog\MadelineProto\Magic::$emojis[(int) (new \phpseclib3\Math\BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        $this->calls[$params['id']]->setVisualization($visualization);
        $this->calls[$params['id']]->configuration['endpoints'] = \array_merge($params['connections'], $this->calls[$params['id']]->configuration['endpoints']);
        $this->calls[$params['id']]->configuration = \array_merge(['recv_timeout' => $this->config['call_receive_timeout_ms'] / 1000, 'init_timeout' => $this->config['call_connect_timeout_ms'] / 1000, 'data_saving' => \danog\MadelineProto\VoIP::DATA_SAVING_NEVER, 'enable_NS' => true, 'enable_AEC' => true, 'enable_AGC' => true, 'auth_key' => $key, 'auth_key_id' => \substr(\sha1($key, true), -8), 'call_id' => \substr(\hash('sha256', $key, true), -16), 'network_type' => \danog\MadelineProto\VoIP::NET_TYPE_ETHERNET], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();
        return $this->calls[$params['id']]->startTheMagic();
    }
    /**
     * Get call status.
     *
     * @param int $id Call ID
     *
     * @return integer
     */
    public function callStatus($id): int
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw \danog\MadelineProto\Exception::extension('libtgvoip');
        }
        if (isset($this->calls[$id])) {
            return $this->calls[$id]->getCallState();
        }
        return \danog\MadelineProto\VoIP::CALL_STATE_NONE;
    }
    /**
     * Get call info.
     *
     * @param int $call Call ID
     *
     * @return array
     */
    public function getCall($call): array
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw \danog\MadelineProto\Exception::extension('libtgvoip');
        }
        return $this->calls[$call];
    }
    /**
     * Discard call.
     *
     * @param array   $call       Call
     * @param array $reason
     * @param array   $rating     Rating
     * @param boolean $need_debug Need debug?
     *
     * @return \Generator
     */
    public function discardCall(array $call, array $reason, array $rating = [], bool $need_debug = true): \Generator
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw \danog\MadelineProto\Exception::extension('libtgvoip');
        }
        if (!isset($this->calls[$call['id']])) {
            return;
        }
        $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_discarding'], $call['id']), \danog\MadelineProto\Logger::VERBOSE);
        try {
            $res = yield from $this->methodCallAsyncRead('phone.discardCall', ['peer' => $call, 'duration' => \time() - $this->calls[$call['id']]->whenCreated(), 'connection_id' => $this->calls[$call['id']]->getPreferredRelayID(), 'reason' => $reason]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if (!\in_array($e->rpc, ['CALL_ALREADY_DECLINED', 'CALL_ALREADY_ACCEPTED'])) {
                throw $e;
            }
        }
        if (!empty($rating)) {
            $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_set_rating'], $call['id']), \danog\MadelineProto\Logger::VERBOSE);
            yield from $this->methodCallAsyncRead('phone.setCallRating', ['peer' => $call, 'rating' => $rating['rating'], 'comment' => $rating['comment']]);
        }
        if ($need_debug && isset($this->calls[$call['id']])) {
            $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['call_debug_saving'], $call['id']), \danog\MadelineProto\Logger::VERBOSE);
            yield from $this->methodCallAsyncRead('phone.saveCallDebug', ['peer' => $call, 'debug' => $this->calls[$call['id']]->getDebugLog()]);
        }
        $update = ['_' => 'updatePhoneCall', 'phone_call' => $this->calls[$call['id']]];
        $this->updates[$this->updates_key++] = $update;
        unset($this->calls[$call['id']]);
    }
    /**
     * Check state of calls.
     *
     * @internal
     *
     * @return void
     */
    public function checkCalls(): void
    {
        \array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $this->logger("Discarding ended call...");
                $controller->discard();
                unset($this->calls[$id]);
            }
        });
    }
}
