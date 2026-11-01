<?php

declare(strict_types=1);

/**
 * CallHandler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\Settings;
use danog\MadelineProto\WrappedFuture;

/**
 * Manages method and object calls.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait CallHandler
{
    /**
     * Call method and wait asynchronously for response.
     *
     * @internal
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     */
    public function methodCallAsyncRead(string $method, array $args, ?int $datacenter = null)
    {
        if (isset($args['specialMethodType']) && $args['specialMethodType'] === SpecialMethodType::USER_RELATED) {
            $datacenter = $this->loginState->getState()->authorizedDc;
        }
        // Calls uploading files are resumed if interrupted by a restart.
        return $this->makeResumableCall(
            'method',
            $method,
            $args,
            fn (array $args) => ($this->datacenter->waitGetConnection($datacenter ?? $this->datacenter->currentDatacenter, self::getSessionAffinity($args)))->methodCallAsyncRead($method, $args),
        );
    }
    /**
     * Call method and make sure it is asynchronously sent.
     *
     * @internal
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     */
    public function methodCallAsyncWrite(string $method, array $args, ?int $datacenter = null): WrappedFuture
    {
        return ($this->datacenter->waitGetConnection($datacenter ?? $this->datacenter->currentDatacenter, self::getSessionAffinity($args)))->methodCallAsyncWrite($method, $args);
    }
    /**
     * With parallel sessions to the DC, the calls about the same peer are sent through the same session, so that
     * they reach the server in order as with a single session (TDLib sends them in the same session too).
     *
     * Best effort: the same peer given in different forms (a username and an ID, say) may use different sessions.
     *
     * @psalm-pure
     */
    private static function getSessionAffinity(array $args): ?string
    {
        $peer = $args['peer'] ?? null;
        if (\is_array($peer)) {
            $peer = $peer['user_id'] ?? $peer['chat_id'] ?? $peer['channel_id'] ?? null;
        }
        return \is_int($peer) || \is_string($peer) ? (string) $peer : null;
    }
}
