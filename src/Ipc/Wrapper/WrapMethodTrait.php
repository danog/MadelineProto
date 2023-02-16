<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use danog\MadelineProto\Ipc\Wrapper;

/**
 * @internal
 */
trait WrapMethodTrait
{
    abstract public function __call($name, $args);
    public function wrap(...$args)
    {
        $new = Wrapper::create($args, $this->session->getIpcCallbackPath(), $this->logger);
        foreach ($args as &$arg) {
            $new->wrap($arg);
        }
        return $this->__call(__FUNCTION__, $new);
    }
}
