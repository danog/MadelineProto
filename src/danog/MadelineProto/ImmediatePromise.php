<?php
/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
 */

namespace danog\MadelineProto;

class ImmediatePromise
{
    private $resolveCallback;
    private $rejectCallback;
    private $waitFn;
    private $state;

    const PENDING = 'pending';
    const FULFILLED = 'fulfilled';
    const REJECTED = 'rejected';

    public function __construct($waitFn = null)
    {
        $this->waitFn = $waitFn;
        $this->state = self::PENDING;
    }

    public function then($resolveCallback = null, $rejectCallback = null)
    {
        $this->resolveCallback = $resolveCallback;
        $this->rejectCallback = $rejectCallback;
    }

    public function resolve($data)
    {
        $this->state = self::FULFILLED;
        if ($this->resolveCallback) {
            $func = $this->resolveCallback;
            $func($data);
        }
    }

    public function reject($data)
    {
        $this->state = self::REJECTED;
        if ($this->rejectCallback) {
            $func = $this->rejectCallback;
            $func($data);
        }
    }

    public function wait()
    {
        if ($this->waitFn) {
            $func = $this->waitFn;
            $func();
            if ($this->state === self::PENDING) {
                throw new Exception('Waiting function did not resolve or reject.');
            }
        }
    }

    public function getState()
    {
        return $this->state;
    }
}
