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

namespace danog\MadelineProto\Namespace;

use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Exception;
use InvalidArgumentException;

/**
 * A namespace of the API ($api->messages, ...), created by InternalDoc::__get() at every access.
 *
 * It holds the instance it was fetched from, so that instance stays alive while the namespace is in use, like it
 * does while one of its own methods runs: in `$service->api()->channels->createChannel()`, the temporary API would
 * otherwise be destroyed as soon as the namespace is fetched, and its destructor would disconnect it mid-call.
 * The instance must not cache its namespaces in properties: the reference cycle would delay its destructor until the
 * cycle collector runs.
 *
 * @internal
 */
final class AbstractAPI
{
    /**
     * @param object $owner The API or event handler the namespace was fetched from, only held to keep it alive.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $namespace,
        private readonly APIWrapper $wrapper,
        /** @psalm-suppress UnusedProperty */
        private readonly object $owner,
    ) {
    }
    /**
     * Call async wrapper function.
     *
     * @param string $name      Method name
     * @param array  $arguments Arguments
     */
    public function __call(string $name, array $arguments)
    {
        if (!isset($arguments[0])) {
            // Named arguments
            $args = $arguments;
        } else {
            // Legacy arguments
            $args = $arguments[0] ?? [];
            $aargs = $arguments[1] ?? [];
            if (!array_is_list($arguments)
                || \count($arguments) > 2
                || !\is_array($args)
                || !\is_array($aargs)
                || isset($args['_'])
                || (isset($args[0]) && !isset($args['multiple']))
            ) {
                throw new InvalidArgumentException('Parameter names must be provided!');
            }
            $args = $args + $aargs;
        }

        $name = $this->namespace.'.'.$name;
        if (isset(Blacklist::BLACKLIST[$name])) {
            throw new Exception(Blacklist::BLACKLIST[$name]);
        }

        return $this->wrapper->getAPI()->methodCallAsyncRead($name, $args);
    }
}
