<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use ReflectionProperty;

/**
 * Restores the state of an unserialized object, skipping the properties its class no longer declares.
 *
 * Sessions outlive the releases that wrote them: a property removed or renamed since then is still in
 * the serialized state. PHP's default unserialization, and an __unserialize() that assigns every key,
 * recreate it as a dynamic property (deprecated since PHP 8.2), and the next __serialize() built from
 * get_object_vars() writes it back, so it is carried forever.
 *
 * @internal
 */
trait RestoresDeclaredProperties
{
    /** @var array<string, ?ReflectionProperty> By the unserialized class, the declaring class and the name of the property. */
    private static array $restorableProperties = [];

    /**
     * Assign the serialized properties that are still declared.
     *
     * Accepts the keys of __serialize() (plain names) and the mangled keys of the default format
     * (`"\0Class\0name"` for private properties, `"\0*\0name"` for protected ones), which is what
     * __unserialize() receives for an object serialized through __sleep().
     *
     * @param array<array-key, mixed> $data
     * @return array<string, mixed> The values of the properties that are no longer declared, by name, for the caller to migrate.
     */
    private function restoreDeclaredProperties(array $data): array
    {
        $dropped = [];
        /** @var mixed $value */
        foreach ($data as $key => $value) {
            $key = (string) $key;
            $class = static::class;
            $name = $key;
            if ($key !== '' && $key[0] === "\0") {
                $end = strpos($key, "\0", 1);
                if ($end === false) {
                    $dropped[$key] = $value;
                    continue;
                }
                $scope = substr($key, 1, $end - 1);
                $name = substr($key, $end + 1);
                if ($scope !== '*') {
                    $class = $scope;
                }
            }
            $cacheKey = static::class."\0$class\0$name";
            if (!\array_key_exists($cacheKey, self::$restorableProperties)) {
                self::$restorableProperties[$cacheKey] = self::findRestorableProperty($class, $name);
            }
            $property = self::$restorableProperties[$cacheKey];
            if ($property === null) {
                $dropped[$name] = $value;
                continue;
            }
            $property->setValue($this, $value);
        }
        return $dropped;
    }

    private static function findRestorableProperty(string $class, string $name): ?ReflectionProperty
    {
        // A private property of a class that is no longer an ancestor (renamed or removed) is gone too.
        if ($class !== static::class && !is_subclass_of(static::class, $class)) {
            return null;
        }
        if (!property_exists($class, $name)) {
            return null;
        }
        $property = new ReflectionProperty($class, $name);
        return $property->isStatic() ? null : $property;
    }
}
