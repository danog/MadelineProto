<?php

declare(strict_types=1);

namespace danog\MadelineProto;

/**
 * Indicates a local file to upload.
 */
final class LocalFile
{
    public function __construct(
        public readonly string $file
    ) {
    }
}
