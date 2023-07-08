<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use Attribute;
use danog\MadelineProto\EventHandler\Update;

/** Attribute that marks a handler method. */
#[Attribute(Attribute::TARGET_METHOD)]
final class Handler
{
}
