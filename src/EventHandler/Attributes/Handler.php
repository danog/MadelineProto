<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Attributes;

use Attribute;

/** Attribute that marks a handler method. */
#[Attribute(Attribute::TARGET_METHOD)]
final class Handler
{
}
