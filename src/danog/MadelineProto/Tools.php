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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Failure;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use function Amp\Promise\all;
use function Amp\Promise\any;
use function Amp\Promise\first;
use function Amp\Promise\some;
use function Amp\Promise\timeout;
use function Amp\Promise\wait;
use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\getOutputBufferStream;

/**
 * Some tools.
 */
trait Tools
{
    public static function gen_vector_hash($ints)
    {
        //sort($ints, SORT_NUMERIC);
        if (\danog\MadelineProto\Magic::$bigint) {
            $hash = new \phpseclib\Math\BigInteger(0);
            foreach ($ints as $int) {
                $hash = $hash->multiply(\danog\MadelineProto\Magic::$twozerotwosixone)->add(\danog\MadelineProto\Magic::$zeroeight)->add(new \phpseclib\Math\BigInteger($int))->divide(\danog\MadelineProto\Magic::$zeroeight)[1];
            }
            $hash = self::unpack_signed_int(strrev(str_pad($hash->toBytes(), 4, "\0", STR_PAD_LEFT)));
        } else {
            $hash = 0;
            foreach ($ints as $int) {
                $hash = ((($hash * 20261) & 0x7FFFFFFF) + $int) & 0x7FFFFFFF;
            }
        }

        return $hash;
    }

    public static function random_int($modulus = false)
    {
        if ($modulus === false) {
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
            $number = self::unpack_signed_int(self::random(4));
        } else {
            $number = self::unpack_signed_long(self::random(8));
        }

        return ($number & PHP_INT_MAX) % $modulus;
    }

    public static function random($length)
    {
        return $length === 0 ? '' : \phpseclib\Crypt\Random::string($length);
    }

    /**
     * posmod(numeric,numeric) : numeric
     * Works just like the % (modulus) operator, only returns always a postive number.
     */
    public static function posmod($a, $b)
    {
        $resto = $a % $b;

        return $resto < 0 ? $resto + abs($b) : $resto;
    }

    public static function unpack_signed_int($value)
    {
        if (strlen($value) !== 4) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_4']);
        }

        return unpack('l', \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public static function unpack_signed_long($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return unpack('q', \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public static function pack_signed_int($value)
    {
        if ($value > 2147483647) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_2147483647'], $value));
        }
        if ($value < -2147483648) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_2147483648'], $value));
        }
        $res = pack('l', $value);

        return \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($res) : $res;
    }

    public static function pack_signed_long($value)
    {
        if ($value > 9223372036854775807) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_9223372036854775807'], $value));
        }
        if ($value < -9.223372036854776E+18) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_9223372036854775808'], $value));
        }
        $res = \danog\MadelineProto\Magic::$bigint ? self::pack_signed_int($value)."\0\0\0\0" : (\danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev(pack('q', $value)) : pack('q', $value));

        return $res;
    }

    public static function pack_unsigned_int($value)
    {
        if ($value > 4294967295) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_4294967296'], $value));
        }
        if ($value < 0) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_0'], $value));
        }

        return pack('V', $value);
    }

    public static function pack_double($value)
    {
        $res = pack('d', $value);
        if (strlen($res) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['encode_double_error']);
        }

        return \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($res) : $res;
    }

    public static function unpack_double($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return unpack('d', \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public static function wait($promise)
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
        } while (!$resolved);

        if ($exception) {
            throw $exception;
        }

        return $value;
    }

    public static function all($promises)
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return all($promises);
    }

    public static function any($promises)
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return any($promises);
    }

    public static function some($promises)
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return some($promises);
    }

    public static function first($promises)
    {
        foreach ($promises as &$promise) {
            $promise = self::call($promise);
        }

        return first($promises);
    }

    public static function timeout($promise, $timeout)
    {
        return timeout(self::call($promise), $timeout);
    }

    public static function call($promise)
    {
        if ($promise instanceof \Generator) {
            $promise = new Coroutine($promise);
        } elseif (!($promise instanceof Promise)) {
            return new Success($promise);
        }

        return $promise;
    }

    public static function callFork($promise, $actual = null, $file = '')
    {
        if ($actual) {
            $promise = $actual;
        } else {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $file = '';
            if (isset($trace['file'])) {
                $file .= basename($trace['file'], '.php');
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

    public static function callForkDefer($promise)
    {
        Loop::defer([__CLASS__, 'callFork'], $promise);
    }

    public static function rethrow($e, $file = '')
    {
        $zis = isset($this) ? $this : null;
        $logger = isset($zis->logger) ? $zis->logger : Logger::$default;
        if ($file) {
            $file = " started @ $file";
        }
        if ($logger) $logger->logger("Got the following exception within a forked strand$file, trying to rethrow");
        if ($e->getMessage() === "Cannot get return value of a generator that hasn't returned") {
            $logger->logger("Well you know, this might actually not be the actual exception, scroll up in the logs to see the actual exception");
            if (!$zis || !$zis->destructing) Promise\rethrow(new Failure($e));
        } else {
            if ($logger) $logger->logger($e);
            Promise\rethrow(new Failure($e));
        }
    }

    public static function after($a, $b)
    {
        $a = self::call($a());
        $deferred = new Deferred();
        $a->onResolve(static function ($e, $res) use ($b, $deferred) {
            if ($e) {
                if (isset($this)) {
                    $this->rethrow($e, $file);
                } else {
                    self::rethrow($e, $file);
                }
                return;
            }
            $b = self::call($b());
            $b->onResolve(static function ($e, $res) use ($deferred) {
                if ($e) {
                    if (isset($this)) {
                        $this->rethrow($e, $file);
                    } else {
                        self::rethrow($e, $file);
                    }
                    return;
                }
                $deferred->resolve($res);
            });
        });

        return $deferred->promise();
    }

    public static function sleep($time)
    {
        return new \Amp\Delayed($time * 1000);
    }
    public static function readLine($prompt = '')
    {
        return self::call(Tools::readLineAsync($prompt));
    }
    public static function readLineAsync($prompt = '')
    {
        $stdin = getStdin();
        $stdout = getStdout();
        if ($prompt) {
            yield $stdout->write($prompt);
        }
        static $lines = [''];
        while (count($lines) < 2 && ($chunk = yield $stdin->read()) !== null) {
            $chunk = explode("\n", str_replace(["\r", "\n\n"], "\n", $chunk));
            $lines[count($lines) - 1] .= array_shift($chunk);
            $lines = array_merge($lines, $chunk);
        }
        return array_shift($lines);
    }

    public static function echo($string)
    {
        return getOutputBufferStream()->write($string);
    }
    public static function is_array_or_alike($var)
    {
        return is_array($var) ||
            ($var instanceof ArrayAccess &&
            $var instanceof Traversable &&
            $var instanceof Countable);
    }
}
