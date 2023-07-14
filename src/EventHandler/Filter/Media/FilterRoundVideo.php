<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Media;

use Attribute;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Media\RoundVideo;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only round videos.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterRoundVideo extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->media instanceof RoundVideo;
    }
}
