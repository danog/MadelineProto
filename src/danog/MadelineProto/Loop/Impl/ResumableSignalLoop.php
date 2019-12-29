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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Impl;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Loop\ResumableLoopInterface;
use danog\MadelineProto\Tools;

/**
 * Resumable signal loop helper trait.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
abstract class ResumableSignalLoop extends SignalLoop implements ResumableLoopInterface
{
    use Tools;
    private $resume;
    private $pause;
    private $resumeWatcher;

    public function pause($time = null): Promise
    {
        if (!\is_null($time)) {
            if ($time <= 0) {
                return new Success(0);
            }
            $resume = \microtime(true) + $time;
            if ($this->resumeWatcher) {
                Loop::cancel($this->resumeWatcher);
                $this->resumeWatcher = null;
            }
            $this->resumeWatcher = Loop::delay((int) ($time * 1000), [$this, 'resume'], $resume);
        }
        $this->resume = new Deferred();

        $pause = $this->pause;
        $this->pause = new Deferred();
        if ($pause) {
            Loop::defer([$pause, 'resolve']);
        }

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
        if ($this->resume) {
            $resume = $this->resume;
            $this->resume = null;
            $resume->resolve();

            return $this->pause ? $this->pause->promise() : null;
        }
    }

    public function resumeDefer()
    {
        Loop::defer([$this, 'resume']);

        return $this->pause ? $this->pause->promise() : null;
    }
}
