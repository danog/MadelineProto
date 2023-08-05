<?php

declare(strict_types=1);

/**
 * Tools module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\TimeoutException;
use Closure;
use Generator;
use Revolt\EventLoop;
use Throwable;

use const LOCK_NB;
use const LOCK_UN;
use function Amp\async;
use function Amp\ByteStream\getOutputBufferStream;
use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;
use function Amp\delay;
use function Amp\Future\await;

use function Amp\Future\awaitAny;
use function Amp\Future\awaitFirst;

/**
 * Async tools.
 */
abstract class AsyncTools extends StrTools
{
    /**
     * Rethrow exception into event loop.
     */
    public static function rethrow(Throwable $e): void
    {
        EventLoop::queue(fn () => throw $e);
    }
    /**
     * Synchronously wait for a Future|generator.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @param Generator|Future $promise The promise to wait for
     */
    public static function wait(Generator|Future $promise)
    {
        if ($promise instanceof Generator) {
            return self::call($promise)->await();
        } elseif (!$promise instanceof Future) {
            return $promise;
        }
        return $promise->await();
    }
    /**
     * Returns a promise that succeeds when all promises succeed, and fails if any promise fails.
     * Returned promise succeeds with an array of values used to succeed each contained promise, with keys corresponding to the array of promises.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @param array<(Generator|Future)> $promises Promises
     */
    public static function all(array $promises)
    {
        return await(\array_map(self::call(...), $promises));
    }
    /**
     * Returns a promise that is resolved when all promises are resolved. The returned promise will not fail.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @param array<(Future|Generator)> $promises Promises
     */
    public static function any(array $promises)
    {
        return awaitAny(\array_map(self::call(...), $promises));
    }
    /**
     * Resolves with a two-item array delineating successful and failed Promise results.
     * The returned promise will only fail if the given number of required promises fail.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @param array<(Future|Generator)> $promises Promises
     */
    public static function some(array $promises)
    {
        return await(\array_map(self::call(...), $promises));
    }
    /**
     * Returns a promise that succeeds when the first promise succeeds, and fails only if all promises fail.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @param array<(Future|Generator)> $promises Promises
     */
    public static function first(array $promises)
    {
        return awaitFirst(\array_map(self::call(...), $promises));
    }
    /**
     * Create an artificial timeout for any Generator or Promise.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @param int $timeout In milliseconds
     */
    public static function timeout(Generator|Future $promise, int $timeout): mixed
    {
        return self::call($promise)->await(Tools::getTimeoutCancellation($timeout/1000));
    }
    /**
     * Creates an artificial timeout for any `Promise`.
     *
     * If the promise is resolved before the timeout expires, the result is returned
     *
     * If the timeout expires before the promise is resolved, a default value is returned
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @template TReturnAlt
     * @template TReturn
     * @template TGenerator of Generator<mixed, mixed, mixed, TReturn>
     * @param Future<TReturn>|TGenerator $promise Promise to which the timeout is applied.
     * @param int                        $timeout Timeout in milliseconds.
     * @param TReturnAlt                 $default
     * @return TReturn|TReturnAlt
     */
    public static function timeoutWithDefault($promise, int $timeout, $default = null): mixed
    {
        try {
            return self::timeout($promise, $timeout);
        } catch (CancelledException $e) {
            if (!$e->getPrevious() instanceof TimeoutException) {
                throw $e;
            }
            return $default;
        }
    }
    /**
     * Convert generator, promise or any other value to a promise.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @template TReturn
     * @param Generator<mixed, mixed, mixed, TReturn>|Future<TReturn>|TReturn $promise
     * @return Future<TReturn>
     */
    public static function call(mixed $promise): Future
    {
        if ($promise instanceof Generator) {
            return async(self::consumeGenerator(...), $promise);
        }
        if (!$promise instanceof Future) {
            $f = new DeferredFuture;
            $f->complete($promise);
            return $f->getFuture();
        }
        return $promise;
    }
    /**
     * @internal Consumes generator without creating fiber
     *
     */
    public static function consumeGenerator(Generator $g): mixed
    {
        $yielded = $g->current();
        do {
            while (!$yielded instanceof Future) {
                if (!$g->valid()) {
                    return $g->getReturn();
                }
                if ($yielded instanceof Generator) {
                    $yielded = self::consumeGenerator($yielded);
                } elseif (\is_array($yielded)) {
                    $yielded = \array_map(
                        fn ($v) => $v instanceof Generator ? self::consumeGenerator($v) : $v,
                        $yielded
                    );
                    $yielded = $g->send($yielded);
                } else {
                    $yielded = $g->send($yielded);
                }
            }
            try {
                $result = $yielded->await();
            } catch (Throwable $e) {
                $yielded = $g->throw($e);
                continue;
            }
            $yielded = $g->send($result);
        } while (true);
    }
    /**
     * Fork a new green thread and execute the passed function in the background.
     *
     * @template T
     *
     * @param \Closure(...):T $callable Function to execute
     * @param mixed ...$args Arguments forwarded to the function when forking the thread.
     *
     * @return Future<T>
     *
     * @psalm-suppress InvalidScope
     */
    public static function callFork(callable|Generator|Future $callable, ...$args): Future
    {
        if (\is_callable($callable)) {
            $callable = async($callable, ...$args);
        }
        if ($callable instanceof Generator) {
            $callable = self::call($callable);
        }
        return $callable;
    }
    /**
     * Call promise $b after promise $a.
     *
     * @deprecated Coroutines are deprecated since amp v3
     * @param Generator|Future $a Promise A
     * @param Generator|Future $b Promise B
     * @psalm-suppress InvalidScope
     */
    public static function after(Generator|Future $a, Generator|Future $b): Future
    {
        return async(function () use ($a, $b) {
            self::call($a)->await();
            return self::call($b)->await();
        });
    }
    /**
     * Asynchronously lock a file
     * Resolves with a callbable that MUST eventually be called in order to release the lock.
     *
     * @param string    $file      File to lock
     * @param integer   $operation Locking mode
     * @param float     $polling   Polling interval
     * @param ?Cancellation $token     Cancellation token
     * @param ?Closure $failureCb Failure callback, called only once if the first locking attempt fails.
     * @return ($token is null ? (Closure(): void) : ((Closure(): void)|null))
     */
    public static function flock(string $file, int $operation, float $polling = 0.1, ?Cancellation $token = null, ?Closure $failureCb = null): ?Closure
    {
        if (!\file_exists($file)) {
            \touch($file);
        }
        $operation |= LOCK_NB;
        $res = \fopen($file, 'c');
        do {
            $result = \flock($res, $operation);
            if (!$result) {
                if ($failureCb) {
                    EventLoop::queue($failureCb);
                    $failureCb = null;
                }
                if ($token) {
                    if ($token->isRequested()) {
                        return null;
                    }
                    try {
                        delay($polling, true, $token);
                    } catch (CancelledException) {
                        return null;
                    }
                } else {
                    delay($polling);
                }
            }
        } while (!$result);
        return static function () use (&$res): void {
            if ($res) {
                \flock($res, LOCK_UN);
                \fclose($res);
                $res = null;
            }
        };
    }
    /**
     * Asynchronously sleep.
     *
     * @param float $time Number of seconds to sleep for
     */
    public static function sleep(float $time): void
    {
        delay($time);
    }

    /**
     * @internal
     */
    public static function getTimeoutCancellation(float $timeout, string $message = "Operation timed out"): Cancellation
    {
        $e = new TimeoutException($message);
        $deferred = new DeferredCancellation;
        EventLoop::delay($timeout, fn () => $deferred->cancel($e));
        return $deferred->getCancellation();
    }

    /**
     * Asynchronously read line.
     *
     * @param string $prompt Prompt
     */
    public static function readLine(string $prompt = '', ?Cancellation $cancel = null): string
    {
        try {
            Magic::togglePeriodicLogging();
            $stdin = getStdin();
            $stdout = getStdout();
            if ($prompt) {
                $stdout->write($prompt);
            }
            static $lines = [''];
            while (\count($lines) < 2 && ($chunk = $stdin->read($cancel)) !== null) {
                $chunk = \explode("\n", \str_replace(["\r", "\n\n"], "\n", $chunk));
                $lines[\count($lines) - 1] .= \array_shift($chunk);
                $lines = \array_merge($lines, $chunk);
            }
        } finally {
            Magic::togglePeriodicLogging();
        }
        return \array_shift($lines) ?? '';
    }
    /**
     * Asynchronously write to stdout/browser.
     *
     * @param string $string Message to echo
     */
    public static function echo(string $string): void
    {
        getOutputBufferStream()->write($string);
    }
}
