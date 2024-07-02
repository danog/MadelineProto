<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use ReflectionClass;
use ReflectionProperty;

abstract class SettingsAbstract
{
    /**
     * Whether this setting was changed.
     *
     */
    protected bool $changed = true;

    public function __sleep()
    {
        $result = [];
        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PROTECTED) as $property) {
            $result []= $property->getName();
        }
        return $result;
    }
    /**
     * Merge with other settings instance.
     *
     * @internal
     */
    public function merge(self $other): void
    {
        $class = new ReflectionClass($other);
        $defaults = $class->getDefaultProperties();
        foreach ($class->getProperties(ReflectionProperty::IS_PROTECTED) as $property) {
            $name = $property->getName();
            if ($name === 'changed') {
                continue;
            }
            $uc = ucfirst($name);
            if ((isset($other->{$name}) || $name === 'metricsBindTo')
                && (
                    !\array_key_exists($name, $defaults)
                    || (
                        $other->{$name} !== $defaults[$name]  // Isn't equal to the default value
                        || $other->{$name} !== $this->{$name} // Is equal, but current value is not the default one
                    )
                )
                && (
                    !isset($this->{$name})
                    || $other->{$name} !== $this->{$name}
                )
            ) {
                if ($uc === 'Proxy') {
                    $uc = 'Proxies';
                }
                $this->{"set$uc"}($other->{$name});
                $this->changed = true;
            }
        }
    }
    /**
     * Convert array of legacy array property names to new camel case names.
     *
     * @param array $properties Properties
     */
    protected static function toCamel(array $properties): array
    {
        $result = [];
        foreach ($properties as $prop) {
            $result['set'.ucfirst(Tools::toCamelCase($prop))] = $prop;
        }
        return $result;
    }

    /**
     * Get whether this setting was changed, also applies changes.
     *
     * @internal
     */
    public function hasChanged(): bool
    {
        return $this->changed;
    }
    /**
     * Apply changes.
     *
     * @internal
     * @return static
     */
    public function applyChanges(): self
    {
        $this->changed = false;
        return $this;
    }
}
