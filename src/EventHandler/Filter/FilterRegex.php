<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Query\ButtonQuery;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages or button queries matching the specified regex.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterRegex extends Filter
{
    /** @param non-empty-string $regex */
    public function __construct(private readonly string $regex)
    {
        \preg_match($regex, '');
        Assert::eq(\preg_last_error_msg(), 'No error');
    }
    public function apply(Update $update): bool
    {
        if ($update instanceof Message && \preg_match($this->regex, $update->message, $matches)) {
            /** @psalm-suppress InaccessibleProperty */
            $update->matches = $matches;
            return true;
        }
        if ($update instanceof ButtonQuery && \preg_match($this->regex, $update->data, $matches)) {
            /** @psalm-suppress InaccessibleProperty */
            $update->matches = $matches;
            return true;
        }
        return false;
    }
}
