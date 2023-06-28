<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\VoIP;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\VoIP;
use phpseclib3\Math\BigInteger;

use const STR_PAD_LEFT;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 *
 * @internal
 */
trait AuthKeyHandler
{
    private array $calls = [];
    /**
     * Accept call from VoIP instance.
     *
     * @param VoIP $instance Call instance
     * @param array                     $user     User
     * @internal
     */
    public function acceptCallFrom(VoIP $instance, array $user): ?VoIP
    {
        if (!$this->acceptCall($user)) {
            $instance->discard();
            return null;
        }
        return $instance;
    }
    /**
     * @param VoIP $instance Call instance
     * @param array                     $call       Call info
     * @param array                     $reason     Discard reason
     * @param array                     $rating     Rating
     * @param boolean                   $need_debug Needs debug?
     * @internal
     */
    public function discardCallFrom(VoIP $instance, array $call, array $reason, array $rating = [], bool $need_debug = true): VoIP
    {
        $this->discardCall($call, $reason, $rating, $need_debug);
        return $instance;
    }
    /**
     * Request VoIP call.
     *
     * @param mixed $user User
     */
    public function requestCall(mixed $user)
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw Exception::extension('libtgvoip');
        }
        $user = ($this->getInfo($user));
        if (!isset($user['InputUser']) || $user['InputUser']['_'] === 'inputUserSelf') {
            throw new PeerNotInDbException();
        }
        $user = $user['InputUser'];
        $this->logger->logger(\sprintf('Calling %s...', $user['user_id']), Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        $this->logger->logger('Generating a...', Logger::VERBOSE);
        $a = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
        $this->logger->logger('Generating g_a...', Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        Crypt::checkG($g_a, $dh_config['p']);
        $controller = new VoIP(true, $user['user_id'], $this, VoIP::CALL_STATE_REQUESTED);
        $controller->storage = ['a' => $a, 'g_a' => \str_pad($g_a->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
        $res = $this->methodCallAsyncRead('phone.requestCall', ['user_id' => $user, 'g_a_hash' => \hash('sha256', $g_a->toBytes(), true), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_p2p' => true, 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => VoIP::getConnectionMaxLayer()]]);
        $controller->setCall($res['phone_call']);
        $this->calls[$res['phone_call']['id']] = $controller;
        $this->updaters[UpdateLoop::GENERIC]->resume();
        return $controller;
    }
    /**
     * Accept call.
     *
     * @param array $call Call
     */
    public function acceptCall(array $call): bool
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw new Exception();
        }
        if ($this->callStatus($call['id']) !== VoIP::CALL_STATE_ACCEPTED) {
            $this->logger->logger(\sprintf(Lang::$current_lang['call_error_1'], $call['id']));
            return false;
        }
        $this->logger->logger(\sprintf(Lang::$current_lang['accepting_call'], $this->calls[$call['id']]->getOtherID()), Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        $this->logger->logger('Generating b...', Logger::VERBOSE);
        $b = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        Crypt::checkG($g_b, $dh_config['p']);
        try {
            $res = $this->methodCallAsyncRead('phone.acceptCall', ['peer' => ['id' => $call['id'], 'access_hash' => $call['access_hash'], '_' => 'inputPhoneCall'], 'g_b' => $g_b->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'udp_p2p' => true, 'min_layer' => 65, 'max_layer' => VoIP::getConnectionMaxLayer()]]);
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                $this->logger->logger(\sprintf(Lang::$current_lang['call_already_accepted'], $call['id']));
                return true;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                $this->logger->logger(Lang::$current_lang['call_already_declined']);
                $this->discardCall($call['id'], ['_' => 'phoneCallDiscardReasonHangup']);
                return false;
            }
            throw $e;
        }
        $this->calls[$res['phone_call']['id']]->storage['b'] = $b;
        $this->updaters[UpdateLoop::GENERIC]->resume();
        return true;
    }
    /**
     * Confirm call.
     *
     * @param array $params Params
     */
    public function confirmCall(array $params)
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw Exception::extension('libtgvoip');
        }
        if ($this->callStatus($params['id']) !== VoIP::CALL_STATE_REQUESTED) {
            $this->logger->logger(\sprintf(Lang::$current_lang['call_error_2'], $params['id']));
            return false;
        }
        $this->logger->logger(\sprintf(Lang::$current_lang['call_confirming'], $this->calls[$params['id']]->getOtherID()), Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        $params['g_b'] = new BigInteger((string) $params['g_b'], 256);
        Crypt::checkG($params['g_b'], $dh_config['p']);
        $key = \str_pad($params['g_b']->powMod($this->calls[$params['id']]->storage['a'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
        try {
            $res = ($this->methodCallAsyncRead('phone.confirmCall', ['key_fingerprint' => \substr(\sha1($key, true), -8), 'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'g_a' => $this->calls[$params['id']]->storage['g_a'], 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => VoIP::getConnectionMaxLayer()]]))['phone_call'];
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                $this->logger->logger(\sprintf(Lang::$current_lang['call_already_accepted'], $params['id']));
                return true;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                $this->logger->logger(Lang::$current_lang['call_already_declined']);
                $this->discardCall($params['id'], ['_' => 'phoneCallDiscardReasonHangup']);
                return false;
            }
            throw $e;
        }
        $this->calls[$params['id']]->setCall($res);
        $visualization = [];
        $length = new BigInteger(\count(Magic::$emojis));
        foreach (\str_split(\hash('sha256', $key.\str_pad($this->calls[$params['id']]->storage['g_a'], 256, \chr(0), STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = \chr(\ord($number[0]) & 0x7f);
            $visualization[] = Magic::$emojis[(int) (new BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        $this->calls[$params['id']]->setVisualization($visualization);
        $this->calls[$params['id']]->configuration['endpoints'] = \array_merge($res['connections'], $this->calls[$params['id']]->configuration['endpoints']);
        $this->calls[$params['id']]->configuration = \array_merge(['recv_timeout' => $this->config['call_receive_timeout_ms'] / 1000, 'init_timeout' => $this->config['call_connect_timeout_ms'] / 1000, 'data_saving' => VoIP::DATA_SAVING_NEVER, 'enable_NS' => true, 'enable_AEC' => true, 'enable_AGC' => true, 'auth_key' => $key, 'auth_key_id' => \substr(\sha1($key, true), -8), 'call_id' => \substr(\hash('sha256', $key, true), -16), 'network_type' => VoIP::NET_TYPE_ETHERNET], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();
        return $this->calls[$params['id']]->startTheMagic();
    }
    /**
     * Complete call handshake.
     *
     * @param array $params Params
     */
    public function completeCall(array $params)
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw Exception::extension('libtgvoip');
        }
        if ($this->callStatus($params['id']) !== VoIP::CALL_STATE_ACCEPTED || !isset($this->calls[$params['id']]->storage['b'])) {
            $this->logger->logger(\sprintf(Lang::$current_lang['call_error_3'], $params['id']));
            return false;
        }
        $this->logger->logger(\sprintf(Lang::$current_lang['call_completing'], $this->calls[$params['id']]->getOtherID()), Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        if (\hash('sha256', $params['g_a_or_b'], true) != $this->calls[$params['id']]->storage['g_a_hash']) {
            throw new SecurityException('Invalid g_a!');
        }
        $params['g_a_or_b'] = new BigInteger((string) $params['g_a_or_b'], 256);
        Crypt::checkG($params['g_a_or_b'], $dh_config['p']);
        $key = \str_pad($params['g_a_or_b']->powMod($this->calls[$params['id']]->storage['b'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
        if (\substr(\sha1($key, true), -8) != $params['key_fingerprint']) {
            throw new SecurityException(Lang::$current_lang['fingerprint_invalid']);
        }
        $visualization = [];
        $length = new BigInteger(\count(Magic::$emojis));
        foreach (\str_split(\hash('sha256', $key.\str_pad($params['g_a_or_b']->toBytes(), 256, \chr(0), STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = \chr(\ord($number[0]) & 0x7f);
            $visualization[] = Magic::$emojis[(int) (new BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        $this->calls[$params['id']]->setVisualization($visualization);
        $this->calls[$params['id']]->configuration['endpoints'] = \array_merge($params['connections'], $this->calls[$params['id']]->configuration['endpoints']);
        $this->calls[$params['id']]->configuration = \array_merge(['recv_timeout' => $this->config['call_receive_timeout_ms'] / 1000, 'init_timeout' => $this->config['call_connect_timeout_ms'] / 1000, 'data_saving' => VoIP::DATA_SAVING_NEVER, 'enable_NS' => true, 'enable_AEC' => true, 'enable_AGC' => true, 'auth_key' => $key, 'auth_key_id' => \substr(\sha1($key, true), -8), 'call_id' => \substr(\hash('sha256', $key, true), -16), 'network_type' => VoIP::NET_TYPE_ETHERNET], $this->calls[$params['id']]->configuration);
        $this->calls[$params['id']]->parseConfig();
        return $this->calls[$params['id']]->startTheMagic();
    }
    /**
     * Get call status.
     *
     * @param int $id Call ID
     */
    public function callStatus(int $id): int
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw Exception::extension('libtgvoip');
        }
        if (isset($this->calls[$id])) {
            return $this->calls[$id]->getCallState();
        }
        return VoIP::CALL_STATE_NONE;
    }
    /**
     * Get call info.
     *
     * @param int $call Call ID
     */
    public function getCall(int $call): array
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw Exception::extension('libtgvoip');
        }
        return $this->calls[$call];
    }
    /**
     * Discard call.
     *
     * @param array   $call       Call
     * @param array   $rating     Rating
     * @param boolean $need_debug Need debug?
     */
    public function discardCall(array $call, array $reason, array $rating = [], bool $need_debug = true): ?VoIP
    {
        if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
            throw Exception::extension('libtgvoip');
        }
        if (!isset($this->calls[$call['id']])) {
            return null;
        }
        $this->logger->logger(\sprintf(Lang::$current_lang['call_discarding'], $call['id']), Logger::VERBOSE);
        try {
            $res = $this->methodCallAsyncRead('phone.discardCall', ['peer' => $call, 'duration' => \time() - $this->calls[$call['id']]->whenCreated(), 'connection_id' => $this->calls[$call['id']]->getPreferredRelayID(), 'reason' => $reason]);
        } catch (RPCErrorException $e) {
            if (!\in_array($e->rpc, ['CALL_ALREADY_DECLINED', 'CALL_ALREADY_ACCEPTED'], true)) {
                throw $e;
            }
        }
        if (!empty($rating)) {
            $this->logger->logger(\sprintf('Setting rating for call %s...', $call['id']), Logger::VERBOSE);
            $this->methodCallAsyncRead('phone.setCallRating', ['peer' => $call, 'rating' => $rating['rating'], 'comment' => $rating['comment']]);
        }
        if ($need_debug && isset($this->calls[$call['id']])) {
            $this->logger->logger(\sprintf('Saving debug data for call %s...', $call['id']), Logger::VERBOSE);
            $this->methodCallAsyncRead('phone.saveCallDebug', ['peer' => $call, 'debug' => $this->calls[$call['id']]->getDebugLog()]);
        }
        $c = $this->calls[$call['id']];
        unset($this->calls[$call['id']]);
        return $c;
    }
    /**
     * Check state of calls.
     *
     * @internal
     */
    public function checkCalls(): void
    {
        \array_walk($this->calls, function ($controller, $id): void {
            if ($controller->getCallState() === VoIP::CALL_STATE_ENDED) {
                $this->logger('Discarding ended call...');
                $controller->discard();
                unset($this->calls[$id]);
            }
        });
    }
}
