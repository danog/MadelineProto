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

use AssertionError;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\ExitFailure;
use danog\MadelineProto\Magic;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Exceptions of the IPC server, rebuilt in the client.
 *
 * getException() overwrote Exception's previous with a property that was never set: the previous exception of the
 * server was lost (replaced with the client backtrace), and on an Error (like the AssertionError reporting a missing
 * event handler class) it created a dynamic property (deprecated).
 *
 * @internal
 */
final class ExitFailureTest extends TestCase
{
    public function setUp(): void
    {
        Magic::start(light: true);
    }

    public function testKeepsThePreviousExceptionOfAnException(): void
    {
        $e = (new ExitFailure(new RuntimeException('server', 0, new LogicException('cause'))))->getException();
        self::assertInstanceOf(RuntimeException::class, $e);
        self::assertSame('server', $e->getMessage());
        self::assertInstanceOf(LogicException::class, $e->getPrevious());
        self::assertSame('cause', $e->getPrevious()->getMessage());
        // The client backtrace ends the chain
        self::assertInstanceOf(Exception::class, $e->getPrevious()->getPrevious());
    }

    public function testKeepsThePreviousExceptionOfAnError(): void
    {
        $e = (new ExitFailure(new AssertionError('server', 0, new RuntimeException('cause'))))->getException();
        self::assertInstanceOf(AssertionError::class, $e);
        self::assertSame('cause', $e->getPrevious()?->getMessage());
        self::assertArrayNotHasKey('previous', get_object_vars($e), 'No dynamic property');
    }

    public function testAppendsTheClientBacktrace(): void
    {
        foreach ([new AssertionError('server'), new RuntimeException('server')] as $server) {
            $e = (new ExitFailure($server))->getException();
            self::assertInstanceOf($server::class, $e);
            self::assertInstanceOf(Exception::class, $e->getPrevious());
            self::assertSame('Client backtrace', $e->getPrevious()->getMessage());
        }
    }
}
