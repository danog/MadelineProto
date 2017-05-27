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

    public function __sleep()
    {
        return ['id', 'method', 'type', 'params', 'method_namespace'];
    }

    public function add($json_dict)
    {
        $json_dict['id'] = 'a'.$json_dict['id'];
        $this->id[$json_dict['method']] = $json_dict['id'];
        $this->method[$json_dict['id']] = $json_dict['method'];
        $this->type[$json_dict['id']] = $json_dict['type'];
        $this->params[$json_dict['id']] = $json_dict['params'];
        $namespace = explode('.', $json_dict['method']);
        if (isset($namespace[1])) {
            $this->method_namespace[$namespace[1]] = $namespace[0];
        }

        $this->parse_params($json_dict['id']);
    }

    public function find_by_method($method)
    {
        if (!isset($this->id[$method])) {
            return false;
        }
        $id = $this->id[$method];

        return [
            'id'                => substr($id, 1),
            'method'            => $this->method[$id],
            'type'              => $this->type[$id],
            'params'            => $this->array_cast_recursive($this->params[$id]),
        ];
    }

    public function find_by_id($oid)
    {
        $id = 'a'.$oid;
        if (!isset($this->method[$id])) {
            return false;
        }

        return [
            'id'                => $oid,
            'method'            => $this->method[$id],
            'type'              => $this->type[$id],
            'params'            => $this->array_cast_recursive($this->params[$id]),
        ];
    }
}
