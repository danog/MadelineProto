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
        EventLoop::queue(static fn () => throw $e);
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
        return $callable;
    }
    /**
     * Asynchronously lock a file
     * Resolves with a callbable that MUST eventually be called in order to release the lock.
     *
     * @param  string                                                          $file      File to lock
     * @param  integer                                                         $operation Locking mode
     * @param  float                                                           $polling   Polling interval
     * @param  ?Cancellation                                                   $token     Cancellation token
     * @param  ?Closure                                                        $failureCb Failure callback, called only once if the first locking attempt fails.
     * @return ($token is null ? (Closure(): void) : ((Closure(): void)|null))
     */
    public static function flock(string $file, int $operation, float $polling = 0.1, ?Cancellation $token = null, ?Closure $failureCb = null): ?Closure
    {
        if (!file_exists($file)) {
            touch($file);
        }
        $operation |= LOCK_NB;
        $res = fopen($file, 'c');
        do {
            $result = flock($res, $operation);
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
                flock($res, LOCK_UN);
                fclose($res);
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
        EventLoop::delay($timeout, static fn () => $deferred->cancel($e));
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
                $chunk = explode("\n", str_replace(["\r", "\n\n"], "\n", $chunk));
                $lines[\count($lines) - 1] .= array_shift($chunk);
                $lines = array_merge($lines, $chunk);
            }
        } finally {
            Magic::togglePeriodicLogging();
        }
        return array_shift($lines) ?? '';
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
