<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\VoIP;
use danog\MadelineProto\VoIP\CallState;

/**
 * Allow only incoming messages.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterIncoming extends Filter
{
    public function apply(Update $update): bool
    {
        return ($update instanceof AbstractMessage && !$update->out)
            || ($update instanceof VoIP && $update->getCallState() === CallState::INCOMING);
    }
}
