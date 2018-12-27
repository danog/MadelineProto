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

use React\Promise\PromiseInterface as ReactPromise;

/**
 * Creates a promise from a generator function yielding promises.
 *
 * When a promise is yielded, execution of the generator is interrupted until the promise is resolved. A success
 * value is sent into the generator, while a failure reason is thrown into the generator. Using a coroutine,
 * asynchronous code can be written without callbacks and be structured like synchronous code.
 */
final class Coroutine implements Promise
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

    /**
     * @param \Generator $generator
     */
    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;

        try {
            $yielded = $this->generator->current();
            if (!$yielded instanceof Promise) {
                if ($yielded instanceof \YieldReturnValue) {
                    $this->resolve($yielded->getReturn());
                    $this->generator->next();
                    return;
                }
                if (!$this->generator->valid()) {
                    if (method_exists($this->generator, 'getReturn')) {
                        $this->resolve($this->generator->getReturn());
                    } else {
                        $this->resolve(null);
                    }


                    return;
                }
                $yielded = $this->transform($yielded);
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
                    if (!$yielded instanceof Promise) {
                        if ($yielded instanceof \YieldReturnValue) {
                            $this->resolve($yielded->getReturn());
                            $this->onResolve = null;
                            $this->generator->next();

                            return;
                        }

                        if (!$this->generator->valid()) {
                            if (method_exists($this->generator, 'getReturn')) {
                                $this->resolve($this->generator->getReturn());
                            } else {
                                $this->resolve(null);
                            }
                            $this->onResolve = null;

                            return;
                        }
                        $yielded = $this->transform($yielded);
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

    /**
     * Attempts to transform the non-promise yielded from the generator into a promise, otherwise returns an instance
     * `Amp\Failure` failed with an instance of `Amp\InvalidYieldError`.
     *
     * @param mixed $yielded Non-promise yielded from generator.
     *
     * @return \Amp\Promise
     */
    private function transform($yielded): Promise
    {
        try {
            if (\is_array($yielded)) {
                foreach ($yielded as &$val) {
                    if ($val instanceof \Generator) {
                        $val = new self($val);
                    }
                }

                return Promise\all($yielded);
            }
            if ($yielded instanceof \Generator) {
                return new self($yielded);
            }

            if ($yielded instanceof ReactPromise) {
                return Promise\adapt($yielded);
            }
            // No match, continue to returning Failure below.
        } catch (\Throwable $exception) {
            // Conversion to promise failed, fall-through to returning Failure below.
        }

        return $yielded instanceof \Throwable || $yielded instanceof \Exception ? new Failure($yielded) : new Success($yielded);
    }
}
