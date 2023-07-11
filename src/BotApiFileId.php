<?php

declare(strict_types=1);

namespace danog\MadelineProto;

/**
 * Indicates a bot API file ID to upload.
 */
final class BotApiFileId
{
    public function __construct(
        public readonly string $fileId
    ) {
    }
}
