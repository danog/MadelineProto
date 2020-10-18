<?php

/**
 * HttpWait loop.
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
use danog\MadelineProto\MTProto\OutgoingMessage;

/**
 * HttpWait loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HttpWaitLoop extends ResumableSignalLoop
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
        if (!$shared->isHttp()) {
            return;
        }
        while (true) {
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
            if (!$connection->isHttp()) {
                return;
            }
            while (!$shared->hasTempAuthKey()) {
                if (yield $this->waitSignal($this->pause())) {
                    return;
                }
            }
            $API->logger->logger("DC {$datacenter}: request {$connection->countHttpSent()}, response {$connection->countHttpReceived()}");
            if ($connection->countHttpSent() === $connection->countHttpReceived() && (!empty($connection->pendingOutgoing) || !empty($connection->new_outgoing) && !$connection->hasPendingCalls())) {
                yield from $connection->sendMessage(
                    new OutgoingMessage(
                        ['max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 0],
                        'http_wait',
                        '',
                        false,
                        false
                    )
                );
            }
            $API->logger->logger("DC {$datacenter}: request {$connection->countHttpSent()}, response {$connection->countHttpReceived()}");
        }
    }
    /**
     * Loop name.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "HTTP wait loop in DC {$this->datacenter}";
    }
}
