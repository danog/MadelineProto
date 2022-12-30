<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

class SeekableInputStream extends InputStream
{
    use SeekableTrait;
}
