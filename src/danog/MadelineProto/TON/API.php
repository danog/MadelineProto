<?php

/**
 * TON API module.
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

namespace danog\MadelineProto\TON;

use danog\MadelineProto\Magic;

/**
 * TON API.
 */
class API extends InternalDoc
{
    /**
     * Construct API.
     *
     * @param array $settings Settings
     */
    public function __construct(array $settings)
    {
        Magic::classExists();

        $this->API = new Lite($settings);
        foreach (\get_class_methods($this->API) as $method) {
            $this->methods[$method] = [$this->API, \strtolower($method)];
        }
        foreach ($this->API->getMethodNamespaces() as $namespace) {
            $this->{$namespace} = new APIFactory($namespace, $this->API, $this->async);
        }
    }
}
