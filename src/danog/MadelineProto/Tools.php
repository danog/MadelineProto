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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use function Amp\Promise\wait;

/**
 * Some tools.
 */
trait Tools
{
    public function gen_vector_hash($ints)
    {
        //sort($ints, SORT_NUMERIC);
        if (\danog\MadelineProto\Magic::$bigint) {
            $hash = new \phpseclib\Math\BigInteger(0);
            foreach ($ints as $int) {
                $hash = $hash->multiply(\danog\MadelineProto\Magic::$twozerotwosixone)->add(\danog\MadelineProto\Magic::$zeroeight)->add(new \phpseclib\Math\BigInteger($int))->divide(\danog\MadelineProto\Magic::$zeroeight)[1];
            }
            $hash = $this->unpack_signed_int(strrev(str_pad($hash->toBytes(), 4, "\0", STR_PAD_LEFT)));
        } else {
            $hash = 0;
            foreach ($ints as $int) {
                $hash = ((($hash * 20261) & 0x7FFFFFFF) + $int) & 0x7FFFFFFF;
            }
        }

        return $hash;
    }

    public function random_int($modulus = false)
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
            $number = $this->unpack_signed_int($this->random(4));
        } else {
            $number = $this->unpack_signed_long($this->random(8));
        }

        return ($number & PHP_INT_MAX) % $modulus;
    }

    public function random($length)
    {
        return $length === 0 ? '' : \phpseclib\Crypt\Random::string($length);
    }

    /**
     * posmod(numeric,numeric) : numeric
     * Works just like the % (modulus) operator, only returns always a postive number.
     */
    public function posmod($a, $b)
    {
        $resto = $a % $b;

        return $resto < 0 ? $resto + abs($b) : $resto;
    }

    public function array_cast_recursive($array, $force = false)
    {
        if (!\danog\MadelineProto\Magic::$has_thread && !$force) {
            return $array;
        }
        if (is_array($array)) {
            if (!is_array($array)) {
                $array = (array) $array;
            }
            foreach ($array as $key => $value) {
                $array[$key] = $this->array_cast_recursive($value, $force);
            }
        }

        return $array;
    }

    public function unpack_signed_int($value)
    {
        if (strlen($value) !== 4) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_4']);
        }

        return unpack('l', \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public function unpack_signed_long($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return unpack('q', \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public function pack_signed_int($value)
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

    public function pack_signed_long($value)
    {
        if ($value > 9223372036854775807) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_9223372036854775807'], $value));
        }
        if ($value < -9.223372036854776E+18) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_9223372036854775808'], $value));
        }
        $res = \danog\MadelineProto\Magic::$bigint ? $this->pack_signed_int($value)."\0\0\0\0" : (\danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev(pack('q', $value)) : pack('q', $value));

        return $res;
    }

    public function pack_unsigned_int($value)
    {
        if ($value > 4294967295) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_4294967296'], $value));
        }
        if ($value < 0) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_0'], $value));
        }

        return pack('V', $value);
    }

    public function pack_double($value)
    {
        $res = pack('d', $value);
        if (strlen($res) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['encode_double_error']);
        }

        return \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($res) : $res;
    }

    public function unpack_double($value)
    {
        if (strlen($value) !== 8) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_8']);
        }

        return unpack('d', \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public function infloop()
    {
        while (true) {
            Loop::loop();
        }
    }

    public function wait($promise)
    {
        if ($promise instanceof \Generator) {
            $promise = new Coroutine($promise);
        } elseif (!($promise instanceof Promise)) {
            return $promise;
        }
        do {
            try {
                return wait($promise);
            } catch (\Throwable $e) {
                if ($e->getMessage() !== 'Loop stopped without resolving the promise') {
                    //$this->logger->logger("AN EXCEPTION SURFACED " . $e, \danog\MadelineProto\Logger::ERROR);
                    throw $e;
                }
            }
        } while (true);
    }

    public function call($promise)
    {
        if ($promise instanceof \Generator) {
            $promise = new Coroutine($promise);
        } elseif (!($promise instanceof Promise)) {
            return new Success($promise);
        }

        return $promise;
    }

    public function after($a, $b)
    {
        $a = $this->call($a);
        $deferred = new Deferred();
        $a->onResolve(function ($e, $res) use ($b, $deferred) {
            if ($e) {
                throw $e;
            }
            $b = $this->call($b());
            $b->onResolve(static function ($e, $res) use ($deferred) {
                if ($e) {
                    throw $e;
                }
                $deferred->resolve($res);
            });
        });

        return $deferred->promise();
    }

    public function sleep_async($time)
    {
        return new \Amp\Delayed($time * 1000);
    }
}
