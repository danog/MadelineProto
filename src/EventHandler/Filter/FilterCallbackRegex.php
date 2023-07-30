<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\CallbackQuery;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

#[Attribute(Attribute::TARGET_METHOD)]
class FilterCallbackRegex extends Filter
{
    public function __construct(private readonly string $regex)
    {
        \preg_match($regex, '');
        Assert::eq(\preg_last_error_msg(), 'No error');
    }

    public function apply(Update $update): bool
    {
        if ($update instanceof CallbackQuery && \preg_match($this->regex, $update->data, $matches)) {
            $update->matches = $matches;
            return true;
        }
        return false;
    }
}
