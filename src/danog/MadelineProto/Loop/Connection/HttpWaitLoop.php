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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;

/**
 * HttpWait loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HttpWaitLoop extends ResumableSignalLoop
{
    protected $connection;
    protected $datacenter;

    public function __construct($API, $datacenter)
    {
        $this->API = $API;
        $this->datacenter = $datacenter;
        $this->connection = $API->datacenter->sockets[$datacenter];
    }

    public function loop()
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        if (!in_array($connection->getCtx()->getStreamName(), [HttpStream::getName(), HttpsStream::getName()])) {
            return;
        }

        $timeout = $API->settings['connection_settings'][isset($API->settings['connection_settings'][$datacenter]) ? $datacenter : 'all']['timeout'];
        while (true) {
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
            if (!in_array($connection->getCtx()->getStreamName(), [HttpStream::getName(), HttpsStream::getName()])) {
                return;
            }
            while ($connection->temp_auth_key === null) {
                if (yield $this->waitSignal($this->pause())) {
                    return;
                }
            }
            $API->logger->logger("DC $datacenter: request {$connection->http_req_count}, response {$connection->http_res_count}");
            if ($connection->http_req_count === $connection->http_res_count && (!empty($connection->pending_outgoing) || (!empty($connection->new_outgoing) && !$connection->hasPendingCalls()))) {
                yield $connection->sendMessage(['_' => 'http_wait', 'body' => ['max_wait' => 30000, 'wait_after' => 0, 'max_delay' => 0], 'content_related' => true, 'unencrypted' => false, 'method' => false]);
            }
            $API->logger->logger("DC $datacenter: request {$connection->http_req_count}, response {$connection->http_res_count}");
        }
    }

    public function __toString(): string
    {
        return "HTTP wait loop in DC {$this->datacenter}";
    }
}
