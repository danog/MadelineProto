<?php

namespace danog\MadelineProto;

use ReflectionClass;
use ReflectionProperty;

abstract class SettingsAbstract
{
    /**
     * Merge legacy settings array.
     *
     * @param array $settings Settings array
     *
     * @return void
     */
    abstract public function mergeArray(array $settings): void;

    /**
     * Merge with other settings instance.
     *
     * @param self $other
     *
     * @return void
     */
    public function merge(self $other): void
    {
        $class = new ReflectionClass($other);
        $defaults = $class->getDefaultProperties();
        foreach ($class->getProperties(ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            if (isset($other->{$name})
                && (
                    !isset($defaults[$name])
                    || $other->{$name} !== $defaults[$name] // Isn't equal to the default value
                )
            ) {
                $this->{'set'.\ucfirst($name)}($other->{$name});
            }
        }
    }
    /**
     * Convert array of legacy array property names to new camel case names.
     *
     * @param array $properties Properties
     *
     * @return array
     */
    protected static function toCamel(array $properties): array
    {
        $result = [];
        foreach ($properties as $prop) {
            $result['set'.\ucfirst(Tools::toCamelCase($prop))] = $prop;
        }
        return $result;
    }
}
