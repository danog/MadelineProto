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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
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

    protected $API;
    protected $connection;
    protected $datacenter;

    public function __construct($API, $datacenter)
    {
        $this->API = $API;
        $this->datacenter = $datacenter;
        $this->connection = $API->datacenter->sockets[$datacenter];
    }

    public function start()
    {
        if ($this->count) {
            $this->API->logger->logger("NOT entering check loop in DC {$this->datacenter} with running count {$this->count}", Logger::ERROR);

            return false;
        }
        Promise\rethrow($this->call($this->loop()));

        return true;
    }

    public function exitedLoop()
    {
        $this->count--;
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
