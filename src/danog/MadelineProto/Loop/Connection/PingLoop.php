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
use danog\MadelineProto\Logger;

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
     */
    public function loop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        $timeout = $shared->getSettings()->getPingInterval();
        $timeoutMs = $timeout * 1000;
        $timeoutDisconnect = $timeout+15;
        while (true) {
            while (!$shared->hasTempAuthKey()) {
                $API->logger->logger("Waiting for temp key in {$this}", Logger::LEVEL_ULTRA_VERBOSE);
                if (yield $this->waitSignal($this->pause())) {
                    $API->logger->logger("Exiting in {$this} while waiting for temp key (init)!", Logger::LEVEL_ULTRA_VERBOSE);
                    return;
                }
            }
            $API->logger->logger("Ping DC {$datacenter}");
            try {
                yield from $connection->methodCallAsyncRead('ping_delay_disconnect', ['ping_id' => \random_bytes(8), 'disconnect_delay' => $timeoutDisconnect]);
            } catch (\Throwable $e) {
                $API->logger->logger("Error while pinging DC {$datacenter}");
                $API->logger->logger((string) $e);
            }
            if (yield $this->waitSignal($this->pause($timeoutMs))) {
                $API->logger->logger("Exiting in {$this} due to signal!", Logger::LEVEL_ULTRA_VERBOSE);
                return;
            }
        }
    }
    /**
     * Get loop name.
     *
     */
    public function __toString(): string
    {
        return "Ping loop in DC {$this->datacenter}";
    }
}
