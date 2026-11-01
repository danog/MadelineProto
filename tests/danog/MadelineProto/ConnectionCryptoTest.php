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
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\Stream\ReadBufferInterface;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Revolt\EventLoop;

use function Amp\async;
use function Amp\delay;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConnectionCryptoTest extends TestCase
{
    /** @return iterable<string, array{string, bool, bool}> */
    public static function changes(): iterable
    {
        foreach ([false => 'decoding', true => 'reading'] as $reading => $while) {
            foreach (['disconnect', 'session', 'auth'] as $change) {
                yield "$change while $while" => [$change, false, (bool) $reading];
                yield "$change while $while, damaged ciphertext" => [$change, true, (bool) $reading];
            }
        }
        yield 'current session mismatch' => ['mismatch', false, false];
    }

    /**
     * Decoding a message can suspend, as reading it can: what changes meanwhile is handled as if it changed
     * before the message was read. It's decoded with the key its ID names, handled even if the socket was
     * disconnected meanwhile (it was received), and a message of a previous session resets the session.
     *
     * @dataProvider changes
     */
    public function testMessagesDecodedAcrossConnectionChanges(string $change, bool $damaged, bool $reading): void
    {
        NativeExecutor::initialize();
        if (!$reading && !NativeExecutor::get()) {
            self::markTestSkipped('Optional native decoder unavailable');
        }
        BypassFinals::enable();
        $api = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['logger', 'getTL', '__destruct'])->getMock();
        $api->expects(self::never())->method('getTL');
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()
            ->onlyMethods(['resetSession'])->getMock();
        $connection->API = $api;
        $connection->session_id = random_bytes(8);
        $previousSession = !$damaged && ($change === 'session' || $change === 'mismatch');
        $connection->expects($previousSession ? self::once() : self::never())->method('resetSession');
        // Handling a message starts with checking its ID.
        $connection->msgIdHandler = $this->getMockBuilder(MsgIdHandler::class)->disableOriginalConstructor()->getMock();
        $connection->msgIdHandler->expects(!$damaged && !$previousSession ? self::once() : self::never())
            ->method('checkIncomingMessageId')->with(12345)->willThrowException(new \RuntimeException('handled'));
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
        $authIdRead = false;
        $buffer->method('bufferRead')->willReturnCallback(static function (int $size) use (&$packet, &$authIdRead, $reading): string {
            if ($reading && $authIdRead) {
                // Waiting for the rest of the message, after its auth key ID.
                delay(0);
            }
            $authIdRead = true;
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
        EventLoop::queue(static function () use ($connection, &$authKey, $change): void {
            if ($change === 'disconnect') {
                $connection->stream = null;
            }
            if ($change === 'session') {
                $connection->session_id = random_bytes(8);
            }
            if ($change === 'auth') {
                $authKey = random_bytes(256);
            }
        });
        if ($damaged) {
            $this->expectException(SecurityException::class);
        } elseif ($previousSession) {
            $this->expectException(NothingInTheSocketException::class);
        } else {
            $this->expectExceptionMessage('handled');
        }
        $result->await();
    }
    /** @return iterable<string, array{string, bool, bool}> */
    public static function outgoingChanges(): iterable
    {
        foreach ([false => 'encrypting', true => 'building'] as $building => $while) {
            foreach (['disconnect', 'reconnect', 'session', 'auth'] as $change) {
                yield "$change while $while" => [$change, false, (bool) $building];
            }
            // auth.bindTempAuthKey embeds its msg_id and session: it can't be resent with new ones.
            yield "session-bound message, session while $while" => ['session', true, (bool) $building];
            yield "session-bound message, auth while $while" => ['auth', true, (bool) $building];
        }
    }

    /**
     * Building a payload (logging can suspend) and encrypting it (native IGE suspends) happen before it's
     * written: it's only written for the connection, session and key its message IDs were assigned for.
     *
     * @dataProvider outgoingChanges
     */
    public function testUnsentMessagesSurviveConnectionChangesBeforeTheyAreWritten(string $change, bool $sessionBound, bool $building): void
    {
        NativeExecutor::initialize();
        if (!$building && !NativeExecutor::get()) {
            self::markTestSkipped('Optional native IGE unavailable');
        }
        BypassFinals::enable();
        $api = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['logger', '__destruct'])->getMock();
        $api->method('logger')->willReturnCallback(static function (mixed $param) use ($building): void {
            if ($building && \is_string($param) && str_contains($param, 'as encrypted message with id')) {
                // A file logger waiting for its file, after the message got its ID.
                delay(0);
            }
        });
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()
            ->onlyMethods(['isHttp', 'generateOutSeqNo'])->getMock();
        $connection->API = $api;
        $connection->session_id = random_bytes(8);
        $connection->method('isHttp')->willReturn(false);
        $connection->method('generateOutSeqNo')->willReturn(1);
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
            ->onlyMethods(['hasBody', 'getSerializedBody', 'hasPromise', 'sent', 'reply', '__toString'])->getMock();
        \Closure::bind(static function () use ($outgoing, $sessionBound): void {
            $outgoing->sessionBound = $sessionBound;
        }, null, MTProtoOutgoingMessage::class)();
        $outgoing->expects($sessionBound ? self::once() : self::never())->method('reply');
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
        $replacement = $this->createMock(CryptoTestTransport::class);
        $replacement->expects(self::never())->method('getWriteBuffer');
        $result = async($loop->encryptedWriteLoop(...));
        EventLoop::queue(static function () use ($connection, &$authKey, $change, $replacement): void {
            if ($change === 'disconnect') {
                $connection->stream = null;
            }
            if ($change === 'reconnect') {
                $connection->stream = $replacement;
            }
            if ($change === 'session') {
                $connection->session_id = random_bytes(8);
            }
            if ($change === 'auth') {
                $authKey = random_bytes(256);
            }
        });
        $result->await();
        // Unsent messages keep their IDs across a reconnection, and get new ones in a new session
        // or with a new key, unless they are bound to their session: then they fail instead.
        $kept = $change === 'disconnect' || $change === 'reconnect' || $sessionBound;
        self::assertSame($kept ? 12345 : null, $outgoing->getMsgId());
        self::assertSame($kept ? 1 : null, $outgoing->getSeqNo());
    }

}

interface CryptoTestTransport extends MTProtoBufferInterface, BufferedStreamInterface
{
}
