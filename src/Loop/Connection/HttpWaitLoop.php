<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use danog\Loop\Loop;

/**
 * HttpWait loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class HttpWaitLoop extends Loop
{
    use Common;
    /**
     * Main loop.
     */
    protected function loop(): ?float
    {
        if (!$this->connection->isHttp()) {
            return self::STOP;
        }
        if (!$this->shared->hasTempAuthKey()) {
            return self::PAUSE;
        }
        $this->API->logger("DC {$this->datacenter}: request {$this->connection->countHttpSent()}, response {$this->connection->countHttpReceived()}");
        if ($this->connection->countHttpSent() === $this->connection->countHttpReceived()
            && ($this->connection->pendingOutgoing || $this->connection->new_outgoing)
            && !$this->connection->hasPendingCalls()
        ) {
            $this->connection->objectCall(
                'http_wait',
                ['max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 0],
            );
        }
        $this->API->logger("DC {$this->datacenter}: request {$this->connection->countHttpSent()}, response {$this->connection->countHttpReceived()}");
        return self::PAUSE;
    }
    /**
     * Loop name.
     */
    public function __toString(): string
    {
        return "HTTP wait loop in DC {$this->datacenter}";
    }
}
