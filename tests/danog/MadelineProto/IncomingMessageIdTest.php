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

use danog\MadelineProto\Connection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoSession\MsgIdHandler;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * The message IDs of received packets (https://core.telegram.org/mtproto/security_guidelines#checking-msg-id): the
 * server can send a message before a container it created earlier, with lower IDs, which must be processed; a
 * replayed packet (an ID equal to one of the last N received, or lower than all of them) must be ignored.
 *
 * @internal
 */
final class IncomingMessageIdTest extends TestCase
{
    private MsgIdHandler $handler;
    private int $base;

    protected function setUp(): void
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new SettingsLogger)->setType(Logger::CALLABLE_LOGGER)->setExtra(static fn () => null));
        $connection = (new ReflectionClass(Connection::class))->newInstanceWithoutConstructor();
        $connection->API = $API;
        $this->handler = new MsgIdHandler($connection);
        // Server message IDs are odd (1 or 3 modulo 4)
        $this->base = (time() << 32) + 1;
    }

    private function id(int $n): int
    {
        return $this->base + 4 * $n;
    }

    public function testReorderedPacketsAreProcessed(): void
    {
        // A standalone rpc_result, then the container the server created before it (as received by dump)
        self::assertTrue($this->handler->checkIncomingPacketId($this->id(100)));
        self::assertTrue($this->handler->checkIncomingPacketId($this->id(90)));
        self::assertTrue($this->handler->checkIncomingPacketId($this->id(101)));
    }

    public function testReplayedPacketsAreIgnored(): void
    {
        self::assertTrue($this->handler->checkIncomingPacketId($this->id(100)));
        self::assertTrue($this->handler->checkIncomingPacketId($this->id(90)));
        self::assertFalse($this->handler->checkIncomingPacketId($this->id(100)));
        self::assertFalse($this->handler->checkIncomingPacketId($this->id(90)));
    }

    public function testPacketsOlderThanTheWindowAreIgnored(): void
    {
        for ($n = 1; $n <= 1000; $n++) {
            self::assertTrue($this->handler->checkIncomingPacketId($this->id(2 * $n)));
        }
        // Older than all of the last 1000
        self::assertFalse($this->handler->checkIncomingPacketId($this->id(1)));
        // Within them, never received
        self::assertTrue($this->handler->checkIncomingPacketId($this->id(1001)));
    }

    public function testTheWindowIsBounded(): void
    {
        for ($n = 1; $n <= 5000; $n++) {
            $this->handler->checkIncomingPacketId($this->id($n));
        }
        $ids = (new ReflectionProperty(MsgIdHandler::class, 'receivedPacketIds'))->getValue($this->handler);
        self::assertLessThanOrEqual(2000, \count($ids));
        self::assertSame($this->id(5000), end($ids));
        // Still rejects what it has forgotten
        self::assertFalse($this->handler->checkIncomingPacketId($this->id(1)));
    }
}
