<?php declare(strict_types=1);

namespace danog\MadelineProto;

use danog\AsyncOrm\Annotations\OrmMappedArray;
use danog\AsyncOrm\DbAutoProperties;
use danog\AsyncOrm\Driver\MemoryArray as DriverMemoryArray;
use danog\MadelineProto\Db\CachedArray;
use danog\MadelineProto\Db\MemoryArray;
use ReflectionClass;

/** @internal */
trait LegacyMigrator
{
    use DbAutoProperties;

    /** @return list<\ReflectionProperty> */
    private function getDbAutoProperties(): array
    {
        $res = [];
        $closure = function (string $propName): void {
            if (isset($this->{$propName})) {
                if ($this->{$propName} instanceof CachedArray) {
                    unset($this->{$propName});
                } elseif ($this->{$propName} instanceof MemoryArray) {
                    $this->{$propName} = new DriverMemoryArray($this->{$propName}->getArrayCopy());
                }
            }
        };
        foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
            $attr = $property->getAttributes(OrmMappedArray::class);
            if (!$attr) {
                continue;
            }
            $closure->bindTo($this, $property->getDeclaringClass()->getName())($property->getName());
            $res []= $property;
        }
        return $res;
    }
}
