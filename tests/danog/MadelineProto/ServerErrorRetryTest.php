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
 * Calls failing with server-side errors (500, -503) are resent with a growing delay, and fail with the error once the
 * delays add up to half of their timeout: a call the server always fails (messages.getTranscriptionLangs got
 * RPC_CALL_FAIL every time) must get the error, not be resent until it times out.
 */
final class ServerErrorRetryTest extends TestCase
{
    private Connection&MockObject $connection;
    /** @var list<float> */
    private array $delays = [];

    protected function setUp(): void
    {
        BypassFinals::enable();

        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = $this->createMock(Logger::class);

        $this->connection = $this->createPartialMock(Connection::class, ['methodRecall']);
        $this->connection->API = $API;
        (new ReflectionProperty(Connection::class, 'datacenter'))->setValue($this->connection, 2);
        $this->connection->method('methodRecall')->willReturnCallback(
            function (MTProtoOutgoingMessage $request, ?int $dc, float $delay): void {
                self::assertSame(2, $dc);
                $this->delays []= $delay;
            }
        );
    }

    public function testRpcCallFailIsResentWithBackoffThenFails(): void
    {
        $request = $this->request('messages.getTranscriptionLangs', 'Vector<string>', timeout: 60.0);
        $this->assertResentThenFails($request, 'RPC_CALL_FAIL', 500, [1.0, 2.0, 4.0, 8.0]);
    }

    public function testMinus503IsResentWithBackoffThenFails(): void
    {
        $request = $this->request('messages.getHistory', 'messages.Messages', timeout: 20.0);
        $this->assertResentThenFails($request, 'Timeout', -503, [1.0, 2.0, 4.0]);
    }

    public function testRandomIdDuplicateIsResentWithBackoffThenFails(): void
    {
        $request = $this->request('messages.sendMessage', 'Updates', timeout: 60.0, tries: 2);
        $this->assertResentThenFails($request, 'RANDOM_ID_DUPLICATE', 500, [1.0, 2.0, 4.0, 8.0]);
    }

    /**
     * @param list<float> $delays
     */
    private function assertResentThenFails(MTProtoOutgoingMessage $request, string $message, int $code, array $delays): void
    {
        foreach ($delays as $_) {
            self::assertNull($this->handleRpcError($request, $message, $code));
        }
        self::assertSame($delays, $this->delays);

        $error = $this->handleRpcError($request, $message, $code);
        self::assertNotNull($error);
        $error = $error();
        self::assertInstanceOf(RPCErrorException::class, $error);
        self::assertSame($message, $error->rpc);
        self::assertSame($delays, $this->delays);
    }

    private function request(string $method, string $type, float $timeout, int $tries = 1): MTProtoOutgoingMessage
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
            timeout: $timeout,
        );
        for ($i = 0; $i < $tries; $i++) {
            $request->trySend();
        }
        return $request;
    }

    private function handleRpcError(MTProtoOutgoingMessage $request, string $message, int $code): ?callable
    {
        return (new ReflectionMethod(Connection::class, 'handleRpcError'))->invoke(
            $this->connection,
            $request,
            ['error_message' => $message, 'error_code' => $code],
        );
    }
}
