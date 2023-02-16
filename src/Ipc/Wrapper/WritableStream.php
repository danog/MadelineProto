<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\ByteStream\WritableStream as AmpWritableStream;

/**
 * @internal
 */
class WritableStream extends Obj implements AmpWritableStream
{
    use ClosableTrait;

    public function write(string $data): void
    {
        $this->__call('write', [$data]);
    }
    public function isWritable(): bool
    {
        return $this->__call('isWritable');
    }

    public function end(string $finalData = ""): void
    {
        $this->__call('end', [$finalData]);
    }
}
