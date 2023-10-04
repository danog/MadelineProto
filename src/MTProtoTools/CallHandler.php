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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

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
        return ($this->datacenter->waitGetConnection($datacenter ?? $this->datacenter->currentDatacenter))->methodCallAsyncRead($method, $args);
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
        return ($this->datacenter->waitGetConnection($datacenter ?? $this->datacenter->currentDatacenter))->methodCallAsyncWrite($method, $args);
    }
}
