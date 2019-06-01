<?php
/**
 * Generic period fetcher loop
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

namespace danog\MadelineProto\Loop\Generic;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

/**
 * Update loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class PeriodicFetcherLoop extends ResumableSignalLoop
{
    const STOP = -1;
    const PAUSE = null;

    protected $callback;
    protected $name;

    /**
     * Constructor
     *
     * @param \danog\MadelineProto\API $API Instance of MadelineProto
     * @param callback $callback Callback to run periodically
     * @param int $timeout Timeout
     * @param string $name Fetcher name
     */
    public function __construct($API, $callback, $name)
    {
        $this->API = $API;
        $this->callback = $callback;
        $this->name = $name;
    }
    public function loop()
    {
        $callback = $this->callback;

        while (true) {
            $timeout = yield $callback();
            if ($timeout === self::STOP || yield $this->waitSignal($this->pause($timeout))) {
                return;
            }
        }
    }

    public function __toString(): string
    {
        return "{$this->name} loop";
    }
}
