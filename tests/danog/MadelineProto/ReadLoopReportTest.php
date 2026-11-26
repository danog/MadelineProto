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

use Amp\DeferredFuture;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Connection;
use danog\MadelineProto\Loop\Connection\ReadLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\TL\TL;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function Amp\async;
use function Amp\delay;

/**
 * magnaluna's main DC stayed disconnected for half an hour: the read loop reported each message it couldn't
 * deserialize and waited for the report, whose messages (sent on the same DC) only that read loop could read, until
 * they timed out, one report after the other; reconnecting waited for the read loop to exit. The undeserializable
 * messages were late answers to requests that had already timed out (a slow DC), whose bare Vector<int> results
 * can't be deserialized without the request.
 *
 * @internal
 */
final class ReadLoopReportTest extends TestCase
{
    /** rpc_result#f35c6d01 req_msg_id:long result:Vector<int> (one element), as sent for phone.checkGroupCall. */
    private const REQ_MSG_ID = 7710390221095208540;

    /** @return list<string> The messages reported. */
    private function deserialize(bool $known): array
    {
        BypassFinals::enable();
        $reports = [];
        $api = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['logger', 'getTL', 'report', '__destruct'])->getMock();
        $tl = $this->getMockBuilder(TL::class)->disableOriginalConstructor()->onlyMethods(['deserialize'])->getMock();
        $tl->method('deserialize')->willThrowException(new \RuntimeException('Could not extract type:  with id 86f7e4a7'));
        $api->method('getTL')->willReturn($tl);
        // A report that never completes: its messages wait for answers that only the read loop could read.
        $api->method('report')->willReturnCallback(static function (string $message) use (&$reports): void {
            $reports[] = $message;
            (new DeferredFuture)->getFuture()->await();
        });
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $connection->API = $api;
        if ($known) {
            $connection->new_outgoing[self::REQ_MSG_ID] = $this->getMockBuilder(MTProtoOutgoingMessage::class)
                ->disableOriginalConstructor()->getMock();
        }
        $reflection = new ReflectionClass(ReadLoop::class);
        $loop = $reflection->newInstanceWithoutConstructor();
        foreach (['API' => $api, 'connection' => $connection, 'datacenter' => '4'] as $property => $value) {
            $reflection->getProperty($property)->setValue($loop, $value);
        }
        $data = "\x01\x6d\x5c\xf3".pack('q', self::REQ_MSG_ID)."\x15\xc4\xb5\x1c".pack('V', 1)."\xa7\xe4\xf7\x86";
        $deserialize = $reflection->getMethod('deserialize');
        $result = async(static fn () => $deserialize->invoke($loop, $data, 1, true))
            ->await(new TimeoutCancellation(5, 'The read loop waited for the report'));
        self::assertInstanceOf(MTProtoIncomingMessage::class, $result);
        self::assertSame('rpc_result', $result->getPredicate());
        self::assertSame(self::REQ_MSG_ID, $result->getRequestId());
        self::assertInstanceOf(\Throwable::class, $result->deserializationError);
        delay(0.01);
        return $reports;
    }

    public function testSchemaIssuesAreReportedWithoutWaiting(): void
    {
        $reports = $this->deserialize(true);
        self::assertCount(1, $reports);
        self::assertStringStartsWith('Schema issues', $reports[0]);
    }

    public function testLateAnswersToForgottenRequestsAreNotReported(): void
    {
        self::assertSame([], $this->deserialize(false));
    }
}
