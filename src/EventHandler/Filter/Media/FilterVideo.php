<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Media;

use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow that only matches videos.
 */
final class FilterVideo extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->media instanceof Video;
    }
}
