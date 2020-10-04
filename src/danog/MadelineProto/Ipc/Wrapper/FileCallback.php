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
     * @param float $percent Percent
     * @param float $speed   Speed in mbps
     * @param float $time    Time
     *
     * @psalm-suppress MethodSignatureMismatch
     *
     * @return mixed
     */
    public function __invoke($percent, $speed, $time)
    {
        return $this->__call('__invoke', [$percent, $speed, $time]);
    }
}
