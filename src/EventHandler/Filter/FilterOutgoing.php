<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only outgoing messages.
 */
final class FilterOutgoing extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof AbstractMessage && $update->out;
    }
}
