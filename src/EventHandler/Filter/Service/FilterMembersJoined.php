<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Media;

use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\MessageService;
use danog\MadelineProto\EventHandler\Service\DialogMembersJoined;
use danog\MadelineProto\EventHandler\Update;

/**
 * Filter that only matches service messages about joined users.
 */
final class FilterMembersJoined extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof MessageService && $update->serviceInfo instanceof DialogMembersJoined;
    }
}
