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

namespace danog\MadelineProto\Test\Fixtures;

use Amp\DeferredFuture;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

/**
 * Run by WatchdogTest in a PHPUnit of its own: a test that waits forever, and one that must still run after it.
 *
 * @internal
 */
final class WatchdogHangingCase extends TestCase
{
    public function testWaitsForever(): void
    {
        [$socket] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        // Keeps the event loop alive, like a connection waiting for a peer that's gone
        EventLoop::onReadable($socket, static fn () => null);
        $this->waitForever();
    }

    public function testRunsAfterwards(): void
    {
        $this->assertTrue(true);
    }

    private function waitForever(): void
    {
        (new DeferredFuture)->getFuture()->await();
    }
}
