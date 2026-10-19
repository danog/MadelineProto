<?php declare(strict_types=1);

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

use danog\MadelineProto\RPCErrorException;
use Fiber;
use PHPUnit\Framework\TestCase;

/**
 * Unknown RPC errors are reported to the error database in the background: the request that got the error must
 * not wait for the report (a livestream's chunk fetch stalled for the 10 seconds of the report's HTTP timeout on
 * every TIME_TOO_BIG, an error the database has no description for, so it was reported again every time).
 *
 * Separate process: the report queued by the test must never run.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class RPCErrorReportTest extends TestCase
{
    public function testAnUnknownErrorIsBuiltWithoutWaitingForItsReport(): void
    {
        $fiber = new Fiber(static fn () => RPCErrorException::make('SOME_UNKNOWN_TEST_ERROR', 400, 'upload.getFile'));
        $fiber->start();

        $this->assertTrue($fiber->isTerminated());
        $e = $fiber->getReturn();
        $this->assertInstanceOf(RPCErrorException::class, $e);
        $this->assertSame('SOME_UNKNOWN_TEST_ERROR', $e->rpc);
    }
}
