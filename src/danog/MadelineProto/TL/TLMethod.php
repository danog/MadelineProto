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

class TLMethod extends \Volatile
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    use TLParams;
    public $id = [];
    public $method = [];
    public $type = [];
    public $params = [];
    public $method_namespace = [];
    public $key = 0;

    public function __sleep()
    {
        return ['id', 'method', 'type', 'params', 'method_namespace', 'key'];
    }

    public function add($json_dict)
    {
        $this->id[$this->key] = $json_dict['id'];
        $this->method[$this->key] = $json_dict['method'];
        $this->type[$this->key] = $json_dict['type'];
        $this->params[$this->key] = $json_dict['params'];
        $namespace = explode('.', $json_dict['method']);
        if (isset($namespace[1])) {
            $this->method_namespace[$namespace[1]] = $namespace[0];
        }

        $this->parse_params($this->key);
        $this->key++;
    }

    public function find_by_method($method)
    {
        $key = array_search($method, (array) $this->method, true);

        return ($key === false) ? false : [
            'id'                => $this->id[$key],
            'method'            => $this->method[$key],
            'type'              => $this->type[$key],
            'params'            => $this->array_cast_recursive($this->params[$key]),
        ];
    }

    public function find_by_id($id)
    {
        $key = array_search($id, (array) $this->id, true);

        return ($key === false) ? false : [
            'id'                => $this->id[$key],
            'method'            => $this->method[$key],
            'type'              => $this->type[$key],
            'params'            => $this->array_cast_recursive($this->params[$key]),
        ];
    }
}
