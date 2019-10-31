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

use Amp\Promise;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\LoopInterface;

/**
 * Loop helper trait.
 *
 * Wraps the asynchronous generator methods with asynchronous promise-based methods
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
abstract class Loop implements LoopInterface
{
    use \danog\MadelineProto\Tools;

    private $count = 0;

    /**
     * MTProto instance.
     *
     * @var \danog\MadelineProto\MTProto
     */
    public $API;

    public function __construct($API)
    {
        $this->API = $API;
    }

    public function start()
    {
        if ($this->count) {
            //$this->API->logger->logger("NOT entering $this with running count {$this->count}", Logger::ERROR);

            return false;
        }
        return \danog\MadelineProto\Tools::callFork($this->loopImpl());
    }

    private function loopImpl()
    {
        //yield ['my_trace' => debug_backtrace(0, 1)[0], (string) $this];
        $this->startedLoop();
        $this->API->logger->logger("Entered $this", Logger::ULTRA_VERBOSE);

        try {
            yield $this->loop();
        } finally {
            $this->exitedLoop();
            $this->API->logger->logger("Physically exited $this", Logger::ULTRA_VERBOSE);
            //return null;
        }
    }

    public function exitedLoop()
    {
        if ($this->count) {
            $this->API->logger->logger("Exited $this", Logger::ULTRA_VERBOSE);
            $this->count--;
        }
    }

    public function startedLoop()
    {
        $this->count++;
    }

    public function isRunning()
    {
        return $this->count;
    }
}
