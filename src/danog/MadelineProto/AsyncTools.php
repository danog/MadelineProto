<?php declare(strict_types=1);

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Failure;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use Amp\TimeoutException;
use Exception;
use Generator;
use Throwable;
use TypeError;

use const LOCK_NB;
use const LOCK_UN;
use function Amp\ByteStream\getOutputBufferStream;
use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;
use function Amp\delay;
use function Amp\File\exists;

use function Amp\File\touch as touchAsync;
use function Amp\Promise\all;
use function Amp\Promise\any;
use function Amp\Promise\first;
use function Amp\Promise\some;

/**
 * Async tools.
 */
abstract class AsyncTools extends StrTools
{
    /**
     * Synchronously wait for a promise|generator.
     *
     * @param Generator|Promise $promise The promise to wait for
     * @param boolean            $ignoreSignal Whether to ignore shutdown signals
     */
    public static function wait($promise, bool $ignoreSignal = false)
    {
        if ($promise instanceof Generator) {
            $promise = new Coroutine($promise);
        } elseif (!$promise instanceof Promise) {
            return $promise;
        }
        $exception = null;
        $value = null;
        $resolved = false;
        do {
            try {
                //Logger::log("Starting event loop...");
                Loop::run(function () use (&$resolved, &$value, &$exception, $promise): void {
                    $promise->onResolve(function ($e, $v) use (&$resolved, &$value, &$exception): void {
                        Loop::stop();
                        $resolved = true;
                        $exception = $e;
                        $value = $v;
                    });
                });
            } catch (Throwable $throwable) {
                Logger::log('Loop exceptionally stopped without resolving the promise', Logger::FATAL_ERROR);
                Logger::log((string) $throwable, Logger::FATAL_ERROR);
                throw $throwable;
            }
        } while (!$resolved && !(Magic::$signaled && !$ignoreSignal));
        if ($exception) {
            throw $exception;
        }
        return $value;
    }
    /**
     * Returns a promise that succeeds when all promises succeed, and fails if any promise fails.
     * Returned promise succeeds with an array of values used to succeed each contained promise, with keys corresponding to the array of promises.
     *
     * @param array<(Generator|Promise)> $promises Promises
     */
    public static function all(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }
        /** @var Promise[] $promises */
        return all($promises);
    }
    /**
     * Returns a promise that is resolved when all promises are resolved. The returned promise will not fail.
     *
     * @param array<(Promise|Generator)> $promises Promises
     */
    public static function any(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }
        /** @var Promise[] $promises */
        return any($promises);
    }
    /**
     * Resolves with a two-item array delineating successful and failed Promise results.
     * The returned promise will only fail if the given number of required promises fail.
     *
     * @param array<(Promise|Generator)> $promises Promises
     */
    public static function some(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }
        /** @var Promise[] $promises */
        return some($promises);
    }
    /**
     * Returns a promise that succeeds when the first promise succeeds, and fails only if all promises fail.
     *
     * @param array<(Promise|Generator)> $promises Promises
     */
    public static function first(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }
        /** @var Promise[] $promises */
        return first($promises);
    }
    /**
     * Create an artificial timeout for any \Generator or Promise.
     *
     * @param Generator|Promise $promise
     */
    public static function timeout($promise, int $timeout): Promise
    {
        $promise = self::call($promise);

        $deferred = new Deferred;

        $watcher = Loop::delay($timeout, static function () use (&$deferred): void {
            $temp = $deferred; // prevent double resolve
            $deferred = null;
            $temp->fail(new TimeoutException);
        });
        //Loop::unreference($watcher);

        $promise->onResolve(function () use (&$deferred, $promise, $watcher): void {
            if ($deferred !== null) {
                Loop::cancel($watcher);
                $deferred->resolve($promise);
            }
        });

        return $deferred->promise();
    }
    /**
     * Creates an artificial timeout for any `Promise`.
     *
     * If the promise is resolved before the timeout expires, the result is returned
     *
     * If the timeout expires before the promise is resolved, a default value is returned
     *
     * @template TReturnAlt
     * @template TReturn
     * @template TGenerator of Generator<mixed, mixed, mixed, TReturn>
     * @param Promise|Generator $promise Promise to which the timeout is applied.
     * @param int               $timeout Timeout in milliseconds.
     * @psalm-param Promise<TReturn>|TGenerator $promise Promise to which the timeout is applied.
     * @psalm-param TReturnAlt $default
     * @return Promise<TReturn>|Promise<TReturnAlt>
     * @throws TypeError If $promise is not an instance of \Amp\Promise, \Generator or \React\Promise\PromiseInterface.
     */
    public static function timeoutWithDefault($promise, int $timeout, $default = null): Promise
    {
        $promise = self::call($promise);

        $deferred = new Deferred;

        $watcher = Loop::delay($timeout, static function () use (&$deferred, $default): void {
            $temp = $deferred; // prevent double resolve
            $deferred = null;
            $temp->resolve($default);
        });
        //Loop::unreference($watcher);

        $promise->onResolve(function () use (&$deferred, $promise, $watcher): void {
            if ($deferred !== null) {
                Loop::cancel($watcher);
                $deferred->resolve($promise);
            }
        });

        return $deferred->promise();
    }
    /**
     * Convert generator, promise or any other value to a promise.
     *
     * @param Generator|Promise|mixed $promise
     * @template TReturn
     * @psalm-param Generator<mixed, mixed, mixed, TReturn>|Promise<TReturn>|TReturn $promise
     * @psalm-return Promise<TReturn>
     */
    public static function call($promise): Promise
    {
        if ($promise instanceof Generator) {
            $promise = new Coroutine($promise);
        } elseif (!$promise instanceof Promise) {
            return new Success($promise);
        }
        return $promise;
    }
    /**
     * Call promise in background.
     *
     * @param Generator|Promise $promise Promise to resolve
     * @param ?\Generator|Promise $actual  Promise to resolve instead of $promise
     * @param string              $file    File
     * @psalm-suppress InvalidScope
     * @return Promise|mixed
     */
    public static function callFork($promise, $actual = null, string $file = '')
    {
        if ($actual) {
            $promise = $actual;
        }
        if ($promise instanceof Generator) {
            $promise = new Coroutine($promise);
        }
        if ($promise instanceof Promise) {
            $promise->onResolve(function ($e, $res) use ($file): void {
                if ($e) {
                    if (isset($this)) {
                        $this->rethrow($e, $file);
                    } else {
                        self::rethrow($e, $file);
                    }
                }
            });
        }
        return $promise;
    }
    /**
     * Call promise in background, deferring execution.
     *
     * @param Generator|Promise $promise Promise to resolve
     */
    public static function callForkDefer($promise): void
    {
        Loop::defer(fn () => self::callFork($promise));
    }
    /**
     * Rethrow error catched in strand.
     *
     * @param Throwable $e Exception
     * @param string     $file File where the strand started
     * @psalm-suppress InvalidScope
     */
    public static function rethrow(Throwable $e, string $file = ''): void
    {
        $zis = $this ?? null;
        $logger = $zis->logger ?? Logger::$default;
        if ($file) {
            $file = " started @ {$file}";
        }
        if ($logger) {
            $logger->logger("Got the following exception within a forked strand{$file}, trying to rethrow");
        }
        if ($e->getMessage() === "Cannot get return value of a generator that hasn't returned") {
            $logger->logger("Well you know, this might actually not be the actual exception, scroll up in the logs to see the actual exception");
            if (!$zis || !$zis->destructing) {
                Promise\rethrow(new Failure($e));
            }
        } else {
            if ($logger) {
                $logger->logger($e);
            }
            Promise\rethrow(new Failure($e));
        }
    }
    /**
     * Call promise $b after promise $a.
     *
     * @param Generator|Promise $a Promise A
     * @param Generator|Promise $b Promise B
     * @psalm-suppress InvalidScope
     */
    public static function after($a, $b): Promise
    {
        $a = self::call($a);
        $deferred = new Deferred();
        $a->onResolve(static function ($e, $res) use ($b, $deferred): void {
            if ($e) {
                if (isset($this)) {
                    $this->rethrow($e);
                } else {
                    self::rethrow($e);
                }
                return;
            }
            $b = self::call($b);
            $b->onResolve(function ($e, $res) use ($deferred): void {
                if ($e) {
                    if (isset($this)) {
                        $this->rethrow($e);
                    } else {
                        self::rethrow($e);
                    }
                    return;
                }
                $deferred->resolve($res);
            });
        });
        return $deferred->promise();
    }
    /**
     * Asynchronously lock a file
     * Resolves with a callbable that MUST eventually be called in order to release the lock.
     *
     * @param string    $file      File to lock
     * @param integer   $operation Locking mode
     * @param float     $polling   Polling interval
     * @param ?Promise  $token     Cancellation token
     * @param ?callable $failureCb Failure callback, called only once if the first locking attempt fails.
     * @return $token is null ? (callable(): void) : ((callable(): void)|null)
     */
    public static function flock(string $file, int $operation, float $polling, ?Promise $token = null, ?callable $failureCb = null): ?callable
    {
        if (!exists($file)) {
            touchAsync($file);
        }
        $operation |= LOCK_NB;
        $res = \fopen($file, 'c');
        do {
            $result = \flock($res, $operation);
            if (!$result) {
                if ($failureCb) {
                    $failureCb();
                    $failureCb = null;
                }
                if ($token) {
                    if (self::timeoutWithDefault($token, $polling, false)) {
                        return;
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
     * Asynchronously read line.
     *
     * @param string $prompt Prompt
     */
    public static function readLine(string $prompt = ''): string
    {
        try {
            Magic::togglePeriodicLogging();
            $stdin = getStdin();
            $stdout = getStdout();
            if ($prompt) {
                $stdout->write($prompt);
            }
            static $lines = [''];
            while (\count($lines) < 2 && ($chunk = $stdin->read()) !== null) {
                $chunk = \explode("\n", \str_replace(["\r", "\n\n"], "\n", $chunk));
                $lines[\count($lines) - 1] .= \array_shift($chunk);
                $lines = \array_merge($lines, $chunk);
            }
        } finally {
            Magic::togglePeriodicLogging();
        }
        return \array_shift($lines);
    }
    /**
     * Asynchronously write to stdout/browser.
     *
     * @param string $string Message to echo
     */
    public static function echo(string $string): Promise
    {
        return getOutputBufferStream()->write($string);
    }
}
