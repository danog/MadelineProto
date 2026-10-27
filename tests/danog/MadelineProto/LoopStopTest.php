<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Amp\TimeoutCancellation;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use danog\MadelineProto\Wrappers\Loop;
use PHPUnit\Framework\TestCase;

use function Amp\async;

/**
 * The event handler runs onStart before the update loop starts: a stop() called from onStart was lost, and the loop
 * then waited forever (startAndLoop() never returned).
 *
 * @internal
 */
final class LoopStopTest extends TestCase
{
    private static function instance(): object
    {
        return new class {
            use Loop;
            public Logger $logger;
            public function __construct()
            {
                $this->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
            }
            public function getAuthorization(): int
            {
                return 1;
            }
            public function hasEventHandler(): bool
            {
                return true;
            }
            public function initSelfRestart(): void
            {
            }
        };
    }

    public function testAStopBeforeTheLoopEndsIt(): void
    {
        $instance = self::instance();
        $instance->stop();
        async($instance->loop(...))->await(new TimeoutCancellation(1));
        $this->addToAssertionCount(1);
    }

    public function testAStopDuringTheLoopEndsIt(): void
    {
        $instance = self::instance();
        $loop = async($instance->loop(...));
        \Amp\delay(0.01);
        $instance->stop();
        $loop->await(new TimeoutCancellation(1));
        $this->addToAssertionCount(1);
    }

    public function testTheLoopCanRunAgainAfterAStop(): void
    {
        $instance = self::instance();
        $instance->stop();
        async($instance->loop(...))->await(new TimeoutCancellation(1));
        $loop = async($instance->loop(...));
        \Amp\delay(0.05);
        $this->assertFalse($loop->isComplete(), 'A stop ends one loop only');
        $instance->stop();
        $loop->await(new TimeoutCancellation(1));
    }
}
