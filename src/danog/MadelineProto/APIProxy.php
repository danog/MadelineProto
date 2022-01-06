<?php

/**
 * APIProxy module.
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

/**
 * This class proxies requests to the APIWrapper.
 */
abstract class APIProxy
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
     * API wrapper.
     *
     * @internal
     */
    protected APIWrapper $wrapper;

    abstract protected function cloneProxy(): self;

    /**
     * Get API namespaces.
     *
     * @return array<string>
     */
    abstract protected function getAPINamespaces(): array;
    /**
     * Initialize API namespaces.
     */
    final protected function initProxyNamespaces(APIWrapper $wrapper): void
    {
        $this->wrapper = $wrapper;
        foreach ($this->getAPINamespaces() as $namespace) {
            $this->{$namespace} = $this->exportNamespace($namespace);
        }
    }
    /**
     * Export APIFactory instance with the specified namespace.
     *
     * @param string $namespace Namespace
     */
    private function exportNamespace(string $namespace): self
    {
        $instance = $this->cloneProxy();
        $instance->namespace = $namespace ? $namespace.'.' : '';
        $instance->wrapper = $this->wrapper;

        return $instance;
    }
    /**
     * Enable or disable async.
     *
     * @param bool $async Whether to enable or disable async
     *
     * @return void
     */
    final public function async(bool $async): void
    {
        $this->wrapper->async($async);
    }
    /**
     * Proxy calls to APIWrapper.
     *
     * @return void
     */
    final public function __call(string $name, array $arguments)
    {
        return $this->wrapper->call($name, $this->namespace, $arguments);
    }
}
