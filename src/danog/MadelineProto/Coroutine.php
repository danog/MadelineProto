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
use JsonSerializable;
use ReflectionGenerator;

/**
 * Creates a promise from a generator function yielding promises.
 *
 * When a promise is yielded, execution of the generator is interrupted until the promise is resolved. A success
 * value is sent into the generator, while a failure reason is thrown into the generator. Using a coroutine,
 * asynchronous code can be written without callbacks and be structured like synchronous code.
 */
final class Coroutine implements Promise, \ArrayAccess, JsonSerializable
{
    use Internal\Placeholder {
        fail as internalFail;
    }
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
    /** @var ?self Reference to coroutine that started this coroutine */
    private $parentCoroutine;
    /**
     * @param \Generator $generator
     */
    public function __construct(\Generator $generator)
    {
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
            if ($yielded instanceof self) {
                $yielded->parentCoroutine = $this;
            }
        } catch (\Throwable $exception) {
            $this->fail($exception);
            return;
        }
        /*
         * @param \Throwable|null $exception Exception to be thrown into the generator.
         * @param mixed           $value Value to be sent into the generator.
         */
        $this->onResolve = function ($exception, $value): void {
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
                        $yielded = $this->throw($this->exception);
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
                    if ($yielded instanceof self) {
                        $yielded->parentCoroutine = $this;
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
     * Throw exception into the generator.
     *
     * @param \Throwable $reason Exception
     *
     * @internal
     *
     * @return mixed
     */
    public function throw(\Throwable $reason)
    {
        if (\method_exists($reason, 'updateTLTrace')) {
            $reason->updateTLTrace($this->getTrace());
        }
        return $this->generator->throw($reason);
    }
    /**
     * @param \Throwable $reason Failure reason.
     *
     * @return void
     */
    public function fail(\Throwable $reason): void
    {
        $this->resolve(new Failure($reason));
    }
    public function offsetExists(mixed $offset): bool
    {
        throw new Exception('Not supported!');
    }
    /**
     * Get data at an array offset asynchronously.
     *
     * @param mixed $offset Offset
     *
     * @return Promise
     */
    public function offsetGet(mixed $offset): Promise
    {
        return Tools::call((function () use ($offset): \Generator {
            $result = yield $this;
            return $result[$offset];
        })());
    }
    #[\ReturnTypeWillChange]
    public function offsetSet(mixed $offset, mixed $value): Promise
    {
        return Tools::call((function () use ($offset, $value): \Generator {
            $result = yield $this;
            if ($offset === null) {
                return $result[] = $value;
            }
            return $result[$offset] = $value;
        })());
    }
    #[\ReturnTypeWillChange]
    public function offsetUnset(mixed $offset): Promise
    {
        return Tools::call((function () use ($offset): \Generator {
            $result = yield $this;
            unset($result[$offset]);
        })());
    }
    /**
     * Get an attribute asynchronously.
     *
     * @param string $offset Offset
     *
     * @return Promise
     */
    public function __get(string $offset)
    {
        return Tools::call((function () use ($offset): \Generator {
            $result = yield $this;
            return $result->{$offset};
        })());
    }
    public function __call(string $name, array $arguments)
    {
        return Tools::call((function () use ($name, $arguments): \Generator {
            $result = yield $this;
            return $result->{$name}(...$arguments);
        })());
    }
    /**
     * Get current stack trace for running coroutine.
     *
     * @return array
     */
    public function getTrace(): array
    {
        $frames = [];
        try {
            $reflector = new ReflectionGenerator($this->generator);
            $frames = $reflector->getTrace();
            $frames[] = \array_merge($this->parentCoroutine ? $this->parentCoroutine->getFrame() : [], ['function' => $reflector->getFunction()->getName(), 'args' => []]);
        } catch (\Throwable $e) {
        }
        if ($this->parentCoroutine) {
            $frames = \array_merge($frames, $this->parentCoroutine->getTrace());
        }
        return $frames;
    }
    /**
     * Get current execution frame.
     *
     * @return array
     */
    public function getFrame(): array
    {
        try {
            $reflector = new ReflectionGenerator($this->generator);
            return ['file' => $reflector->getExecutingFile(), 'line' => $reflector->getExecutingLine()];
        } catch (\Throwable $e) {
        }
        return [];
    }

    private const WARNING = 'To obtain a result from a Coroutine object, yield the result or disable async (not recommended). See https://docs.madelineproto.xyz/docs/ASYNC.html for more information on async.';
    public function __debugInfo()
    {
        return [self::WARNING];
    }

    /**
     * Obtain.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return self::WARNING;
    }
}
