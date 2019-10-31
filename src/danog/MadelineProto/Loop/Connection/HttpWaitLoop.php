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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use danog\MadelineProto\Connection;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

/**
 * HttpWait loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HttpWaitLoop extends ResumableSignalLoop
{
    /**
     * Connection instance.
     *
     * @var \danog\MadelineProto\Connection
     */
    protected $connection;
    /**
     * DC ID.
     *
     * @var string
     */
    protected $datacenter;

    /**
     * DataCenterConnection instance.
     *
     * @var \danog\MadelineProto\DataCenterConnection
     */
    protected $datacenterConnection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->API = $connection->getExtra();
        $this->datacenter = $connection->getDatacenterID();
        $this->datacenterConnection = $connection->getShared();
    }

    public function loop()
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
            $API->logger->logger("DC $datacenter: request {$connection->countHttpSent()}, response {$connection->countHttpReceived()}");
            if ($connection->countHttpSent() === $connection->countHttpReceived() && (!empty($connection->pending_outgoing) || (!empty($connection->new_outgoing) && !$connection->hasPendingCalls()))) {
                yield $connection->sendMessage(['_' => 'http_wait', 'body' => ['max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 0], 'contentRelated' => true, 'unencrypted' => false, 'method' => false]);
            }
            $API->logger->logger("DC $datacenter: request {$connection->countHttpSent()}, response {$connection->countHttpReceived()}");
        }
    }

    public function __toString(): string
    {
        return "HTTP wait loop in DC {$this->datacenter}";
    }
}
