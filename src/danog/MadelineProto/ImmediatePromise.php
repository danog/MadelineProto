<?php
/**
 * ImmediatePromise module.
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

namespace danog\MadelineProto;

class ImmediatePromise
{
    private $resolveCallback;
    private $rejectCallback;
    private $waitFn;
    private $state;
    private $chained;
    private $value;

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

        return $this->chained = new self(function () {
            $this->wait();
        });
    }

    public function resolve($data)
    {
        $this->state = self::FULFILLED;
        $this->value = $data;

        if ($this->resolveCallback) {
            $func = $this->resolveCallback;
            $result = $func($data);

            $this->chained->resolve($result);
        }
    }

    public function reject($data)
    {
        $this->state = self::REJECTED;
        $this->value = $data;

        if ($this->rejectCallback) {
            $func = $this->rejectCallback;
            $result = $func($data);

            $this->chained->resolve($result);
        }
    }

    public function wait($unwrap = true)
    {
        if ($this->state === self::PENDING && $this->waitFn) {
            $func = $this->waitFn;
            $func();
            if ($this->state === self::PENDING) {
                throw new Exception('Waiting function did not resolve or reject.');
            }
        }
        if ($unwrap) {
            if ($this->getState() === self::REJECTED) {
                throw ($this->value instanceof \Exception || $this->value instanceof \Throwable ? $this->value : new Exception($this->value));
            }

            return $this->value;
        }
    }

    public function getState()
    {
        return $this->state;
    }
}
