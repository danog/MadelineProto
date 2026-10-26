<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Loop\Connection\ReadLoop;
use danog\MadelineProto\Loop\Connection\WriteLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LinkedList;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\MTProtoSession\MsgIdHandler;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\Crypt\NativeExecutor;
use danog\MadelineProto\NothingInTheSocketException;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\ReadBufferInterface;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Revolt\EventLoop;

use function Amp\async;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConnectionCryptoTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function changes(): iterable
    {
        foreach (['reconnect', 'session', 'auth'] as $change) {
            yield $change => [$change, false];
            yield $change.' with damaged ciphertext' => [$change, true];
        }
        yield 'current session mismatch' => ['mismatch', false];
    }

    /** @dataProvider changes */
    public function testResultsCannotModifyAReplacementConnection(string $change, bool $damaged): void
    {
        NativeExecutor::initialize();
        if (!NativeExecutor::get(NativeExecutor::DECODE_V2)) {
            self::markTestSkipped('Optional native decoder unavailable');
        }
        BypassFinals::enable();
        $api = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['logger', 'getTL', '__destruct'])->getMock();
        $api->expects(self::never())->method('getTL');
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()
            ->onlyMethods(['shouldReconnect', 'getGeneration', 'reading', 'resetSession'])->getMock();
        $connection->API = $api;
        $connection->session_id = random_bytes(8);
        $connection->method('shouldReconnect')->willReturn(false);
        $connection->expects($change === 'mismatch' ? self::once() : self::never())->method('resetSession');
        $generation = 1;
        // A reference is needed because the transport generation can change while decode awaits.
        $connection->method('getGeneration')->willReturnCallback(static function () use (&$generation): int {
            return $generation;
        });
        $authKey = random_bytes(256);
        $authId = substr(sha1($authKey, true), -8);
        $auth = $this->getMockBuilder(NewAuthKey::class)->disableOriginalConstructor()
            ->onlyMethods(['getTempID', 'getTempAuthKey'])->getMock();
        $auth->method('getTempID')->willReturn($authId);
        $auth->method('getTempAuthKey')->willReturnCallback(static function () use (&$authKey): string {
            return $authKey;
        });
        $shared = (new ReflectionClass(DataCenterConnection::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(DataCenterConnection::class, 'auth'))->setValue($shared, $auth);
        $wireSession = $change === 'mismatch' ? random_bytes(8) : $connection->session_id;
        $plain = random_bytes(8).$wireSession.pack('V2', 12345, 0).pack('V2', 1, 16).str_repeat('b', 16).str_repeat('p', 16);
        $msgKey = substr(Crypt::sha256(substr($authKey, 96, 32).$plain), 8, 16);
        [$key, $iv] = Crypt::kdf($msgKey, $authKey, false);
        $cipher = Crypt::igeEncrypt($plain, $key, $iv);
        if ($damaged) {
            $cipher[0] = \chr(\ord($cipher[0]) ^ 1);
        }
        $packet = $authId.$msgKey.$cipher;
        $length = \strlen($packet);
        $buffer = $this->createMock(ReadBufferInterface::class);
        $buffer->method('bufferRead')->willReturnCallback(static function (int $size) use (&$packet): string {
            $part = substr($packet, 0, $size);
            $packet = substr($packet, $size);
            return $part;
        });
        $stream = $this->createMock(CryptoTestTransport::class);
        $stream->method('getReadBuffer')->willReturnCallback(static function (?int &$size) use ($length, $buffer) {
            $size = $length;
            return $buffer;
        });
        $connection->stream = $stream;
        $reflection = new ReflectionClass(ReadLoop::class);
        $loop = $reflection->newInstanceWithoutConstructor();
        foreach (['API' => $api, 'connection' => $connection, 'shared' => $shared, 'datacenter' => '1'] as $property => $value) {
            $reflection->getProperty($property)->setValue($loop, $value);
        }
        $result = async($loop->readMessage(...));
        EventLoop::queue(static function () use ($connection, &$generation, &$authKey, $change): void {
            if ($change === 'reconnect') {
                $generation++;
            }
            if ($change === 'session') {
                $connection->session_id = random_bytes(8);
            }
            if ($change === 'auth') {
                $authKey = random_bytes(256);
            }
        });
        if ($change === 'mismatch') {
            $this->expectException(NothingInTheSocketException::class);
        }
        self::assertNull($result->await());
    }
    /** @return iterable<string, array{string}> */
    public static function outgoingChanges(): iterable
    {
        foreach (['reconnect', 'session', 'auth'] as $change) {
            yield $change => [$change];
        }
    }

    /** @dataProvider outgoingChanges */
    public function testUnsentMessagesSurviveConnectionChangesDuringEncryption(string $change): void
    {
        NativeExecutor::initialize();
        if (!NativeExecutor::get(3)) {
            self::markTestSkipped('Optional native IGE unavailable');
        }
        BypassFinals::enable();
        $api = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['logger', '__destruct'])->getMock();
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()
            ->onlyMethods(['shouldReconnect', 'getGeneration', 'isHttp', 'generateOutSeqNo'])->getMock();
        $connection->API = $api;
        $connection->session_id = random_bytes(8);
        $connection->method('shouldReconnect')->willReturn(false);
        $connection->method('isHttp')->willReturn(false);
        $connection->method('generateOutSeqNo')->willReturn(1);
        $generation = 1;
        $connection->method('getGeneration')->willReturnCallback(static function () use (&$generation): int {
            return $generation;
        });
        $connection->msgIdHandler = $this->getMockBuilder(MsgIdHandler::class)->disableOriginalConstructor()->getMock();
        $connection->msgIdHandler->method('generateMessageId')->willReturn(12345);
        $authKey = random_bytes(256);
        $auth = $this->getMockBuilder(NewAuthKey::class)->disableOriginalConstructor()
            ->onlyMethods(['getTempID', 'getTempAuthKey', 'getTempAuthKeyForHash', 'getServerSalt'])->getMock();
        $auth->method('getTempID')->willReturn(substr(sha1($authKey, true), -8));
        $auth->method('getTempAuthKey')->willReturnCallback(static function () use (&$authKey): string {
            return $authKey;
        });
        $auth->method('getTempAuthKeyForHash')->willReturn(substr($authKey, 88, 32));
        $auth->method('getServerSalt')->willReturn(random_bytes(8));
        $shared = (new ReflectionClass(DataCenterConnection::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(DataCenterConnection::class, 'auth'))->setValue($shared, $auth);
        $stream = $this->createMock(CryptoTestTransport::class);
        $stream->expects(self::never())->method('getWriteBuffer');
        $connection->stream = $stream;
        $outgoing = $this->getMockBuilder(MTProtoOutgoingMessage::class)->disableOriginalConstructor()
            ->onlyMethods(['hasBody', 'getSerializedBody', 'hasPromise', 'sent', '__toString'])->getMock();
        $outgoing->constructor = 'test.request';
        $outgoing->contentRelated = true;
        $outgoing->method('hasBody')->willReturn(true);
        $outgoing->method('getSerializedBody')->willReturn(str_repeat('b', 16));
        $outgoing->method('hasPromise')->willReturn(false);
        $outgoing->method('__toString')->willReturn('test request');
        $outgoing->expects(self::never())->method('sent');
        $queue = $this->getMockBuilder(LinkedList::class)->onlyMethods(['isEmpty'])->getMock();
        $queue->method('isEmpty')->willReturnOnConsecutiveCalls(false, true);
        $queue->prev = $outgoing;
        $outgoing->prev = $queue;
        $reflection = new ReflectionClass(WriteLoop::class);
        $loop = $reflection->newInstanceWithoutConstructor();
        foreach (['API' => $api, 'connection' => $connection, 'shared' => $shared, 'datacenter' => '1', 'queue' => $queue] as $property => $value) {
            $reflection->getProperty($property)->setValue($loop, $value);
        }
        $result = async($loop->encryptedWriteLoop(...));
        EventLoop::queue(static function () use ($connection, &$generation, &$authKey, $change): void {
            if ($change === 'reconnect') {
                $generation++;
            }
            if ($change === 'session') {
                $connection->session_id = random_bytes(8);
            }
            if ($change === 'auth') {
                $authKey = random_bytes(256);
            }
        });
        $result->await();
        self::assertSame($change === 'reconnect' ? 12345 : null, $outgoing->getMsgId());
        self::assertSame($change === 'reconnect' ? 1 : null, $outgoing->getSeqNo());
    }

}

interface CryptoTestTransport extends MTProtoBufferInterface, BufferedStreamInterface
{
}
