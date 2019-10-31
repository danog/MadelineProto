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
use Amp\Promise;
use danog\MadelineProto\Coroutine;
use danog\MadelineProto\Loop\SignalLoopInterface;

/**
 * Signal loop helper trait.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
abstract class SignalLoop extends Loop implements SignalLoopInterface
{
    private $signalDeferred;

    public function signal($what)
    {
        if ($this->signalDeferred) {
            $deferred = $this->signalDeferred;
            $this->signalDeferred = null;
            if ($what instanceof \Exception || $what instanceof \Throwable) {
                $deferred->fail($what);
            } else {
                $deferred->resolve($what);
            }
        }
    }

    public function waitSignal($promise): Promise
    {
        if ($promise instanceof \Generator) {
            $promise = new Coroutine($promise);
        }
        $this->signalDeferred = new Deferred();
        $dpromise = $this->signalDeferred->promise();

        $promise->onResolve(function () use ($promise) {
            if ($this->signalDeferred !== null) {
                $deferred = $this->signalDeferred;
                $this->signalDeferred = null;
                $deferred->resolve($promise);
            }
        });

        return $dpromise;
    }
}
