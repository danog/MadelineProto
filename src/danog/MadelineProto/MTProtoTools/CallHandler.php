<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Deferred;
use Amp\Promise;

/**
 * Manages method and object calls.
 */
trait CallHandler
{
    /**
     * Synchronous wrapper for methodCall.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     * @param array  $aargs  Additional arguments
     *
     * @return array
     */
    public function methodCall(string $method, $args = [], array $aargs = ['msg_id' => null])
    {
        return \danog\MadelineProto\Tools::wait($this->methodCallAsyncRead($method, $args, $aargs));
    }

    /**
     * Call method and wait asynchronously for response.
     *
     * If the $aargs['noResponse'] is true, will not wait for a response.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     * @param array  $aargs  Additional arguments
     *
     * @return Promise
     */
    public function methodCallAsyncRead(string $method, $args = [], array $aargs = ['msg_id' => null]): Promise
    {
        $deferred = new Deferred;
        $this->datacenter->waitGetConnection($aargs['datacenter'] ?? $this->datacenter->curdc)->onResolve(
            static function ($e, $res) use (&$method, &$args, &$aargs, &$deferred) {
                if ($e) {
                    throw $e;
                }
                $deferred->resolve($res->methodCallAsyncRead($method, $args, $aargs));
            }
        );

        return $deferred->promise();
    }
    /**
     * Call method and make sure it is asynchronously sent.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     * @param array  $aargs  Additional arguments
     *
     * @return Promise
     */
    public function methodCallAsyncWrite(string $method, $args = [], array $aargs = ['msg_id' => null]): Promise
    {
        $deferred = new Deferred;
        $this->datacenter->waitGetConnection($aargs['datacenter'] ?? $this->datacenter->curdc)->onResolve(
            static function ($e, $res) use (&$method, &$args, &$aargs, &$deferred) {
                if ($e) {
                    throw $e;
                }
                $deferred->resolve($res->methodCallAsyncWrite($method, $args, $aargs));
            }
        );

        return $deferred->promise();
    }
}
