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

class TLConstructor extends TLParams
{
    public $id = [];
    public $predicate = [];
    public $type = [];
    public $params = [];
    public $key = 0;

    public function add($json_dict, $mtproto)
    {
        $this->id[$this->key] = (int) $json_dict['id'];
        $this->predicate[$this->key] = (string) ((($mtproto && $json_dict['predicate'] === 'message') ? 'MT' : '').$json_dict['predicate']);
        $this->type[$this->key] = (($mtproto && $json_dict['type'] === 'Message') ? 'MT' : '').$json_dict['type'];
        $this->params[$this->key] = $json_dict['params'];
        $this->parse_params($this->key, $mtproto);
        $this->key++;
    }

    public function find_by_type($type)
    {
        $key = array_search($type, $this->type);

        return ($key === false) ? false : [
            'id'        => $this->id[$key],
            'predicate' => $this->predicate[$key],
            'type'      => $this->type[$key],
            'params'    => $this->params[$key],
        ];
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
