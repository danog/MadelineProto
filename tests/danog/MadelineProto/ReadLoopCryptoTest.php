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
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\Crypt\NativeCrypto;
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
final class ReadLoopCryptoTest extends TestCase
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
        NativeCrypto::initialize();
        if (!NativeCrypto::get(NativeCrypto::DECODE_V2)) {
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
}

interface CryptoTestTransport extends MTProtoBufferInterface, BufferedStreamInterface
{
}
