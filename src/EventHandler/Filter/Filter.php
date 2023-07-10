<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Filter\Combinator\FiltersAnd;
use danog\MadelineProto\EventHandler\Filter\Combinator\FiltersOr;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\EventHandler\SimpleFilter\Outgoing;
use danog\MadelineProto\EventHandler\Update;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

abstract class Filter
{
    abstract public function apply(Update $update): bool;
    /** Run some initialization logic, optionally returning a new filter to replace the current one. */
    public function initialize(EventHandler $API): ?Filter
    {
        return null;
    }

    public static function fromReflectionType(ReflectionType $type): Filter
    {
        return match (true) {
            $type instanceof ReflectionUnionType => new FiltersOr(
                ...\array_map(
                    self::fromReflectionType(...),
                    $type->getTypes()
                )
            ),
            $type instanceof ReflectionIntersectionType => new FiltersAnd(
                ...\array_map(
                    self::fromReflectionType(...),
                    $type->getTypes()
                )
            ),
            $type instanceof ReflectionNamedType => match ($type->getName()) {
                Incoming::class => new FilterIncoming,
                Outgoing::class => new FilterOutgoing,
                Update::class => new FilterAllowAll,
                default => new FilterClass($type->getName())
            }
        };
    }
}
