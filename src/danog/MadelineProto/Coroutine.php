<?php
/**
 * Coroutine (modified version of AMP Coroutine).
 *
 * The MIT License (MIT)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @copyright 2015-2018 amphp
 * @copyright 2016 PHP Asynchronous Interoperability Group
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace danog\MadelineProto;

use Amp\Failure;
use Amp\Internal;
use Amp\Promise;
use Amp\Success;

/**
 * Creates a promise from a generator function yielding promises.
 *
 * When a promise is yielded, execution of the generator is interrupted until the promise is resolved. A success
 * value is sent into the generator, while a failure reason is thrown into the generator. Using a coroutine,
 * asynchronous code can be written without callbacks and be structured like synchronous code.
 */
final class Coroutine implements Promise, \ArrayAccess
{
    use Internal\Placeholder;
    /** @var \Generator */
    private $generator;
    /** @var callable(\Throwable|null $exception, mixed $value): void */
    private $onResolve;
    /** @var bool Used to control iterative coroutine continuation. */
    private $immediate = true;
    /** @var \Throwable|null Promise failure reason when executing next coroutine step, null at all other times. */
    private $exception;
    /** @var mixed Promise success value when executing next coroutine step, null at all other times. */
    private $value;

    private $frames = [];

    /**
     * @param \Generator $generator
     */
    public function __construct(\Generator $generator)
    {
        /*
        $this->generator = new class($generator) {
        private $s = '';
        private $g;
        private $trace;
        public function __construct($g) {
        $this->g = $g;
        $this->s .= spl_object_hash($this).', ';
        }
        public function __call($a, $args) {
        $this->s .= "$a, ";
        try {
        $res = $this->g->{$a}(...$args);
        if (is_array($res) && isset($res['my_trace'])) {
        $this->trace = $res;
        $res = $this->g->{$a}(...$args);
        }
        return $res;
        } catch (\Throwable $e) {
        $this->s .= $e->getMessage();
        $this->s .= ', ';
        var_dump($this->s, $this->trace);
        throw $e;
        }
        }
        //public function __destruct() { var_dump($this->s); }
        };*/
        $this->generator = $generator;

        try {
            $yielded = $this->generator->current();
            while (!$yielded instanceof Promise) {
                if ($yielded instanceof \YieldReturnValue) {
                    $this->resolve($yielded->getReturn());
                    $this->generator->next();

                    return;
                }
                if (!$this->generator->valid()) {
                    if (PHP_MAJOR_VERSION >= 7) {
                        $this->resolve($this->generator->getReturn());
                    } else {
                        $this->resolve(null);
                    }

                    return;
                }
                if ($yielded instanceof \Generator) {
                    $yielded = new self($yielded);
                } else {
                    $yielded = $this->generator->send($yielded);
                }
            }
        } catch (\Throwable $exception) {
            $this->fail($exception);

            return;
        }
        /*
         * @param \Throwable|null $exception Exception to be thrown into the generator.
         * @param mixed           $value Value to be sent into the generator.
         */
        $this->onResolve = function ($exception, $value) {
            $this->exception = $exception;
            $this->value = $value;
            if (!$this->immediate) {
                $this->immediate = true;

                return;
            }

            try {
                do {
                    if ($this->exception) {
                        // Throw exception at current execution point.
                        $yielded = $this->generator->throw($this->exception);
                    } else {
                        // Send the new value and execute to next yield statement.
                        $yielded = $this->generator->send($this->value);
                    }
                    while (!$yielded instanceof Promise) {
                        if ($yielded instanceof \YieldReturnValue) {
                            $this->resolve($yielded->getReturn());
                            $this->onResolve = null;
                            $this->generator->next();

                            return;
                        }

                        if (!$this->generator->valid()) {
                            if (PHP_MAJOR_VERSION >= 7) {
                                $this->resolve($this->generator->getReturn());
                            } else {
                                $this->resolve(null);
                            }
                            $this->onResolve = null;

                            return;
                        }
                        if ($yielded instanceof \Generator) {
                            $yielded = new self($yielded);
                        } else {
                            $yielded = $this->generator->send($yielded);
                        }
                    }
                    $this->immediate = false;
                    $yielded->onResolve($this->onResolve);
                } while ($this->immediate);
                $this->immediate = true;
            } catch (\Throwable $exception) {
                $this->fail($exception);
                $this->onResolve = null;
            } finally {
                $this->exception = null;
                $this->value = null;
            }
        };
        $yielded->onResolve($this->onResolve);
    }

    public function offsetExists($offset): bool
    {
        throw new Exception('Not supported!');
    }
    public function offsetGet($offset)
    {
        return Tools::call((function () use ($offset) {
            return (yield $this)[$offset];
        })());
    }
    public function offsetSet($offset, $value)
    {
        return Tools::call((function () use ($offset, $value) {
            $result = yield $this;
            return $result[$offset] = $value;
        })());
    }
    public function offsetUnset($offset)
    {
        return Tools::call((function () use ($offset) {
            $result = yield $this;
            unset($result[$offset]);
        })());
    }
}
