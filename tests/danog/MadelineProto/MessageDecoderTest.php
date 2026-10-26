<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\Crypt\MessageDecoder;
use danog\MadelineProto\MTProtoTools\Crypt\NativeCrypto;
use danog\MadelineProto\MTProtoTools\Crypt\NativeMessageDecoder;
use danog\MadelineProto\MTProtoTools\Crypt\PhpMessageDecoder;
use danog\MadelineProto\SecurityException;
use PHPUnit\Framework\TestCase;

final class MessageDecoderTest extends TestCase
{
    /** @return iterable<string, array{class-string<MessageDecoder>, int, int, ?int, string}> */
    public static function packets(): iterable
    {
        foreach ([PhpMessageDecoder::class, NativeMessageDecoder::class] as $decoder) {
            foreach ([[0,16], [4,12], [1024,1024], [65536,16]] as [$size, $padding]) {
                yield $decoder." valid $size/$padding" => [$decoder, $size, $padding, null, ''];
            }
            foreach ([[16,0,16], [16,1040,16], [16,16,17], [16,16,4294967280], [16,16,40]] as [$size,$padding,$declared]) {
                yield $decoder." invalid $padding/$declared" => [$decoder, $size, $padding, $declared, 'bounds'];
            }
            foreach (['auth', 'msg_key', 'ciphertext', 'truncated', 'short', 'unaligned'] as $damage) {
                yield $decoder.' '.$damage => [$decoder, 1024, 16, null, $damage];
            }
        }
    }

    /** @dataProvider packets */
    public function testAuthenticatedPayloads(string $class, int $size, int $padding, ?int $declared, string $damage): void
    {
        NativeCrypto::initialize();
        if ($class === NativeMessageDecoder::class && !NativeCrypto::get(NativeCrypto::DECODE_V2)) {
            self::markTestSkipped('Optional native decoder unavailable');
        }
        $auth = random_bytes(256);
        $session = random_bytes(8);
        $body = $size ? random_bytes($size) : '';
        $plain = random_bytes(8).$session.pack('V2', 12345, 0).pack('V2', 7, $declared ?? $size).$body.str_repeat('p', $padding);
        $msgKey = substr(Crypt::sha256(substr($auth, 96, 32).$plain), 8, 16);
        [$key, $iv] = Crypt::kdf($msgKey, $auth, false);
        $cipher = Crypt::igeEncrypt($plain, $key, $iv);
        switch ($damage) {
            case 'auth': $auth[96] = \chr(\ord($auth[96]) ^ 1);
                break;
            case 'msg_key': $msgKey[0] = \chr(\ord($msgKey[0]) ^ 1);
                break;
            case 'ciphertext': $cipher[40] = \chr(\ord($cipher[40]) ^ 1);
                break;
            case 'truncated': $cipher = substr($cipher, 0, -16);
                break;
            case 'short': $cipher = substr($cipher, 0, 16);
                break;
            case 'unaligned': $cipher = substr($cipher, 0, -1);
                break;
        }
        if ($damage !== '') {
            $this->expectException(SecurityException::class);
        }
        $result = (new $class)->decode($cipher, $auth, $msgKey);
        self::assertSame($body, $result->body);
        self::assertSame(12345, $result->messageId);
        self::assertSame(7, $result->seqNo);
        self::assertSame($session, $result->sessionId);
    }

    public function testConcurrentMessagesWithDifferentPaddingRemainIndependent(): void
    {
        NativeCrypto::initialize();
        if (!NativeCrypto::get(NativeCrypto::DECODE_V2)) {
            self::markTestSkipped('Optional native decoder unavailable');
        }
        $tasks = [];
        for ($i = 0; $i < 24; $i++) {
            $auth = random_bytes(256);
            $body = random_bytes(65536);
            $session = random_bytes(8);
            $plain = random_bytes(8).$session.pack('V2', $i + 1, 0).pack('V2', $i, \strlen($body)).$body.str_repeat('p', 16 * ($i + 1));
            $msgKey = substr(Crypt::sha256(substr($auth, 96, 32).$plain), 8, 16);
            [$key, $iv] = Crypt::kdf($msgKey, $auth, false);
            $cipher = Crypt::igeEncrypt($plain, $key, $iv);
            $tasks[] = \Amp\async(static function () use ($cipher, $auth, $msgKey, $body, $session, $i): void {
                $result = (new NativeMessageDecoder)->decode($cipher, $auth, $msgKey);
                self::assertSame($body, $result->body);
                self::assertSame($session, $result->sessionId);
                self::assertSame($i + 1, $result->messageId);
                self::assertSame($i, $result->seqNo);
            });
        }
        foreach ($tasks as $task) {
            $task->await();
        }
    }

    public function testNativeDecoderRetainsPhpFallback(): void
    {
        // This test also runs with ffi.enable=0 or an older synchronous helper.
        $auth = str_repeat('k', 256);
        $plain = str_repeat('s', 16).pack('V2', 12345, 0).pack('V2', 7, 16).str_repeat('b', 16).str_repeat('p', 16);
        $msgKey = substr(Crypt::sha256(substr($auth, 96, 32).$plain), 8, 16);
        [$key, $iv] = Crypt::kdf($msgKey, $auth, false);
        $cipher = Crypt::igeEncrypt($plain, $key, $iv);
        self::assertEquals((new PhpMessageDecoder)->decode($cipher, $auth, $msgKey), (new NativeMessageDecoder)->decode($cipher, $auth, $msgKey));
    }
}
