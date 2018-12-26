<?php
/**
 * Loop helper trait.
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

namespace danog\MadelineProto\Loop\Impl;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Loop\ResumableLoopInterface;

/**
 * Resumable signal loop helper trait.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
abstract class ResumableSignalLoop extends SignalLoop implements ResumableLoopInterface
{
    private $resume;
    private $resumeWatcher;

    public function pause($time = null): Promise
    {
        if (!is_null($time)) {
            if ($time <= 0) {
                return new Success(0);
            } else {
                $resume = microtime(true) + $time;
                if ($this->resumeWatcher) {
                    Loop::cancel($this->resumeWatcher);
                    $this->resumeWatcher = null;
                }
                $this->resumeWatcher = Loop::delay($time * 1000, [$this, 'resume'], $resume);
                //var_dump("resume {$this->resumeWatcher} ".get_class($this)." DC {$this->datacenter} after ", ($time * 1000), $resume);
            }
        }
        $this->resume = new Deferred();

        return $this->resume->promise();
    }

    public function resume($watcherId = null, $expected = 0)
    {
        if ($this->resumeWatcher) {
            $storedWatcherId = $this->resumeWatcher;
            Loop::cancel($storedWatcherId);
            $this->resumeWatcher = null;
            if ($watcherId && $storedWatcherId !== $watcherId) {
                return;
            }
        }
        if ($expected) {
            //var_dump("=======", "resume $watcherId ".get_class($this)." DC {$this->datacenter} diff ".(microtime(true) - $expected).": expected $expected, actual ".microtime(true));
        }
        if ($this->resume) {
            $resume = $this->resume;
            $this->resume = null;
            $resume->resolve();
        }
    }
}
