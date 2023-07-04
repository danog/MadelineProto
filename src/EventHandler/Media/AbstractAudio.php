<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;

/**
 * Represents a generic audio file.
 */
abstract class AbstractAudio extends Media
{
    public readonly int $duration;
}
