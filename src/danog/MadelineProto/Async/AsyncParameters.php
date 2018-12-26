<?php
/**
 * Async parameters class.
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

namespace danog\MadelineProto\Async;

use Amp\Success;

/**
 * Async parameters class.
 *
 * Manages asynchronous generation of method parameters
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class AsyncParameters extends Parameters
{
    private $callable;
    private $refetchable = true;

    public function __construct(callable $callable, bool $refetchable = true)
    {
        $this->callable = $callable;
        $this->refetchable = $refetchable;
    }

    public function setRefetchable(bool $refetchable)
    {
        $this->refetchable = $refetchable;
    }

    public function setCallable(callable $callable)
    {
        $this->callable = $callable;
    }

    public function isRefetchable(): bool
    {
        return $this->refetchable;
    }

    public function getParameters(): \Generator
    {
        $callable = $this->callable;
        $params = $callable();

        if ($params instanceof \Generator) {
            $params = yield coroutine($params);
        } else {
            $params = yield new Success($params);
        }

        return $params;
    }
}
