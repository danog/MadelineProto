<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Attributes;

use Attribute;

/** Attribute that enables periodic execution of a certain method. */
#[Attribute(Attribute::TARGET_METHOD)]
final class Cron
{
    public function __construct(
        public readonly float $period
    ) {
    }
}
