<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class EventHandler extends APIFactory
{
    public function __construct($MadelineProto) {
        $this->API = $MadelineProto;
        foreach ($this->API->get_method_namespaces() as $namespace) {
            $this->{$namespace} = new APIFactory($namespace, $this->API);
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
            if (Logger::is_fork() && !Logger::$processed_fork) {
                \danog\MadelineProto\Logger::log('Detected fork');
                $this->API->reset_session();
                foreach ($this->API->datacenter->sockets as $datacenter) {
                    $datacenter->close_and_reopen();
                }
                Logger::$processed_fork = true;
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
