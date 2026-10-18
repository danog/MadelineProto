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
            function (array $args) use ($method, $datacenter) {
                // Not saved with the arguments of the call: a resumed call goes through the current business connection.
                $datacenter = $this->businessCall($method, $args) ?? $datacenter;
                return ($this->datacenter->waitGetConnection($datacenter ?? $this->datacenter->currentDatacenter))->methodCallAsyncRead($method, $args);
            },
        );
    }
    /**
     * When logged in through a business connection, make the calls that act on behalf of the user go through it.
     *
     * Wrapped calls are sent to the DC of the connection, which is returned.
     * A businessConnectionId argument set to null runs the call as the bot.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     */
    private function businessCall(string $method, array &$args): ?int
    {
        $connection = $this->businessConnection;
        if ($connection === null || \array_key_exists('businessConnectionId', $args)) {
            return null;
        }
        if ($connection->wraps($method, $args)) {
            $args['businessConnectionId'] = $connection->id;
            return $connection->dcId;
        }
        if ($method === 'messages.uploadMedia') {
            $args['business_connection_id'] ??= $connection->id;
        } elseif ($method === 'photos.uploadProfilePhoto' || $method === 'photos.updateProfilePhoto') {
            // An upload (wraps() is false for photos.updateProfilePhoto with an existing photo):
            // bots upload the profile photo of the user who connected them with the bot parameter.
            $args['bot'] ??= $connection->userId;
        }
        return null;
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
        $datacenter = $this->businessCall($method, $args) ?? $datacenter;
        return ($this->datacenter->waitGetConnection($datacenter ?? $this->datacenter->currentDatacenter))->methodCallAsyncWrite($method, $args);
    }
}
