<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter\Media;

use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\MessageService;
use danog\MadelineProto\EventHandler\Service\DialogMemberLeft;
use danog\MadelineProto\EventHandler\Update;

/**
 * Filter that only matches service messages about left users.
 */
final class FilterMemberLeft extends Filter
{
    public function apply(Update $update): bool
    {
        return $update instanceof MessageService && $update->serviceInfo instanceof DialogMemberLeft;
    }
}
