<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\MTProto;

/**
 * Represents a generic video.
 */
abstract class AbstractVideo extends Media
{
    /** Video duration in seconds */
    public readonly float $duration;
    /** Whether the video supports streaming */
    public readonly bool $supportsStreaming;
    /** Video width */
    public readonly int $width;
    /** Video height */
    public readonly int $height;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $attribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $protected);
        $this->duration = $attribute['duration'];
        $this->supportsStreaming = $attribute['supports_streaming'];
        $this->width = $attribute['w'];
        $this->height = $attribute['h'];
    }
}
