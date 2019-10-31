<?php

/**
 * CombinedEventHandler module.
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

abstract class CombinedEventHandler
{
    private $CombinedAPI;

    public function __construct($CombinedAPI)
    {
        $this->CombinedAPI = $CombinedAPI;
        foreach ($CombinedAPI->instances as $path => $instance) {
            $this->referenceInstance($path);
        }
    }

    final public function __sleep()
    {
        $keys = \method_exists($this, '__magic_sleep') ? $this->__magic_sleep() : \get_object_vars($this);
        unset($keys['CombinedAPI']);
        if (isset($this->CombinedAPI) && $this->CombinedAPI instanceof CombinedAPI) {
            foreach ($this->CombinedAPI->instance_paths as $path) {
                unset($keys[$path]);
            }
        } else {
            foreach ($keys as $key => $value) {
                if ($value instanceof API && $key === $value->session) {
                    unset($keys[$key]);
                }
            }
        }

        return \array_keys($keys);
    }

    final public function referenceInstance($path)
    {
        $this->{$path} = $this->CombinedAPI->instances[$path];
    }

    final public function removeInstance($path)
    {
        if (isset($this->{$path})) {
            unset($this->{$path});
        }
    }
}
