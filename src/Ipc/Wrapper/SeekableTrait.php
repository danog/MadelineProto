<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

/**
 * @internal
 */
trait SeekableTrait
{
    /**
    * Set the handle's internal pointer position.
    *
    * $whence values:
    *
    * SEEK_SET - Set position equal to offset bytes.
    * SEEK_CUR - Set position to current location plus offset.
    * SEEK_END - Set position to end-of-file plus offset.
    */
    public function seek(int $position, int $whence = \SEEK_SET): int
    {
        return $this->__call('seek', [$position, $whence]);
    }
}
