<?php

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\ByteStream\InputStream as AmpInputStream;
use Amp\ByteStream\PendingReadError;
use Amp\Promise;
use danog\MadelineProto\Tools;

class InputStream extends Obj implements AmpInputStream
{
    /**
     * Reads data from the stream.
     *
     * @return Promise Resolves with a string when new data is available or `null` if the stream has closed.
     *
     * @psalm-return Promise<string|null>
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     */
    public function read(): Promise
    {
        return Tools::call($this->__call('read'));
    }
}
