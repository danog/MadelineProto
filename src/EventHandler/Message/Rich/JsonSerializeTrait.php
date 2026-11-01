<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

use ReflectionClass;
use ReflectionProperty;

/**
 * Serializes the public properties of a rich message object.
 *
 * @internal
 */
trait JsonSerializeTrait
{
    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
