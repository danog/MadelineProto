<?php
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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Tools;

trait LoggerLoop
{
    /**
     * Whether the loop was started.
     */
    private bool $started = false;
    /**
     * Logger instance.
     */
    protected Logger $logger;
    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Start the loop.
     *
     * Returns false if the loop is already running.
     *
     * @return bool
     */
    public function start(): bool
    {
        if ($this->started) {
            return false;
        }
        Tools::callFork((function (): \Generator {
            $this->startedLoop();
            try {
                yield from $this->loop();
            } finally {
                $this->exitedLoop();
            }
        })());
        return true;
    }
    /**
     * Check whether loop is running.
     *
     * @return boolean
     */
    public function isRunning(): bool
    {
        return $this->started;
    }

    /**
     * Signal that loop has started.
     *
     * @return void
     */
    protected function startedLoop(): void
    {
        $this->started = true;
        parent::startedLoop();
        $this->logger->logger("Entered $this", Logger::ULTRA_VERBOSE);
    }

    /**
     * Signal that loop has exited.
     *
     * @return void
     */
    protected function exitedLoop(): void
    {
        $this->started = false;
        parent::exitedLoop();
        $this->logger->logger("Exited $this", Logger::ULTRA_VERBOSE);
    }

    /**
     * Report pause, can be overriden for logging.
     *
     * @param integer $timeout Pause duration, 0 = forever
     *
     * @return void
     */
    protected function reportPause(int $timeout): void
    {
        $this->logger->logger(
            "Pausing $this for $timeout",
            Logger::ULTRA_VERBOSE
        );
    }

    /**
     * Get loop name.
     *
     * @return string
     */
    abstract public function __toString(): string;
}
