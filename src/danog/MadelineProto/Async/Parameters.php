<?php
/**
 * Parameters module.
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

use Amp\Promise;
use function Amp\call;

/**
 * Parameters module.
 *
 * Manages asynchronous generation of method parameters
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
abstract class Parameters
{
    private $fetched = false;
    private $params = [];

    /**
     * Fetch parameters asynchronously.
     *
     * @return Promise
     */
    public function fetchParameters(): Promise
    {
        return call([$this, 'fetchParametersAsync']);
    }

    /**
     * Fetch parameters asynchronously.
     *
     * @return \Generator
     */
    public function fetchParametersAsync(): \Generator
    {
        $refetchable = $this->isRefetchable();
        if ($this->fetched && !$refetchable) {
            return $this->params;
        }
        $params = yield call([$this, 'getParameters']);

        if (!$refetchable) {
            $this->params = $params;
        }

        return $params;
    }

    /**
     * Check if the parameters can be fetched more than once.
     *
     * @return bool
     */
    abstract public function isRefetchable(): bool;

    /**
     * Gets the parameters asynchronously.
     *
     * @return \Generator
     */
    abstract public function getParameters(): \Generator;
}
