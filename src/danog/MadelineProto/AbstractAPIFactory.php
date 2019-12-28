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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
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
    public $namespace = '';
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
    public $lua = false;
    /**
     * Whether async is enabled.
     *
     * @internal
     *
     * @var boolean
     */
    public $async = false;
    /**
     * Async init promise.
     *
     * @var Promise
     */
    public $asyncAPIPromise;

    /**
     * Method list.
     *
     * @var string[]
     */
    protected $methods = [];

    public function __construct($namespace, &$API, &$async)
    {
        $this->namespace = $namespace.'.';
        $this->API = &$API;
        $this->async = &$async;
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
        $async = $this->lua === false && (\is_array(\end($arguments)) && isset(\end($arguments)['async']) ? \end($arguments)['async'] : ($this->async && $name !== 'loop'));

        if ($async) {
            return $yielded;
        }
        if (!$this->lua) {
            return Tools::wait($yielded);
        }

        try {
            $yielded = Tools::wait($yielded);
            Lua::convertObjects($yielded);

            return $yielded;
        } catch (\Throwable $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        }
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
        if ($this->asyncInitPromise) {
            yield $this->initAsynchronously();
            $this->API->logger->logger('Finished init asynchronously');
        }
        if (Magic::isFork() && !Magic::$processed_fork) {
            throw new Exception('Forking not supported, use async logic, instead: https://docs.madelineproto.xyz/docs/ASYNC.html');
        }
        if (!$this->API) {
            throw new Exception('API did not init!');
        }
        if ($this->API->asyncInitPromise) {
            yield $this->API->initAsynchronously();
            $this->API->logger->logger('Finished init asynchronously');
        }
        if (isset($this->session) && !\is_null($this->session) && \time() - $this->serialized > $this->API->settings['serialization']['serialization_interval']) {
            Logger::log("Didn't serialize in a while, doing that now...");
            $this->serialize($this->session);
        }
        if ($this->API->flushSettings) {
            $this->API->flushSettings = false;
            $this->API->__construct($this->API->settings);
            yield $this->API->initAsynchronously();
        }
        if ($this->API->asyncInitPromise) {
            yield $this->API->initAsynchronously();
            $this->API->logger->logger('Finished init asynchronously');
        }

        $lower_name = \strtolower($name);
        if ($this->namespace !== '' || !isset($this->methods[$lower_name])) {
            $name = $this->namespace.$name;
            $aargs = isset($arguments[1]) && \is_array($arguments[1]) ? $arguments[1] : [];
            $aargs['apifactory'] = true;
            $aargs['datacenter'] = $this->API->datacenter->curdc;
            $args = isset($arguments[0]) && \is_array($arguments[0]) ? $arguments[0] : [];

            return yield $this->API->methodCallAsyncRead($name, $args, $aargs);
        }
        return yield $this->methods[$lower_name](...$arguments);
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
        if ($this->asyncAPIPromise) {
            Tools::wait($this->asyncAPIPromise);
        }
        if ($name === 'settings') {
            $this->API->flushSettings = true;

            return $this->API->settings;
        }
        if ($name === 'logger') {
            return $this->API->logger;
        }

        return $this->API->storage[$name];
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
        if ($this->asyncAPIPromise) {
            Tools::wait($this->asyncAPIPromise);
        }
        if ($name === 'settings') {
            if ($this->API->asyncInitPromise) {
                $this->API->init();
            }

            return $this->API->__construct(\array_replace_recursive($this->API->settings, $value));
        }

        return $this->API->storage[$name] = $value;
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
        if ($this->asyncAPIPromise) {
            Tools::wait($this->asyncAPIPromise);
        }

        return isset($this->API->storage[$name]);
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
        if ($this->asyncAPIPromise) {
            Tools::wait($this->asyncAPIPromise);
        }
        unset($this->API->storage[$name]);
    }
}
