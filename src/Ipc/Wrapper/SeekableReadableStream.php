<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

/**
 * @internal
 */
final class SeekableReadableStream extends ReadableStream
{
    use SeekableTrait;
}
