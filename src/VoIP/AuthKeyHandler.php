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

use Amp\DeferredFuture;
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
use Throwable;

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
    /** @var array<int, VoIP> */
    private array $calls = [];
    private array $pendingCalls = [];
    /**
     * Request VoIP call.
     *
     * @param mixed $user User
     */
    public function requestCall(mixed $user)
    {
        $user = ($this->getInfo($user));
        if (!isset($user['InputUser']) || $user['InputUser']['_'] === 'inputUserSelf') {
            throw new PeerNotInDbException();
        }
        $user = $user['bot_api_id'];
        if (isset($this->pendingCalls[$user])) {
            return $this->pendingCalls[$user]->await();
        }
        $deferred = new DeferredFuture;
        $this->pendingCalls[$user] = $deferred->getFuture();
        
        try {
            $this->logger->logger(\sprintf('Calling %s...', $user), Logger::VERBOSE);
            $dh_config = ($this->getDhConfig());
            $this->logger->logger('Generating a...', Logger::VERBOSE);
            $a = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
            $this->logger->logger('Generating g_a...', Logger::VERBOSE);
            $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
            Crypt::checkG($g_a, $dh_config['p']);
            $res = $this->methodCallAsyncRead('phone.requestCall', ['user_id' => $user, 'g_a_hash' => \hash('sha256', $g_a->toBytes(), true), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_p2p' => true, 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => 92]])['phone_call'];
            $res['a'] = $a;
            $res['g_a'] = \str_pad($g_a->toBytes(), 256, \chr(0), STR_PAD_LEFT);
            $this->calls[$res['id']] = $controller = new VoIP($this, $res);
            unset($this->pendingCalls[$user]);
            $deferred->complete($controller);
        } catch (Throwable $e) {
            unset($this->pendingCalls[$user]);
            $deferred->error($e);
        }
        return $deferred->getFuture()->await();
    }
    /**
     * Get call status.
     *
     * @param int $id Call ID
     */
    public function callStatus(int $id): ?CallState
    {
        if (isset($this->calls[$id])) {
            return $this->calls[$id]->getCallState();
        }
        return null;
    }

    /** @internal */
    public function cleanupCall(int $id): void {
        unset($this->calls[$id]);
    }
}
