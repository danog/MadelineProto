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
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\RPCErrorException;
use DG\BypassFinals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * RANDOM_ID_DUPLICATE: the server is still processing an earlier copy of the call with the same random_id (#1669).
 */
final class RandomIdDuplicateTest extends TestCase
{
    private Connection&MockObject $connection;

    protected function setUp(): void
    {
        BypassFinals::enable();

        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = $this->createMock(Logger::class);

        $this->connection = $this->createPartialMock(Connection::class, ['methodRecall']);
        $this->connection->API = $API;
        (new ReflectionProperty(Connection::class, 'datacenter'))->setValue($this->connection, 2);
    }

    /**
     * Sent again by us (after a -503, ...): resent until the server answers with the result of the first copy.
     */
    public function testACallWeSentAgainIsResent(): void
    {
        $request = $this->request('messages.sendMessage', 'Updates', tries: 2);
        $this->connection->expects(self::once())->method('methodRecall')->with($request, 2, 1.0);

        self::assertNull($this->handleRpcError($request));
    }

    /**
     * Sent once: the random_id was used by another call (passed by the caller), not by an earlier copy of this one.
     */
    public function testACallSentOnceFails(): void
    {
        $request = $this->request('messages.sendMessage', 'Updates', tries: 1);
        $this->connection->expects(self::never())->method('methodRecall');

        $this->assertFails($request);
    }

    /**
     * Only calls sending messages return the result of the earlier copy.
     */
    public function testACallNotSendingMessagesFails(): void
    {
        $request = $this->request('phone.requestCall', 'phone.PhoneCall', tries: 2);
        $this->connection->expects(self::never())->method('methodRecall');

        $this->assertFails($request);
    }

    private function request(string $method, string $type, int $tries): MTProtoOutgoingMessage
    {
        $request = new MTProtoOutgoingMessage(
            connection: $this->connection,
            body: [],
            constructor: $method,
            type: $type,
            isMethod: true,
            unencrypted: false,
            specialMethodType: null,
            cancellation: null,
        );
        for ($i = 0; $i < $tries; $i++) {
            $request->trySend();
        }
        return $request;
    }

    private function handleRpcError(MTProtoOutgoingMessage $request): ?callable
    {
        return (new ReflectionMethod(Connection::class, 'handleRpcError'))->invoke(
            $this->connection,
            $request,
            ['error_message' => 'RANDOM_ID_DUPLICATE', 'error_code' => 500],
        );
    }

    private function assertFails(MTProtoOutgoingMessage $request): void
    {
        $error = $this->handleRpcError($request);
        self::assertNotNull($error);
        $error = $error();
        self::assertInstanceOf(RPCErrorException::class, $error);
        self::assertSame('RANDOM_ID_DUPLICATE', $error->rpc);
    }
}
