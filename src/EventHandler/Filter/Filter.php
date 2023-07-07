<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Update;

#[Attribute(Attribute::TARGET_METHOD)]
abstract class Filter
{
    abstract public function apply(Update $update): bool;
    public function initialize(EventHandler $API): Filter
    {
        return $this;
    }
}
