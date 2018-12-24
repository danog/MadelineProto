<?php
/**
 * HttpWait loop
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Logger;
use Amp\Success;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

/**
 * HttpWait loop
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class HttpWaitLoop extends ResumableSignalLoop
{
    public function loop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        if (!in_array($connection->getCtx()->getStreamName(), [HttpStream::getName(), HttpsStream::getName()])) {
            yield new Success(0);
            return;
        }

        $this->startedLoop();
        $API->logger->logger("Entered HTTP wait loop in DC {$datacenter}", Logger::ULTRA_VERBOSE);

        $timeout = $API->settings['connection_settings'][isset($API->settings['connection_settings'][$datacenter]) ? $datacenter : 'all']['timeout'];
        while (true) {
            if (!in_array($connection->getCtx()->getStreamName(), [HttpStream::getName(), HttpsStream::getName()])) {
                $this->exitedLoop();
                yield new Success(0);
                return;
            }
            if (time() - $connection->last_http_wait > $timeout) {
                yield $connection->sendMessage(['_' => 'http_wait', 'body' => ['max_wait' => $timeout * 1000 - 100, 'wait_after' => 0, 'max_delay' => 0], 'content_related' => false, 'unencrypted' => false, 'method' => false]);
            }
            if (yield $this->waitSignal($this->pause(($connection->last_http_wait + $timeout) - time()))) {
                $API->logger->logger("Exiting HTTP wait loop");
                $this->exitedLoop();
                yield new Success(0);
                return;
            }
        }
    }
}
