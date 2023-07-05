<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Media;

use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Media\Audio;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Filter that only matches audio files.
 */
final class FilterAudio extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->media instanceof Audio;
    }
}
