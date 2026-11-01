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
                $datacenter ??= $this->datacenter->currentDatacenter;
                if (($args['specialMethodType'] ?? null) !== SpecialMethodType::FILE_RELATED) {
                    return $this->datacenter->waitGetConnection($datacenter)->methodCallAsyncRead($method, $args);
                }
                $shared = $this->datacenter->getDataCenterConnection($this->getFileDatacenter($datacenter));
                $connection = $shared->waitGetFileConnection($args['cancellation'] ?? null);
                try {
                    return $connection->methodCallAsyncRead($method, $args);
                } finally {
                    $shared->fileRequestDone($connection);
                }
            },
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
        $datacenter ??= $this->datacenter->currentDatacenter;
        if (($args['specialMethodType'] ?? null) !== SpecialMethodType::FILE_RELATED) {
            return $this->datacenter->waitGetConnection($datacenter)->methodCallAsyncWrite($method, $args);
        }
        $shared = $this->datacenter->getDataCenterConnection($this->getFileDatacenter($datacenter));
        $connection = $shared->waitGetFileConnection($args['cancellation'] ?? null);
        try {
            $result = $connection->methodCallAsyncWrite($method, $args);
        } catch (\Throwable $e) {
            $shared->fileRequestDone($connection);
            throw $e;
        }
        $result->finally(static fn () => $shared->fileRequestDone($connection));
        return $result;
    }
    /**
     * Get the DC to send a file request for DC $datacenter to: its media DC, if it has one.
     */
    private function getFileDatacenter(int $datacenter): int
    {
        return $datacenter > 0 && $this->datacenter->has(-$datacenter) ? -$datacenter : $datacenter;
    }
}
