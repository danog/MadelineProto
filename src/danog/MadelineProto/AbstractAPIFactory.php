<?php

declare(strict_types=1);

/**
 * APIFactory module.
 *
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

use danog\MadelineProto\Ipc\Client;
use InvalidArgumentException;

abstract class AbstractAPIFactory
{
    /**
     * Namespace.
     *
     * @internal
     */
    private string $namespace = '';
    /**
     * Method list.
     *
     * @var array<string, callable>
     */
    protected array $methods = [];

    /**
     * Main API instance.
     */
    private API $mainAPI;

    /**
     * Export APIFactory instance with the specified namespace.
     *
     * @param string $namespace Namespace
     */
    protected function exportNamespace(string $namespace = ''): self
    {
        $class = \array_reverse(\array_values(\class_parents(static::class)))[$namespace ? 1 : 2];

        $instance = new $class;
        $instance->namespace = $namespace ? $namespace.'.' : '';
        self::link($instance, $this);

        return $instance;
    }
    /**
     * Link two APIFactory instances.
     *
     * @param self $a First instance
     * @param self $b Second instance
     */
    protected static function link(self $a, self $b): void
    {
        $a->methods =& $b->methods;
        if ($b instanceof API) {
            $a->mainAPI = $b;
            $b->mainAPI = $b;
        } elseif ($a instanceof API) {
            $a->mainAPI = $a;
            $b->mainAPI = $a;
        } else {
            $a->mainAPI =& $b->mainAPI;
        }
    }
    /**
     * Enable or disable async.
     *
     * @deprecated Starting from MadelineProto v8, async is always enabled. This function does nothing.
     */
    public function async(bool $async): void
    {
    }
    /**
     * Call async wrapper function.
     *
     * @param string $name      Method name
     * @param array  $arguments Arguments
     * @internal
     */
    public function __call(string $name, array $arguments)
    {
        if ($arguments && !isset($arguments[0])) {
            $arguments = [$arguments];
        }

        $lower_name = \strtolower($name);
        if ($this->namespace !== '' || !isset($this->methods[$lower_name])) {
            $name = $this->namespace.$name;
            $aargs = isset($arguments[1]) && \is_array($arguments[1]) ? $arguments[1] : [];
            $aargs['apifactory'] = true;
            $args = isset($arguments[0]) && \is_array($arguments[0]) ? $arguments[0] : [];
            if (isset($args[0]) && !isset($args['multiple'])) {
                throw new InvalidArgumentException('Parameter names must be provided!');
            }
            return $this->mainAPI->API->methodCallAsyncRead($name, $args, $aargs);
        }
        if ($lower_name === 'seteventhandler') {
            throw new InvalidArgumentException('Cannot call setEventHandler like this, please use MyEventHandler::startAndLoop("session.madeline", $settings);');
        }
        return $this->methods[$lower_name](...$arguments);
    }
    /**
     * Info to dump.
     */
    public function __debugInfo(): array
    {
        $keys = APIWrapper::properties();
        $res = [];
        foreach ($keys as $key) {
            if (Tools::hasVar($this, $key)) {
                $res[$key] = Tools::getVar($this, $key);
            }
        }
        return $res;
    }
    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return [];
    }
    /**
     * Get fully resolved method list for object, including snake_case and camelCase variants.
     *
     * @param API|MTProto|Client $value Value
     * @param string             $class Custom class name
     */
    protected static function getInternalMethodList(API|MTProto|Client $value, ?string $class = null): array
    {
        return \array_map(fn ($method) => [$value, $method], self::getInternalMethodListClass($class ?? $value::class));
    }
    /**
     * Get fully resolved method list for object, including snake_case and camelCase variants.
     *
     * @param string $class Class name
     */
    protected static function getInternalMethodListClass(string $class): array
    {
        static $cache = [];
        if (isset($cache[$class])) {
            return $cache[$class];
        }
        $methods = \get_class_methods($class);
        foreach ($methods as $method) {
            if ($method == 'methodCallAsyncRead') {
                unset($methods[\array_search('methodCall', $methods)]);
            } elseif (\stripos($method, 'async') !== false) {
                if (\strpos($method, '_async') !== false) {
                    unset($methods[\array_search(\str_ireplace('_async', '', $method), $methods)]);
                } else {
                    unset($methods[\array_search(\str_ireplace('async', '', $method), $methods)]);
                }
            }
        }
        $finalMethods = [];
        foreach ($methods as $method) {
            $actual_method = $method;
            if ($method == 'methodCallAsyncRead') {
                $method = 'methodCall';
            } elseif (\stripos($method, 'async') !== false) {
                if (\strpos($method, '_async') !== false) {
                    $method = \str_ireplace('_async', '', $method);
                } else {
                    $method = \str_ireplace('async', '', $method);
                }
            }
            $finalMethods[\strtolower($method)] = $actual_method;
            if (\strpos($method, '_') !== false) {
                $finalMethods[\strtolower(\str_replace('_', '', $method))] = $actual_method;
            } else {
                $finalMethods[\strtolower(StrTools::toSnakeCase($method))] = $actual_method;
            }
        }

        $cache[$class] = $finalMethods;
        return $finalMethods;
    }
}
