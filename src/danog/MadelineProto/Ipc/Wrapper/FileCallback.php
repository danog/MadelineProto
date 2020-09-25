<?php

namespace danog\MadelineProto\Ipc\Wrapper;

use danog\MadelineProto\FileCallbackInterface;

class FileCallback extends Obj implements FileCallbackInterface
{
    /**
     * Get file.
     *
     * @return mixed
     */
    public function getFile()
    {
        return $this->__call('getFile');
    }
    /**
     * Invoke callback.
     *
     * @param int $percent Percent
     * @param int $speed   Speed in mbps
     * @param int $time    Time
     *
     * @return mixed
     */
    public function __invoke(...$args)
    {
        return $this->__call('__invoke', $args);
    }
}
