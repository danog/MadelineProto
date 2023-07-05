<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\MTProto;

/**
 * Represents a generic audio file.
 */
abstract class AbstractAudio extends Media
{
    /** Audio duration in seconds */
    public readonly int $duration;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $audioAttribute
    ) {
        parent::__construct($API, $rawMedia);
        $this->duration = $audioAttribute['duration'];
    }
}
