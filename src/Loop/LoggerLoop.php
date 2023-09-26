<?php

declare(strict_types=1);

/**
 * Loop logging trait.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop;

use danog\MadelineProto\Logger;

/**
 * @internal
 */
trait LoggerLoop
{
    private bool $logPauses = true;

    /**
     * Report pause, can be overriden for logging.
     *
     * @param float $timeout Pause duration, 0 = forever
     */
    protected function reportPause(float $timeout): void
    {
        $timeout = $timeout ? "for $timeout" : "until resume";
        $this->API->logger("Pausing $this $timeout...", Logger::ULTRA_VERBOSE);
    }

    /**
     * Signal that loop was started.
     */
    protected function startedLoop(): void
    {
        $this->API->logger("Started $this!", Logger::ULTRA_VERBOSE);
    }
    /**
     * Signal that loop has exited.
     */
    protected function exitedLoop(): void
    {
        $this->API->logger("Exited $this!", Logger::ULTRA_VERBOSE);
    }
    /**
     * Get loop name.
     */
    abstract public function __toString(): string;
}
