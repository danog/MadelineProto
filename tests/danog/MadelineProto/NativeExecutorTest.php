<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\Test;

use Amp\CancelledException;
use Amp\DeferredCancellation;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\Crypt\IGE;
use danog\MadelineProto\MTProtoTools\Crypt\IGENative;
use danog\MadelineProto\MTProtoTools\Crypt\NativeExecutor;
use PHPUnit\Framework\TestCase;
use function Amp\async;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class NativeExecutorTest extends TestCase
{
    private static function submit(NativeExecutor $executor, int $operation, string $data, string $key, string $iv, ?\Amp\Cancellation $cancellation = null): \Amp\Future
    {
        return $executor->submit(static fn (\FFI $ffi, \FFI\CData $pool): int => (int) $ffi->madeline_crypto_submit($pool, $operation, $data, \strlen($data), $key, $iv), $cancellation);
    }

    public static function setUpBeforeClass(): void
    {
        NativeExecutor::initialize(2, 4 * 1024 * 1024, 1000);
    }

    protected function setUp(): void
    {
        if (!NativeExecutor::get(3)) {
            self::markTestSkipped('Optional native crypto executor is unavailable');
        }
    }

    public function testConcurrentTransfersCanResumeWithTheirOwnState(): void
    {
        $tasks = [];
        for ($i = 0; $i < 12; $i++) {
            $key = random_bytes(32);
            $iv = random_bytes(32);
            $plain = random_bytes(65536);
            // Outside a Fiber, the reference uses the synchronous fallback.
            $expected = Crypt::igeEncrypt($plain, $key, $iv);
            $tasks[] = async(static function () use ($key, $iv, $plain, $expected): void {
                $encoder = IGE::getInstance($key, $iv);
                self::assertInstanceOf(IGENative::class, $encoder);
                $first = $encoder->encrypt(substr($plain, 0, 4096));
                $encoder = IGE::getInstance($key, $encoder->getState());
                self::assertSame($expected, $first.$encoder->encrypt(substr($plain, 4096)));
                $decoder = IGE::getInstance($key, $iv);
                self::assertSame($plain, $decoder->decrypt(substr($expected, 0, 8192)).$decoder->decrypt(substr($expected, 8192)));
                self::assertSame($encoder->getState(), $decoder->getState());
                $state = $decoder->getState();
                self::assertSame('', $decoder->decrypt(''));
                try {
                    $decoder->decrypt('partial block');
                    self::fail('Partial block was accepted');
                } catch (\ValueError) {
                    self::assertSame($state, $decoder->getState());
                }
            });
        }
        foreach ($tasks as $task) {
            $task->await();
        }
    }

    public function testConcurrentCallsOnOneCipherRemainInOrder(): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $parts = [random_bytes(4096), random_bytes(8192), random_bytes(16)];
        $expected = Crypt::igeEncrypt(implode('', $parts), $key, $iv);
        $actual = async(static function () use ($parts, $key, $iv): string {
            $cipher = IGE::getInstance($key, $iv);
            $jobs = [];
            foreach ($parts as $part) {
                $jobs[] = async($cipher->encrypt(...), $part);
            }
            return implode('', array_map(static fn ($job) => $job->await(), $jobs));
        })->await();
        self::assertSame($expected, $actual);
    }

    public function testCancellingAWaiterDoesNotCancelAcceptedWork(): void
    {
        $executor = NativeExecutor::get(3);
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $plain = random_bytes(65536);
        $expected = Crypt::igeEncrypt($plain, $key, $iv);
        $future = self::submit($executor, NativeExecutor::ENCRYPT, $plain, $key, $iv);
        $cancel = new DeferredCancellation;
        $cancel->cancel();
        try {
            $future->await($cancel->getCancellation());
            self::fail('Cancelled waiter returned a result');
        } catch (CancelledException) {
            self::assertSame($expected, $future->await()['data']);
        }
    }

    public function testCancellingSubmissionIntoAFullQueuePreservesOtherJobs(): void
    {
        $executor = NativeExecutor::get(3);
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $plain = random_bytes(65536);
        $expected = Crypt::igeEncrypt($plain, $key, $iv);
        $first = self::submit($executor, NativeExecutor::ENCRYPT, $plain, $key, $iv);
        $second = self::submit($executor, NativeExecutor::ENCRYPT, $plain, $key, $iv);
        $cancel = new DeferredCancellation;
        $waiting = async(static fn () => self::submit($executor, NativeExecutor::ENCRYPT, $plain, $key, $iv, $cancel->getCancellation())->await());
        \Revolt\EventLoop::queue($cancel->cancel(...));
        try {
            $waiting->await();
            self::fail('Cancelled submission was accepted');
        } catch (CancelledException) {
            self::assertSame($expected, $first->await()['data']);
            self::assertSame($expected, $second->await()['data']);
        }
        self::assertSame($expected, self::submit($executor, NativeExecutor::ENCRYPT, $plain, $key, $iv)->await()['data']);
    }

    public function testShutdownCompletesAcceptedWorkAndAllowsSynchronousContinuation(): void
    {
        $executor = NativeExecutor::get(3);
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $plain = random_bytes(65536);
        $expected = Crypt::igeEncrypt($plain.$plain, $key, $iv);
        $first = self::submit($executor, NativeExecutor::ENCRYPT, $plain, $key, $iv);
        $second = self::submit($executor, NativeExecutor::DECRYPT, str_repeat("\0", 16), $key, $iv);
        $executor->close();
        $result = $first->await();
        self::assertSame(16, \strlen($second->await()['data']));
        self::assertNull(NativeExecutor::get(3));
        self::assertSame($expected, $result['data'].IGE::getInstance($key, $result['iv'])->encrypt($plain));
        $executor->close();
    }
}
