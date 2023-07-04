<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;

/**
 * Represents a generic video.
 */
abstract class AbstractVideo extends Media
{
    /** Video duration in seconds */
    public readonly int $duration;
    /** Whether the video supports streaming */
    public readonly bool $supportsStreaming;
    /** Video width */
    public readonly int $width;
    /** Video height */
    public readonly int $height;
}
