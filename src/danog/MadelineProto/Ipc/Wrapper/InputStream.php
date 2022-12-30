<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\ByteStream\InputStream as AmpInputStream;
use Amp\ByteStream\PendingReadError;
use Amp\Future;
use danog\MadelineProto\Tools;

class InputStream extends Obj implements AmpInputStream
{
    /**
     * Reads data from the stream.
     *
     * @return Promise Resolves with a string when new data is available or `null` if the stream has closed.
     * @psalm-return Promise<string|null>
     * @throws PendingReadError Thrown if another read operation is still pending.
     */
    public function read(): Future
    {
        return Tools::call($this->__call('read'));
    }
}
