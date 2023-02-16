<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use danog\MadelineProto\Ipc\Wrapper;

/**
 * Generic callback wrapper object.
 *
 * @internal
 */
abstract class Obj
{
    /**
     * Constructor.
     *
     * @param array<string, int> $methods
     */
    public function __construct(private Wrapper $wrapper, private array $methods)
    {
    }
    /**
     * Call method.
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        return $this->wrapper->__call($this->methods[$name], $arguments);
    }
}
