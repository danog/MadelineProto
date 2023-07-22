<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages with a specific case-insensitive content.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterTextCaseInsensitive extends Filter
{
    public function __construct(
        private readonly string $content
    ) {
        Assert::notEmpty($content);
        Assert::lower($content);
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && \strtolower($update->message) === $this->content;
    }
}
