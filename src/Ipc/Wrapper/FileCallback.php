<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use danog\MadelineProto\FileCallbackInterface;

/**
 * @internal
 */
final class FileCallback extends Obj implements FileCallbackInterface
{
    /**
     * Get file.
     */
    public function getFile(): mixed
    {
        return $this->__call('getFile');
    }
    /**
     * Invoke callback.
     *
     * @param float $percent Percent
     * @param float $speed   Speed in mbps
     * @param float $time    Time
     */
    public function __invoke(float $percent, float $speed, float $time)
    {
        return $this->__call('__invoke', [$percent, $speed, $time]);
    }
}
