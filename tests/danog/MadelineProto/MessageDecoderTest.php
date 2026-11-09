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
use danog\MadelineProto\MTProtoTools\Crypt\NativeExecutor;
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
        NativeExecutor::initialize();
        if ($class === NativeMessageDecoder::class && !NativeExecutor::get()) {
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
        NativeExecutor::initialize();
        if (!NativeExecutor::get()) {
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

    /** A message as the server encrypts it, then damaged. @return array{string, string, string, string} */
    private static function packet(int $size, int $padding, ?int $declared = null, string $damage = ''): array
    {
        $auth = random_bytes(256);
        $body = $size ? random_bytes($size) : '';
        $plain = random_bytes(8).random_bytes(8).pack('V2', 12345, 0).pack('V2', 7, $declared ?? $size).$body.str_repeat('p', $padding);
        $msgKey = substr(Crypt::sha256(substr($auth, 96, 32).$plain), 8, 16);
        [$key, $iv] = Crypt::kdf($msgKey, $auth, false);
        $cipher = Crypt::igeEncrypt($plain, $key, $iv);
        switch ($damage) {
            case 'auth': $auth[96] = \chr(\ord($auth[96]) ^ 1);
                break;
            case 'msg_key': $msgKey[15] = \chr(\ord($msgKey[15]) ^ 1);
                break;
            case 'ciphertext': $cipher[\strlen($cipher) - 1] = \chr(\ord($cipher[\strlen($cipher) - 1]) ^ 1);
                break;
        }
        return [$cipher, $auth, $msgKey, $body];
    }

    /** @return iterable<string, array{int, int, ?int, string}> */
    public static function thresholdPackets(): iterable
    {
        // Ciphertexts of 48 bytes up to around the synchronous threshold (16 KiB), and above it.
        foreach ([[0, 16], [4, 12], [16, 1024], [16304, 16], [16320, 16], [16336, 16], [16340, 12], [16352, 16], [20000, 16]] as [$size, $padding]) {
            yield "valid $size/$padding" => [$size, $padding, null, ''];
        }
        foreach ([16, 16320] as $size) {
            foreach (['auth', 'msg_key', 'ciphertext'] as $damage) {
                yield "$damage $size" => [$size, 16, null, $damage];
            }
            // Bad padding (too short, too long) and bad declared lengths (unaligned, past the end, huge).
            foreach ([[0, null], [1040, null], [16, $size + 1], [16, $size + 16], [16, 4294967280]] as [$padding, $declared]) {
                yield "bounds $size/$padding/$declared" => [$size, $padding, $declared, ''];
            }
        }
    }

    /**
     * The synchronous native decode, the executor's and PHP's accept the same messages with the same result, and
     * reject the same ones: natively with the same message and code.
     *
     * @dataProvider thresholdPackets
     */
    public function testSyncExecutorAndPhpDecodeIdentically(int $size, int $padding, ?int $declared, string $damage): void
    {
        NativeExecutor::initialize();
        if (!Crypt::native()) {
            self::markTestSkipped('The madeline_crypto extension (API 0.5) is not loaded');
        }
        [$cipher, $auth, $msgKey, $body] = self::packet($size, $padding, $declared, $damage);
        $outcome = static function (\Closure $decode): array {
            try {
                $result = $decode();
                return [$result->body, $result->messageId, $result->seqNo, $result->sessionId];
            } catch (\Throwable $e) {
                return [$e::class, $e->getMessage(), $e->getCode(), $e->getPrevious()?->getCode()];
            }
        };
        $php = $outcome(static fn () => (new PhpMessageDecoder)->decode($cipher, $auth, $msgKey));
        $decoder = $outcome(static fn () => (new NativeMessageDecoder)->decode($cipher, $auth, $msgKey));
        $raw = static function (string $method) use ($cipher, $auth, $msgKey): array {
            try {
                $call = static fn (): array => \danog\MadelineProto\Native\Crypto::$method($cipher, $auth, $msgKey);
                // The executor keeps the event loop alive while it suspends the caller.
                return $method === 'decode' ? NativeExecutor::get()->run($call) : $call();
            } catch (\Exception $e) {
                return [$e::class, $e->getMessage(), $e->getCode()];
            }
        };
        $sync = $raw('decodeSync');
        $valid = $damage === '' && $declared === null && $padding >= 12 && $padding <= 1024;
        if ($valid) {
            self::assertSame([$body, 12345, 7], \array_slice($php, 0, 3));
            self::assertSame($php, $decoder);
            self::assertSame($body, $sync[0]);
        } else {
            self::assertSame(SecurityException::class, $php[0]);
            self::assertSame(SecurityException::class, $decoder[0]);
            self::assertSame(NativeExecutor::INVALID, $sync[2]);
            // The decoder wraps the native failure.
            self::assertSame([$sync[1], NativeExecutor::INVALID], [$decoder[1], $decoder[3]]);
        }
        if (NativeExecutor::get()) {
            self::assertSame($sync, $raw('decode'));
        }
    }
}
