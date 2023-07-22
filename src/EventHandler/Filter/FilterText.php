<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages with a specific content.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterText extends Filter
{
    private readonly string $content;
    public function __construct(
        string $content,
        private readonly bool $caseInsensitive = false
    ) {
        Assert::notEmpty($content);
        $this->content = $caseInsensitive ? \strtolower($content) : $content;
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && (
            $this->caseInsensitive
                ? \strtolower($update->message) === $this->content
                : $update->message === $this->content
        );
    }
}
