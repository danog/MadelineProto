<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\Async\AsyncConstruct;

abstract class AbstractAPIFactory extends AsyncConstruct
{
    /**
     * Namespace.
     *
     * @internal
     *
     * @var string
     */
    private string $namespace = '';
    /**
     * MTProto instance.
     *
     * @internal
     *
     * @var MTProto
     */
    public $API;
    /**
     * Whether lua is being used.
     *
     * @internal
     *
     * @var boolean
     */
    public bool $lua = false;
    /**
     * Whether async is enabled.
     *
     * @internal
     *
     * @var boolean
     */
    protected bool $async = false;
    /**
     * Method list.
     *
     * @var string[]
     */
    protected array $methods = [];
    /**
     * Export APIFactory instance with the specified namespace.
     *
     * @param string $namespace Namespace
     *
     * @return self
     */
    protected function exportNamespace(string $namespace = ''): self
    {
        $class = \array_reverse(\array_values(\class_parents(static::class)))[$namespace ? 2 : 3];

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
     *
     * @return void
     */
    protected static function link(self $a, self $b): void
    {
        $a->API =& $b->API;
        $a->lua =& $b->lua;
        $a->async =& $b->async;
        $a->methods =& $b->methods;
    }
    /**
     * Enable or disable async.
     *
     * @param bool $async Whether to enable or disable async
     *
     * @return void
     */
    public function async(bool $async): void
    {
        $this->async = $async;
    }
    /**
     * Call async wrapper function.
     *
     * @param string $name      Method name
     * @param array  $arguments Arguments
     *
     * @internal
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $yielded = Tools::call($this->__call_async($name, $arguments));
        $async = !$this->lua && ((\is_array(\end($arguments)) ? \end($arguments) : [])['async'] ?? ($this->async && $name !== 'loop'));

        if ($async) {
            return $yielded;
        }

        $yielded = Tools::wait($yielded);

        if (!$this->lua) {
            return $yielded;
        }
        try {
            Lua::convertObjects($yielded);
            return $yielded;
        } catch (\Throwable $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        }
    }
    /**
     * Info to dump.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        $keys = APIWrapper::__sleep();
        $res = [];
        foreach ($keys as $key) {
            $res[$key] = Tools::getVar($this, $key);
        }
        return $res;
    }
    /**
     * Call async wrapper function.
     *
     * @param string $name      Method name
     * @param array  $arguments Arguments
     *
     * @internal
     *
     * @return \Generator
     */
    public function __call_async(string $name, array $arguments): \Generator
    {
        yield from $this->initAsynchronously();

        $lower_name = \strtolower($name);
        if ($this->namespace !== '' || !isset($this->methods[$lower_name])) {
            $name = $this->namespace.$name;
            $aargs = isset($arguments[1]) && \is_array($arguments[1]) ? $arguments[1] : [];
            $aargs['apifactory'] = true;
            $aargs['datacenter'] = $this->API->datacenter->curdc;
            $args = isset($arguments[0]) && \is_array($arguments[0]) ? $arguments[0] : [];
            return yield from $this->API->methodCallAsyncRead($name, $args, $aargs);
        }
        $res = $this->methods[$lower_name](...$arguments);
        return $res instanceof \Generator ? yield from $res : yield $res;
    }
    /**
     * Get fully resolved method list for object, including snake_case and camelCase variants.
     *
     * @param API $value Value
     *
     * @return array
     */
    protected static function getInternalMethodList($value): array
    {
        static $cache = [];
        $class = \get_class($value);
        if (isset($cache[$class])) {
            return \array_map(
                static function ($v) use ($value) {
                    return [$value, $v];
                },
                $cache[$class]
            );
        }

        $methods = \get_class_methods($value);
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
        return self::getInternalMethodList($value);
    }
    /**
     * Get attribute.
     *
     * @param string $name Attribute nam
     *
     * @internal
     *
     * @return mixed
     */
    public function &__get(string $name)
    {
        if ($name === 'logger') {
            if (isset($this->API)) {
                return $this->API->logger;
            }
            return Logger::$default;
        }
        return $this->storage[$name];
    }
    /**
     * Set an attribute.
     *
     * @param string $name  Name
     * @param mixed  $value Value
     *
     * @internal
     *
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        return $this->storage[$name] = $value;
    }
    /**
     * Whether an attribute exists.
     *
     * @param string $name Attribute name
     *
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return isset($this->storage[$name]);
    }
    /**
     * Unset attribute.
     *
     * @param string $name Attribute name
     *
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->storage[$name]);
    }
}
