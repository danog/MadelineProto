<?php

namespace danog\MadelineProto\Ipc\Wrapper;

use danog\MadelineProto\Ipc\Wrapper;

/**
 * Generic callback wrapper object.
 */
class Obj
{
    /**
     * Method list.
     *
     * @var array<string, int>
     */
    private array $methods = [];
    /**
     * Wrapper.
     */
    private Wrapper $wrapper;
    /**
     * Constructor.
     *
     * @param Wrapper $wrapper
     * @param array $methods
     */
    public function __construct(Wrapper $wrapper, array $methods)
    {
        $this->wrapper = $wrapper;
        $this->methods = $methods;
    }
    /**
     * Call method.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return \Generator<mixed, mixed, mixed, mixed>
     */
    public function __call(string $name, array $arguments = []): \Generator
    {
        return $this->wrapper->__call($this->methods[$name], $arguments);
    }
}
