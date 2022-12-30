<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Wrapper;

class SeekableOutputStream extends OutputStream
{
    use SeekableTrait;
}
