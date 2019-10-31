<?php

/**
 * ResponseHandler module.
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

namespace danog\MadelineProto;

/**
 * Represents a piece of a coroutine stack trace.
 */
class Trace
{
    /**
     * Next piece of the stack trace.
     *
     * @var Trace
     */
    private $next;
    /**
     * Current stack trace frames.
     *
     * @var array
     */
    private $frames = [];
    /**
     * Create trace.
     *
     * @param array $frames Current frames
     * @param self  $next   Next trace
     */
    public function __construct(array $frames, self $next = null)
    {
        $this->frames = $frames;
        $this->next = $next;
    }

    /**
     * Get stack trace.
     *
     * @return array
     */
    public function getTrace(): array
    {
        return \iterator_to_array($this->getTraceGenerator());
    }

    /**
     * Get stack trace.
     *
     * @return \Generator
     */
    private function getTraceGenerator(): \Generator
    {
        foreach ($this->frames as $frame) {
            yield $frame;
        }
        if ($this->next) {
            yield from $this->next->getTraceGenerator();
        }
    }
}
