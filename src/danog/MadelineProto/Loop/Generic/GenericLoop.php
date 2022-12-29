<?php

/**
 * Update feeder loop.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Generic;

use Amp\Promise;
use danog\Loop\Generic\GenericLoop as GenericGenericLoop;
use danog\MadelineProto\InternalDoc;
use danog\MadelineProto\Loop\APILoop;

/**
 * {@inheritDoc}
 *
 * @deprecated Use the danog/loop API instead
 */
class GenericLoop extends GenericGenericLoop
{
    use APILoop {
        __construct as private init;
    }
    /**
     * Constructor.
     *
     * @param InternalDoc $API      API instance
     * @param callable    $callable Method
     * @param string      $name     Loop name
     */
    public function __construct(InternalDoc $API, callable $callable, string $name)
    {
        $this->init($API);
        parent::__construct($callable, $name);
    }

    /**
     * Pause the loop.
     *
     * @param ?int $time For how long to pause the loop, if null will pause forever (until resume is called from outside of the loop)
     *
     * @return Promise Resolved when the loop is resumed
     */
    public function pause(?int $time = null): Promise
    {
        return parent::pause(\is_integer($time) ? $time * 1000 : $time);
    }
}
