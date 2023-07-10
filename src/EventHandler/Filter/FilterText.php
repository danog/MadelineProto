<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only messages with a specific content.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterText extends Filter
{
    public function __construct(
        private readonly string $content
    ) {
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->message === $this->content;
    }
}
