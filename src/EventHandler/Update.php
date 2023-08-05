<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\Ipc\IpcCapable;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Represents a generic update.
 */
abstract class Update extends IpcCapable implements JsonSerializable
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
