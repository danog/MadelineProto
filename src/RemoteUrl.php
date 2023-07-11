<?php

declare(strict_types=1);

namespace danog\MadelineProto;

/**
 * Indicates a remote URL to upload.
 */
final class RemoteUrl
{
    public function __construct(
        public readonly string $url
    ) {
    }
}
