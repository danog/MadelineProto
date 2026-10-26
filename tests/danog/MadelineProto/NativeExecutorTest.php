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
    private static function submit(NativeExecutor $executor, bool $encrypt, string $data, string $key, string $iv, ?\Amp\Cancellation $cancellation = null): \Amp\Future
    {
        $cancellation?->throwIfRequested();
        return async(static fn (): array => $executor->run(static fn (): array => \danog\MadelineProto\Native\Crypto::ige($data, $key, $iv, $encrypt)));
    }

    private function reference(string $data, string $key, string $iv): string
    {
        $cipher = (new \ReflectionClass(\danog\MadelineProto\MTProtoTools\Crypt\IGEOpenssl::class))->newInstanceWithoutConstructor();
        $constructor = new \ReflectionMethod($cipher, '__construct');
        $constructor->invoke($cipher, $key, $iv);
        return $cipher->encrypt($data);
    }

    public static function setUpBeforeClass(): void
    {
        NativeExecutor::initialize(2, 4 * 1024 * 1024, 1000);
    }

    protected function setUp(): void
    {
        if (!NativeExecutor::get()) {
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
            // Compute the reference through the unchanged PHP backend.
            $expected = $this->reference($plain, $key, $iv);
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
        $expected = $this->reference(implode('', $parts), $key, $iv);
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
        $executor = NativeExecutor::get();
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $plain = random_bytes(65536);
        $expected = $this->reference($plain, $key, $iv);
        $future = self::submit($executor, true, $plain, $key, $iv);
        $cancel = new DeferredCancellation;
        $cancel->cancel();
        try {
            $future->await($cancel->getCancellation());
            self::fail('Cancelled waiter returned a result');
        } catch (CancelledException) {
            self::assertSame($expected, $future->await()[0]);
        }
    }

    public function testCancelledWaitUnderQueuePressureDoesNotBreakTheChain(): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $parts = array_fill(0, 12, random_bytes(65536));
        $expected = $this->reference(implode('', $parts), $key, $iv);
        $cipher = IGE::getInstance($key, $iv);
        $jobs = array_map(static fn (string $part) => async($cipher->encrypt(...), $part), $parts);
        $cancel = new DeferredCancellation;
        $cancel->cancel();
        try {
            $jobs[5]->await($cancel->getCancellation());
            self::fail('Cancelled wait returned normally');
        } catch (CancelledException) {
            self::assertSame($expected, implode('', array_map(static fn ($job) => $job->await(), $jobs)));
        }
        $tail = random_bytes(16);
        self::assertSame($this->reference(implode('', $parts).$tail, $key, $iv), $expected.$cipher->encrypt($tail));
    }

    public function testShutdownCompletesAcceptedWorkAndAllowsSynchronousContinuation(): void
    {
        $executor = NativeExecutor::get();
        $key = random_bytes(32);
        $iv = random_bytes(32);
        $plain = random_bytes(65536);
        $expected = $this->reference($plain.$plain, $key, $iv);
        $first = self::submit($executor, true, $plain, $key, $iv);
        $second = self::submit($executor, false, str_repeat("\0", 16), $key, $iv);
        \Amp\delay(0); // Let both operations enter the native bridge before shutdown.
        $executor->close();
        $result = $first->await();
        self::assertSame(16, \strlen($second->await()[0]));
        self::assertNull(NativeExecutor::get());
        self::assertSame($expected, $result[0].IGE::getInstance($key, $result[1])->encrypt($plain));
        $executor->close();
    }
}
