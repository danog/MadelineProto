<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\Future;
use danog\MadelineProto\Tools;

use const SEEK_SET;

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
    *
    * @return Promise<int> New offset position.
    */
    public function seek(int $position, int $whence = SEEK_SET): Future
    {
        return Tools::call($this->__call('seek', [$position, $whence]));
    }
}
