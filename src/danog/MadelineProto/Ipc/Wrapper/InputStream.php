<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\ByteStream\ReadableStream as AmpReadableStream;
use Amp\Cancellation;
use Webmozart\Assert\Assert;

class ReadableStream extends Obj implements AmpReadableStream
{
    use ClosableTrait;

    public function read(?Cancellation $cancellation = null): ?string
    {
        Assert::null($cancellation);
        return $this->__call('read');
    }

    /**
     * @return bool A stream may become unreadable if the underlying source is closed or lost.
     */
    public function isReadable(): bool
    {
        return $this->__call('isReadable');
    }
}