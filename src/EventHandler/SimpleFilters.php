<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\EventHandler\SimpleFilter\Outgoing;

/**
 * @internal An internal interface used to avoid type errors when using simple filters.
 */
interface SimpleFilters extends Incoming, Outgoing
{
}
