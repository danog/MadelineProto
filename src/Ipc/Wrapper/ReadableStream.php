<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\ByteStream\ReadableStream as AmpReadableStream;
use Amp\ByteStream\ReadableStreamIteratorAggregate;
use Amp\Cancellation;
use IteratorAggregate;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
class ReadableStream extends Obj implements AmpReadableStream, IteratorAggregate
{
    use ClosableTrait;
    use ReadableStreamIteratorAggregate;

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
