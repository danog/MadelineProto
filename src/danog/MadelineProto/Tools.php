<?php
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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Failure;
use Amp\File\StatCache;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use tgseclib\Math\BigInteger;

use function Amp\ByteStream\getOutputBufferStream;
use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;
use function Amp\File\exists;
use function Amp\Promise\all;
use function Amp\Promise\any;
use function Amp\Promise\first;
use function Amp\Promise\some;
use function Amp\Promise\timeout;
use function Amp\Promise\wait;

/**
 * Some tools.
 */
trait Tools
{

    /**
     * Sanify TL obtained from JSON for TL serialization.
     *
     * @param array $input Data to sanitize
     *
     * @internal
     *
     * @return array
     */
    public static function convertJsonTL(array $input): array
    {
        $cb = static function (&$val) use (&$cb) {
            if (isset($val['@type'])) {
                $val['_'] = $val['@type'];
            } elseif (\is_array($val)) {
                \array_walk($val, $cb);
            }
        };
        \array_walk($input, $cb);
        return $input;
    }
    /**
     * Generate MTProto vector hash.
     *
     * @param array $ints IDs
     *
     * @return int Vector hash
     */
    public static function genVectorHash(array $ints): int
    {
        //sort($ints, SORT_NUMERIC);
        if (\danog\MadelineProto\Magic::$bigint) {
            $hash = new \tgseclib\Math\BigInteger(0);
            foreach ($ints as $int) {
                $hash = $hash->multiply(\danog\MadelineProto\Magic::$twozerotwosixone)->add(\danog\MadelineProto\Magic::$zeroeight)->add(new \tgseclib\Math\BigInteger($int))->divide(\danog\MadelineProto\Magic::$zeroeight)[1];
            }
            $hash = self::unpackSignedInt(\strrev(\str_pad($hash->toBytes(), 4, "\0", STR_PAD_LEFT)));
        } else {
            $hash = 0;
            foreach ($ints as $int) {
                $hash = ((($hash * 20261) & 0x7FFFFFFF) + $int) & 0x7FFFFFFF;
            }
        }

        return $hash;
    }

    /**
     * Get random integer.
     *
     * @param integer $modulus Modulus
     *
     * @return int
     */
    public static function randomInt(int $modulus = 0): int
    {
        if ($modulus === 0) {
            $modulus = PHP_INT_MAX;
        }

        try {
            return \random_int(0, PHP_INT_MAX) % $modulus;
        } catch (\Exception $e) {
            // random_compat will throw an Exception, which in PHP 5 does not implement Throwable
        } catch (\Throwable $e) {
            // If a sufficient source of randomness is unavailable, random_bytes() will throw an
            // object that implements the Throwable interface (Exception, TypeError, Error).
            // We don't actually need to do anything here. The string() method should just continue
            // as normal. Note, however, that if we don't have a sufficient source of randomness for
            // random_bytes(), most of the other calls here will fail too, so we'll end up using
            // the PHP implementation.
        }

        if (Magic::$bigint) {
            $number = self::unpackSignedInt(self::random(4));
        } else {
            $number = self::unpackSignedLong(self::random(8));
        }

        return ($number & PHP_INT_MAX) % $modulus;
    }

    /**
     * Get random string of specified length.
     *
     * @param integer $length Length
     *
     * @return string Random string
     */
    public static function random(int $length): string
    {
        return $length === 0 ? '' : \tgseclib\Crypt\Random::string($length);
    }

    /**
     * Positive modulo
     * Works just like the % (modulus) operator, only returns always a postive number.
     *
     * @param int $a A
     * @param int $b B
     *
     * @return int Modulo
     */
    public static function posmod(int $a, int $b): int
    {
        $resto = $a % $b;

        return $resto < 0 ? $resto + \abs($b) : $resto;
    }

    /**
     * Unpack base256 signed int.
     *
     * @param string $value base256 int
     *
     * @return integer
     */
    public static function unpackSignedInt(string $value): int
    {
        if (\strlen($value) !== 4) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_4']);
        }

        return \unpack('l', \danog\MadelineProto\Magic::$BIG_ENDIAN ? \strrev($value) : $value)[1];
    }

    /**
     * Unpack base256 signed long.
     *
     * @param string $value base256 long
     *
     * @return integer
     */
    public static function unpackSignedLong(string $value): int
    {
        if (\strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return \unpack('q', \danog\MadelineProto\Magic::$BIG_ENDIAN ? \strrev($value) : $value)[1];
    }
    /**
     * Unpack base256 signed long to string.
     *
     * @param string $value base256 long
     *
     * @return string
     */
    public static function unpackSignedLongString($value): string
    {
        if (\is_int($value)) {
            return (string) $value;
        }
        if (\strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        $big = new BigInteger((string) $value, -256);
        return (string) $big;
    }

    /**
     * Convert integer to base256 signed int.
     *
     * @param integer $value Value to convert
     *
     * @return string
     */
    public static function packSignedInt(int $value): string
    {
        if ($value > 2147483647) {
            throw new TL\Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_2147483647'], $value));
        }
        if ($value < -2147483648) {
            throw new TL\Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_2147483648'], $value));
        }
        $res = \pack('l', $value);

        return \danog\MadelineProto\Magic::$BIG_ENDIAN ? \strrev($res) : $res;
    }

    /**
     * Convert integer to base256 long.
     *
     * @param int $value Value to convert
     *
     * @return string
     */
    public static function packSignedLong(int $value): string
    {
        if ($value > 9223372036854775807) {
            throw new TL\Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_9223372036854775807'], $value));
        }
        if ($value < -9.223372036854776E+18) {
            throw new TL\Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_9223372036854775808'], $value));
        }
        $res = \danog\MadelineProto\Magic::$bigint ? self::packSignedInt($value)."\0\0\0\0" : (\danog\MadelineProto\Magic::$BIG_ENDIAN ? \strrev(\pack('q', $value)) : \pack('q', $value));

        return $res;
    }

    /**
     * Convert value to unsigned base256 int.
     *
     * @param int $value Value
     *
     * @return string
     */
    public static function packUnsignedInt(int $value): string
    {
        if ($value > 4294967295) {
            throw new TL\Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_4294967296'], $value));
        }
        if ($value < 0) {
            throw new TL\Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_0'], $value));
        }

        return \pack('V', $value);
    }

    /**
     * Convert double to binary version.
     *
     * @param float $value Value to convert
     *
     * @return string
     */
    public static function packDouble(float $value): string
    {
        $res = \pack('d', $value);
        if (\strlen($res) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['encode_double_error']);
        }

        return \danog\MadelineProto\Magic::$BIG_ENDIAN ? \strrev($res) : $res;
    }

    /**
     * Unpack binary double.
     *
     * @param string $value Value to unpack
     *
     * @return float
     */
    public static function unpackDouble(string $value): float
    {
        if (\strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return \unpack('d', \danog\MadelineProto\Magic::$BIG_ENDIAN ? \strrev($value) : $value)[1];
    }

    /**
     * Synchronously wait for a promise|generator.
     *
     * @param \Generator|Promise $promise      The promise to wait for
     * @param boolean            $ignoreSignal Whether to ignore shutdown signals
     *
     * @return mixed
     */
    public static function wait($promise, $ignoreSignal = false)
    {
        if ($promise instanceof \Generator) {
            $promise = new Coroutine($promise);
        } elseif (!($promise instanceof Promise)) {
            return $promise;
        }

        $exception = null;
        $value = null;
        $resolved = false;
        do {
            try {
                Loop::run(function () use (&$resolved, &$value, &$exception, $promise) {
                    $promise->onResolve(function ($e, $v) use (&$resolved, &$value, &$exception) {
                        Loop::stop();
                        $resolved = true;
                        $exception = $e;
                        $value = $v;
                    });
                });
            } catch (\Throwable $throwable) {
                throw new \Error('Loop exceptionally stopped without resolving the promise', 0, $throwable);
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
     * @param array<\Generator|Promise> $promises Promises
     *
     * @return Promise
     */
    public static function all(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return all($promises);
    }

    /**
     * Returns a promise that is resolved when all promises are resolved. The returned promise will not fail.
     *
     * @param array<Promise|\Generator> $promises Promises
     *
     * @return Promise
     */
    public static function any(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return any($promises);
    }

    /**
     * Resolves with a two-item array delineating successful and failed Promise results.
     * The returned promise will only fail if the given number of required promises fail.
     *
     * @param array<Promise|\Generator> $promises Promises
     *
     * @return Promise
     */
    public static function some(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return some($promises);
    }

    /**
     * Returns a promise that succeeds when the first promise succeeds, and fails only if all promises fail.
     *
     * @param array<Promise|\Generator> $promises Promises
     *
     * @return Promise
     */
    public static function first(array $promises): Promise
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return first($promises);
    }

    /**
     * Create an artificial timeout for any \Generator or Promise.
     *
     * @param \Generator|Promise $promise
     * @param integer $timeout
     *
     * @return Promise
     */
    public static function timeout($promise, int $timeout): Promise
    {
        return timeout(self::call($promise), $timeout);
    }

    /**
     * Convert generator, promise or any other value to a promise.
     *
     * @param \Generator|Promise|mixed $promise
     *
     * @return Promise
     */
    public static function call($promise): Promise
    {
        if ($promise instanceof \Generator) {
            $promise = new Coroutine($promise);
        } elseif (!($promise instanceof Promise)) {
            return new Success($promise);
        }

        return $promise;
    }

    /**
     * Call promise in background.
     *
     * @param \Generator|Promise  $promise Promise to resolve
     * @param ?\Generator|Promise $actual  Promise to resolve instead of $promise
     * @param string              $file    File
     *
     * @return Promise
     */
    public static function callFork($promise, $actual = null, $file = '')
    {
        if ($actual) {
            $promise = $actual;
        } else {
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $file = '';
            if (isset($trace['file'])) {
                $file .= \basename($trace['file'], '.php');
            }
            if (isset($trace['line'])) {
                $file .= ":{$trace['line']}";
            }
        }
        if ($promise instanceof \Generator) {
            $promise = new Coroutine($promise);
        }
        if ($promise instanceof Promise) {
            $promise->onResolve(function ($e, $res) use ($file) {
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
     * @param \Generator|Promise $promise Promise to resolve
     *
     * @return void
     */
    public static function callForkDefer($promise): void
    {
        Loop::defer([__CLASS__, 'callFork'], $promise);
    }

    /**
     * Rethrow error catched in strand.
     *
     * @param \Throwable $e    Exception
     * @param string     $file File where the strand started
     *
     * @return void
     */
    public static function rethrow(\Throwable $e, $file = ''): void
    {
        $zis = isset($this) ? $this : null;
        $logger = isset($zis->logger) ? $zis->logger : Logger::$default;
        if ($file) {
            $file = " started @ $file";
        }
        if ($logger) {
            $logger->logger("Got the following exception within a forked strand$file, trying to rethrow");
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
     * @param \Generator|Promise $a Promise A
     * @param \Generator|Promise $b Promise B
     *
     * @return Promise
     */
    public static function after($a, $b): Promise
    {
        $a = self::call($a());
        $deferred = new Deferred();
        $a->onResolve(static function ($e, $res) use ($b, $deferred) {
            if ($e) {
                if (isset($this)) {
                    $this->rethrow($e);
                } else {
                    self::rethrow($e);
                }
                return;
            }
            $b = self::call($b());
            $b->onResolve(function ($e, $res) use ($deferred) {
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
     * Asynchronously send noCache headers.
     *
     * @param integer $status  HTTP status code to send
     * @param string  $message Message to print
     *
     * @return Promise
     */
    public static function noCache(int $status, string $message): Promise
    {
        \header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        \header('Cache-Control: post-check=0, pre-check=0', false);
        \header('Pragma: no-cache');
        \http_response_code($status);
        return self::echo($message);
    }

    /**
     * Asynchronously lock a file
     * Resolves with a callbable that MUST eventually be called in order to release the lock.
     *
     * @param string  $file      File to lock
     * @param integer $operation Locking mode
     * @param float  $polling   Polling interval
    *
     * @return Promise
     */
    public static function flock(string $file, int $operation, float $polling = 0.1): Promise
    {
        return self::call(Tools::flockGenerator($file, $operation, $polling));
    }
    /**
     * Asynchronously lock a file (internal generator function).
     *
     * @param string  $file      File to lock
     * @param integer $operation Locking mode
     * @param float  $polling   Polling interval
     *
     * @internal Generator function
     *
     * @return \Generator
     */
    public static function flockGenerator(string $file, int $operation, float $polling): \Generator
    {
        if (!yield exists($file)) {
            yield \touch($file);
            StatCache::clear($file);
        }
        $operation |= LOCK_NB;
        $res = \fopen($file, 'c');
        do {
            $result = \flock($res, $operation);
            if (!$result) {
                yield self::sleep($polling);
            }
        } while (!$result);

        return static function () use (&$res) {
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
     * @param int $time Number of seconds to sleep for
     *
     * @return Promise
     */
    public static function sleep(int $time): Promise
    {
        return new \Amp\Delayed($time * 1000);
    }

    /**
     * Asynchronously read line.
     *
     * @param string $prompt Prompt
     *
     * @return Promise
     */
    public static function readLine(string $prompt = ''): Promise
    {
        return self::call(Tools::readLineGenerator($prompt));
    }
    /**
     * Asynchronously read line (generator function).
     *
     * @param string $prompt Prompt
     *
     * @internal Generator function
     *
     * @return \Generator
     */
    public static function readLineGenerator(string $prompt = ''): \Generator
    {
        $stdin = getStdin();
        $stdout = getStdout();
        if ($prompt) {
            yield $stdout->write($prompt);
        }
        static $lines = [''];
        while (\count($lines) < 2 && ($chunk = yield $stdin->read()) !== null) {
            $chunk = \explode("\n", \str_replace(["\r", "\n\n"], "\n", $chunk));
            $lines[\count($lines) - 1] .= \array_shift($chunk);
            $lines = \array_merge($lines, $chunk);
        }
        return \array_shift($lines);
    }

    /**
     * Asynchronously write to stdout/browser.
     *
     * @param string $string Message to echo
     *
     * @return Promise
     */
    public static function echo(string $string): Promise
    {
        return getOutputBufferStream()->write($string);
    }
    /**
     * Check if is array or similar (traversable && countable && arrayAccess).
     *
     * @param arraylike $var Value to check
     *
     * @return boolean
     */
    public static function isArrayOrAlike($var): bool
    {
        return \is_array($var) ||
            ($var instanceof \ArrayAccess &&
            $var instanceof \Traversable &&
            $var instanceof \Countable);
    }

    /**
     * Convert to camelCase.
     *
     * @param string $input
     *
     * @return string
     */
    public static function fromSnakeCase(string $input): string
    {
        return \lcfirst(\str_replace('_', '', \ucwords($input, '_')));
    }
    /**
     * Convert to snake_case.
     *
     * @param string $input
     *
     * @return string
     */
    public static function fromCamelCase(string $input): string
    {
        \preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == \strtoupper($match) ? \strtolower($match) : \lcfirst($match);
        }

        return \implode('_', $ret);
    }

    /**
     * Create array.
     *
     * @param mixed ...$params Params
     *
     * @return array
     */
    public static function arr(...$params): array
    {
        return $params;
    }
    /**
     * base64URL decode.
     *
     * @param string $data Data to decode
     *
     * @return string
     */
    public static function base64urlDecode(string $data): string
    {
        return \base64_decode(\str_pad(\strtr($data, '-_', '+/'), \strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    /**
     * Base64URL encode.
     *
     * @param string $data Data to encode
     *
     * @return string
     */
    public static function base64urlEncode(string $data): string
    {
        return \rtrim(\strtr(\base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * null-byte RLE decode.
     *
     * @param string $string Data to decode
     *
     * @return string
     */
    public static function rleDecode(string $string): string
    {
        $new = '';
        $last = '';
        $null = \chr(0);
        foreach (\str_split($string) as $cur) {
            if ($last === $null) {
                $new .= \str_repeat($last, \ord($cur));
                $last = '';
            } else {
                $new .= $last;
                $last = $cur;
            }
        }
        $string = $new.$last;

        return $string;
    }

    /**
     * null-byte RLE encode.
     *
     * @param string $string Data to encode
     *
     * @return string
     */
    public static function rleEncode(string $string): string
    {
        $new = '';
        $count = 0;
        $null = \chr(0);
        foreach (\str_split($string) as $cur) {
            if ($cur === $null) {
                $count++;
            } else {
                if ($count > 0) {
                    $new .= $null.\chr($count);
                    $count = 0;
                }
                $new .= $cur;
            }
        }

        return $new;
    }

    /**
     * Get final element of array.
     *
     * @param array $what Array
     *
     * @return mixed
     */
    public static function end(array $what)
    {
        return \end($what);
    }

    /**
     * Escape string for markdown.
     *
     * @param string $hwat String to escape
     *
     * @return void
     */
    public static function markdownEscape(string $hwat): string
    {
        return \str_replace('_', '\\_', $hwat);
    }

    /**
     * Whether this is altervista.
     *
     * @return boolean
     */
    public static function isAltervista(): bool
    {
        return Magic::$altervista;
    }


    /**
     * Accesses a private variable from an object.
     *
     * @param object $obj Object
     * @param string $var Attribute name
     *
     * @return mixed
     * @access public
     */
    public static function getVar($obj, string $var)
    {
        $reflection = new \ReflectionClass(\get_class($obj));
        $prop = $reflection->getProperty($var);
        $prop->setAccessible(true);
        return $prop->getValue($obj);
    }
}
