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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\CancelledException;
use Amp\Future;
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
        // Deliberately not Amp\TimeoutCancellation: its timer is unreferenced,
        // but some callers rely on this timer alone to keep the event loop alive.
        return new ReferencedTimeoutCancellation($timeout, $message);
    }

    /**
     * Asynchronously read line.
     *
     * @param string  $prompt    Prompt
     * @param bool    $hidden    Whether to hide what is typed (for tokens, passwords and codes), when reading from a terminal
     * @param ?string $hideAfter Show what is typed until this character, censor what follows it with asterisks
     *                           (for prompts that take either a phone number or a bot token, which contains a
     *                           colon), when reading from a terminal
     */
    public static function readLine(string $prompt = '', ?Cancellation $cancel = null, bool $hidden = false, ?string $hideAfter = null): string
    {
        try {
            Magic::togglePeriodicLogging();
            $stdin = getStdin();
            $stdout = getStdout();
            if ($prompt) {
                $stdout->write($prompt);
            }
            static $lines = [''];
            if (($hidden || $hideAfter !== null) && \count($lines) < 2 && \defined('STDIN') && stream_isatty(STDIN)) {
                if (PHP_OS_FAMILY === 'Windows') {
                    return self::readHiddenLineWindows($cancel, $hidden ? null : $hideAfter);
                }
                if (!$hidden && ($restoreTerminal = self::disableEcho('-icanon -echo min 1')) !== null) {
                    try {
                        return self::readLineHidingAfter($stdin, $stdout, $lines, $hideAfter, $cancel);
                    } finally {
                        $restoreTerminal();
                    }
                }
                if ($hidden) {
                    $restoreEcho = self::disableEcho('-echo');
                }
            }
            try {
                while (\count($lines) < 2 && ($chunk = $stdin->read($cancel)) !== null) {
                    $chunk = explode("\n", str_replace(["\r", "\n\n"], "\n", $chunk));
                    $lines[\count($lines) - 1] .= array_shift($chunk);
                    $lines = array_merge($lines, $chunk);
                }
            } finally {
                if (isset($restoreEcho)) {
                    $restoreEcho();
                    // The newline typed wasn't echoed either.
                    $stdout->write(PHP_EOL);
                }
            }
        } finally {
            Magic::togglePeriodicLogging();
        }
        return array_shift($lines) ?? '';
    }

    /**
     * Read a line one character at a time, echoing it until $hideAfter and asterisks after it (Unix, in
     * non-canonical mode, without echo).
     *
     * @param list<string> $lines Lines read but not returned yet (what follows the newline of a paste)
     */
    private static function readLineHidingAfter(ReadableStream $stdin, WritableStream $stdout, array &$lines, string $hideAfter, ?Cancellation $cancel): string
    {
        $line = $lines[0];
        while (($chunk = $stdin->read($cancel)) !== null) {
            // Escape sequences (arrows...) can't edit the line.
            $chunk = preg_replace('/\e(\[[0-9;?]*[ -\/]*[@-~]|.)/s', '', $chunk);
            foreach (preg_split('//u', $chunk, -1, PREG_SPLIT_NO_EMPTY) ?: str_split($chunk) as $i => $char) {
                if ($char === "\r" || $char === "\n") {
                    $stdout->write(PHP_EOL);
                    // The rest of a paste, for the next reads.
                    $rest = implode('', \array_slice(preg_split('//u', $chunk, -1, PREG_SPLIT_NO_EMPTY) ?: str_split($chunk), $i + 1));
                    $rest = explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($rest, "\r\n")));
                    $lines = $rest === [''] ? [''] : $rest;
                    return $line;
                }
                if ($char === "\x7f" || $char === "\x08") {
                    if ($line !== '') {
                        $line = (string) preg_replace('/.$/su', '', $line);
                        // Shown, or as an asterisk.
                        $stdout->write("\x08 \x08");
                    }
                    continue;
                }
                if (\ord($char[0]) < 0x20) {
                    continue;
                }
                $stdout->write(str_contains($line, $hideAfter) ? '*' : $char);
                $line .= $char;
            }
        }
        $lines = [''];
        return $line;
    }

    /**
     * Configure the terminal to stop echoing what is typed (Unix), returning the function that restores it.
     *
     * @param string $mode Settings for stty
     *
     * @return ?Closure(): void Null if the terminal can't be configured (stty unavailable)
     */
    private static function disableEcho(string $mode): ?Closure
    {
        if (!\function_exists('shell_exec')) {
            return null;
        }
        $state = trim((string) @shell_exec('stty -g 2>/dev/null'));
        if ($state === '') {
            return null;
        }
        @shell_exec('stty '.$mode.' 2>/dev/null');
        $restored = false;
        $restore = static function () use ($state, &$restored): void {
            if (!$restored) {
                $restored = true;
                @shell_exec('stty '.escapeshellarg($state).' 2>/dev/null');
            }
        };
        // A signal (see Magic::start()) ends the script from the event loop, without running finally blocks.
        register_shutdown_function($restore);
        return $restore;
    }

    /**
     * Read a line from the console without echoing it (Windows, which has no stty), or echoing it until $hideAfter
     * and asterisks after it.
     *
     * PowerShell reads it from the console it inherits (echoing on its standard error, the console too); it's
     * polled, so that the event loop keeps running, and terminated if the read is cancelled.
     */
    private static function readHiddenLineWindows(?Cancellation $cancel, ?string $hideAfter): string
    {
        $script = $hideAfter === null
            ? '$s = Read-Host -AsSecureString; [Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR([Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)))'
            : '$h = '.var_export($hideAfter, true).'; $s = \'\'; while ($true) { $k = [Console]::ReadKey($true);'
                .' if ($k.Key -eq \'Enter\') { [Console]::Error.WriteLine(); break }'
                .' if ($k.Key -eq \'Backspace\') { if ($s.Length -gt 0) { $s = $s.Substring(0, $s.Length - 1); [Console]::Error.Write("`b `b") }; continue }'
                .' if ([char]::IsControl($k.KeyChar)) { continue }'
                .' if ($s.Contains($h)) { [Console]::Error.Write(\'*\') } else { [Console]::Error.Write($k.KeyChar) }; $s += $k.KeyChar }'
                .' [Console]::Out.Write($s)';
        $process = proc_open(
            ['powershell', '-NoProfile', '-Command', $script],
            [0 => STDIN, 1 => ['pipe', 'w'], 2 => STDERR],
            $pipes,
        );
        if ($process === false) {
            throw new Exception('Could not start powershell to read the hidden input');
        }
        try {
            while (proc_get_status($process)['running']) {
                delay(0.1, cancellation: $cancel);
            }
            return (string) stream_get_contents($pipes[1]);
        } finally {
            if (proc_get_status($process)['running']) {
                proc_terminate($process);
            }
            fclose($pipes[1]);
            proc_close($process);
        }
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
