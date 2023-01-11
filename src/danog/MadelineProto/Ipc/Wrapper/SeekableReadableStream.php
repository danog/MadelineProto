<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

class SeekableReadableStream extends ReadableStream
{
    use SeekableTrait;
}
