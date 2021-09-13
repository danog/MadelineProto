<?php

/**
 * Ping loop.
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

namespace danog\MadelineProto\Loop\Connection;

use danog\Loop\ResumableSignalLoop;

/**
 * Ping loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class PingLoop extends ResumableSignalLoop
{
    use Common;
    /**
     * Main loop.
     *
     * @return \Generator
     */
    public function loop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        $timeout = $shared->getSettings()->getPingInterval();
        $timeoutMs = $timeout * 1000;
        while (true) {
            while (!$shared->hasTempAuthKey()) {
                if (yield $this->waitSignal($this->pause())) {
                    return;
                }
            }
            if (yield $this->waitSignal($this->pause($timeoutMs))) {
                return;
            }
            if (\time() - $connection->getLastChunk() >= $timeout && $shared->hasTempAuthKey()) {
                $API->logger->logger("Ping DC {$datacenter}");
                try {
                    yield from $connection->methodCallAsyncRead('ping', ['ping_id' => \random_bytes(8)]);
                } catch (\Throwable $e) {
                    $API->logger->logger("Error while pinging DC {$datacenter}");
                    $API->logger->logger((string) $e);
                }
            }
        }
    }
    /**
     * Get loop name.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "Ping loop in DC {$this->datacenter}";
    }
}
