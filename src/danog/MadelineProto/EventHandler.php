<?php

/**
 * EventHandler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

class EventHandler extends APIFactory
{
    public function __construct($MadelineProto)
    {
        $this->API = $MadelineProto->API;
        $this->async = $MadelineProto->async;
        $this->methods = $MadelineProto->methods;
        foreach ($this->API->get_method_namespaces() as $namespace) {
            $this->{$namespace} = new APIFactory($namespace, $this->API);
            $this->{$namespace}->async = $MadelineProto->async;
        }
    }

    public function &__get($name)
    {
        if ($name === 'settings') {
            $this->API->setdem = true;

            return $this->API->settings;
        }

        return $this->API->storage[$name];
    }

    public function __set($name, $value)
    {
        if ($name === 'settings') {
            if (Magic::is_fork() && !Magic::$processed_fork) {
                \danog\MadelineProto\Logger::log('Detected fork');
                $this->API->reset_session();
                foreach ($this->API->datacenter->sockets as $id => $datacenter) {
                    $this->API->close_and_reopen($id);
                }
                Magic::$processed_fork = true;
            }

            return $this->API->__construct(array_replace_recursive($this->API->settings, $value));
        }

        return $this->API->storage[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->API->storage[$name]);
    }

    public function __unset($name)
    {
        unset($this->API->storage[$name]);
    }
}
