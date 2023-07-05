<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Filter only messages matching the specified regex.
 */
final class FilterRegex extends Filter
{
    public function __construct(private readonly string $regex)
    {
        \preg_match($regex, '');
        Assert::eq(\preg_last_error_msg(), 'No error');
    }
    public function apply(Update $update): bool
    {
        if ($update instanceof Message && \preg_match($this->regex, $update->message, $matches)) {
            $update->matches = $matches;
            return true;
        }
        return false;
    }
}
