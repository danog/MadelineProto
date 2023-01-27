<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

final class SeekableWritableStream extends WritableStream
{
    use SeekableTrait;
}
