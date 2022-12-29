<?php
/**
 * Internal loop trait.
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

namespace danog\MadelineProto\Loop;

use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;

trait InternalLoop
{
    use LoggerLoop {
        __construct as private setLogger;
    }

    /**
     * API instance.
     */
    protected MTProto $API;
    /**
     * Constructor.
     *
     * @param MTProto $API API instance
     */
    public function __construct(MTProto $API)
    {
        $this->API = $API;
        $this->setLogger($API->getLogger());
    }

    private function waitForAuthOrSignal(bool $waitAfter = true): \Generator
    {
        $API = $this->API;
        while (!$API->hasAllAuth()) {
            $waitAfter = false;
            $API->logger->logger("Waiting for auth in {$this}");
            if (yield $this->waitSignal($this->pause())) {
                $API->logger->logger("Exiting in {$this} while waiting for auth (init)!", Logger::LEVEL_ULTRA_VERBOSE);
                return true;
            }
        }
        if (!$waitAfter) {
            return false;
        }
        if (yield $this->waitSignal($this->pause())) {
            $API->logger->logger("Exiting in {$this} due to signal!", Logger::LEVEL_ULTRA_VERBOSE);
            return true;
        }
        return false;
    }
}
