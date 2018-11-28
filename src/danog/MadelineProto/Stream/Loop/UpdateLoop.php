<?php
/**
 * Update loop
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

namespace danog\MadelineProto\Stream\Loop;

use Amp\Promise;
use Amp\Success;
use Amp\Deferred;
use function Amp\call;
use function Amp\asyncCall;
use danog\MadelineProto\Stream\Async\Loop;
use danog\MadelineProto\Stream\Async\ResumableLoop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Stream\ResumableLoopInterface;
use danog\MadelineProto\Stream\SignalLoopInterface;
use danog\MadelineProto\Stream\Async\SignalLoop;

/**
 * Update loop
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class UpdateLoop implements ResumableLoopInterface, SignalLoopInterface
{
    use Loop;
    use ResumableLoop;
    use SignalLoop;
    public function loop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        if (!$this->API->settings['updates']['handle_updates']) {
            yield new Success(0);

            return false;
        }

        $this->startedLoop();
        $API->logger->logger("Entered updates loop in DC {$datacenter}", Logger::ULTRA_VERBOSE);

        $timeout = $API->settings['updates']['getdifference_interval'];
        while (true) {
            if (!$this->API->settings['updates']['handle_updates']) {
                $this->exitedLoop();
                return false;
            }
            if (time() - $API->last_getdifference > $timeout) {
                $API->get_updates_difference();
            }
            if (yield $this->waitSignal($this->pause(($API->last_getdifference + $timeout) - time()))) {
                $API->logger->logger("Exiting update loop");
                $this->exitedLoop();
                return;
            }
        }
    }
}