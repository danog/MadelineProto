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

class TLConstructor
{
    public $id = [];
    public $predicate = [];
    public $type = [];
    public $params = [];
    public $key = 0;

    public function add($json_dict, $mtproto)
    {
        $this->id[$this->key] = (int) $json_dict['id'];
        $this->predicate[$this->key] = (($mtproto && $json_dict['predicate'] == "message") ? "MT" : "").$json_dict['predicate'];
        $this->type[$this->key] = $json_dict['type'];
        $this->params[$this->key] = $json_dict['params'];
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
                $param['subtype'] = (($mtproto && $param['subtype'] == "message") ? "MT" : "").$param['subtype'];
            }
        }
        $this->key++;
    }

    public function find_by_predicate($predicate)
    {
        $key = array_search($predicate, $this->predicate);
        return ($key === false) ? false : [
            'id'        => $this->id[$key],
            'predicate' => $this->predicate[$key],
            'type'      => $this->type[$key],
            'params'    => $this->params[$key],
        ];
    }

    public function find_by_id($id)
    {
        $key = array_search($id, $this->id);

        return ($key === false) ? false : [
            'id'        => $this->id[$key],
            'predicate' => $this->predicate[$key],
            'type'      => $this->type[$key],
            'params'    => $this->params[$key],
        ];
    }
}
