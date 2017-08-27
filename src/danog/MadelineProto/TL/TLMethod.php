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

namespace danog\MadelineProto\TL;

class TLMethod
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    use TLParams;
    public $by_id = [];
    public $by_method = [];
    public $method_namespace = [];

    public function __sleep()
    {
        return ['by_id', 'by_method', 'method_namespace'];
    }

    public function add($json_dict)
    {
        $this->by_id[$json_dict['id']] = ['method' => $json_dict['method'], 'type' => $json_dict['type'], 'params' => $json_dict['params']];
        $this->by_method[$json_dict['method']] = $json_dict['id'];

        $namespace = explode('.', $json_dict['method']);
        if (isset($namespace[1])) {
            $this->method_namespace[] = [$namespace[0] => $namespace[1]];
        }

        $this->parse_params($json_dict['id']);
    }

    public function find_by_id($id)
    {
        if (isset($this->by_id[$id])) {
            $method = $this->by_id[$id];
            $method['id'] = $id;
            $method['params'] = $method['params'];

            return $method;
        }

        return false;
    }

    public function find_by_method($method_name)
    {
        if (isset($this->by_method[$method_name])) {
            $method = $this->by_id[$this->by_method[$method_name]];
            $method['id'] = $this->by_method[$method_name];
            $method['params'] = $method['params'];

            return $method;
        }

        return false;
    }
}
