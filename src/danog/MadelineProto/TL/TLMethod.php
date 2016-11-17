<?php
/*
Copyright 2016 Daniil Gentili
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
    public $id = [];
    public $method = [];
    public $type = [];
    public $params = [];
    public $method_namespaced = [];
    public $key = 0;

    public function add($json_dict)
    {
        $this->id[$this->key] = (int) $json_dict['id'];
        $this->method[$this->key] = $json_dict['method'];
        $this->type[$this->key] = $json_dict['type'];
        $this->params[$this->key] = $json_dict['params'];
        $this->method_namespaced[$this->key] = explode('.', $json_dict['method']);

        foreach ($this->params[$this->key] as &$param) {
            $param['opt'] = false;
            $param['subtype'] = null;
            if (preg_match('/^flags\.\d\?/', $param['type'])) {
                $param['opt'] = true;
                $param['flag'] = preg_replace(['/^flags\./', '/\?.*/'], '', $param['type']);
                $param['type'] = preg_replace('/^flags\.\d\?/', '', $param['type']);
            }
            if (preg_match('/vector<.*>/i', $param['type'])) {
                if (preg_match('/vector/', $param['type'])) {
                    $param['subtype'] = preg_replace(['/.*</', '/>$/'], '', $param['type']);
                    $param['type'] = 'vector';
                }
                if (preg_match('/Vector/', $param['type'])) {
                    $param['subtype'] = preg_replace(['/.*</', '/>$/'], '', $param['type']);
                    $param['type'] = 'Vector t';
                }
                if (preg_match('/^\%/', $param['subtype'])) {
                    $param['subtype'] = lcfirst(preg_replace('/^\%/', '', $param['subtype']));
                }
            }
        }
        $this->key++;
    }

    public function find_by_method($method)
    {
        $key = array_search($method, $this->method);

        return ($key == false) ? false : [
            'id'                => $this->id[$key],
            'method'            => $this->method[$key],
            'type'              => $this->type[$key],
            'params'            => $this->params[$key],
            'method_namespaced' => $this->method_namespaced[$key],
        ];
    }
}
