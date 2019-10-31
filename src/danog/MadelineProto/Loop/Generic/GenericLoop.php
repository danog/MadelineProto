<?php
/**
 * Generic loop.
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

namespace danog\MadelineProto\Loop\Generic;

use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

/**
 * Generic loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class GenericLoop extends ResumableSignalLoop
{
    const STOP = -1;
    const PAUSE = null;
    const CONTINUE = 0;

    protected $callback;
    protected $name;

    /**
     * Constructor.
     *
     * The callback will be bound to the GenericLoop instance: this means that you will be able to use `$this` as if the callback were actually the `loop` function (you can access the API property, use the pause/waitSignal methods & so on).
     * The return value of the callable can be:
     *  A number - the loop will be paused for the specified number of seconds
     *  GenericLoop::STOP - The loop will stop
     *  GenericLoop::PAUSE - The loop will pause forever (or until the `resume` method is called on the loop object from outside the loop)
     *  GenericLoop::CONTINUE - Return this if you want to rerun the loop without waiting
     *
     * @param \danog\MadelineProto\API $API      Instance of MadelineProto
     * @param callable                 $callback Callback to run
     * @param string                   $name     Fetcher name
     */
    public function __construct($API, $callback, $name)
    {
        $this->API = $API;
        $this->callback = $callback->bindTo($this);
        $this->name = $name;
    }

    public function loop()
    {
        $callback = $this->callback;

        while (true) {
            $timeout = yield $callback();
            if ($timeout === self::PAUSE) {
                $this->API->logger->logger("Pausing $this", \danog\MadelineProto\Logger::VERBOSE);
            } elseif ($timeout > 0) {
                $this->API->logger->logger("Pausing $this for $timeout", \danog\MadelineProto\Logger::VERBOSE);
            }
            if ($timeout === self::STOP || yield $this->waitSignal($this->pause($timeout))) {
                return;
            }
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
