<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class APIFactory extends \Volatile
{
    use Tools;

    public $namespace;
    public $API;

    public function __construct($namespace, $API)
    {
        $this->namespace = $namespace.'.';
        $this->API = $API;
    }

    public function __call($name, $arguments)
    {
        $this->API->get_config([], ['datacenter' => $this->API->datacenter->curdc]);

        return method_exists($this->API, $this->namespace.$name) ? $this->API->{$this->namespace.$name}(...$arguments) : $this->API->method_call($this->namespace.$name, (isset($arguments[0]) && $this->is_array($arguments[0])) ? $arguments[0] : [], (isset($arguments[1]) && $this->is_array($arguments[1])) ? $arguments[1] : ['datacenter' => $this->API->datacenter->curdc]);
    }
}
