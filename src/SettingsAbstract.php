<?php

declare(strict_types=1);

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
    /**
     * Merge legacy settings array.
     *
     * @param array $settings Settings array
     * @internal
     */
    public function mergeArray(array $settings): void
    {
    }

    public function __sleep()
    {
        $result = [];
        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PUBLIC) as $property) {
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
        foreach ($class->getProperties(ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            if ($name === 'changed') {
                continue;
            }
            $uc = \ucfirst($name);
            if (isset($other->{$name})
                && (
                    !isset($defaults[$name])
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
            $result['set'.\ucfirst(Tools::toCamelCase($prop))] = $prop;
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
