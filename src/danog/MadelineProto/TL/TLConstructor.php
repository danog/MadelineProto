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

class TLConstructor extends \Volatile
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    use TLParams;

    public $id = [];
    public $predicate = [];
    public $type = [];
    public $params = [];
    public $layers = [];

    public function __sleep()
    {
        return ['id', 'predicate', 'type', 'params', 'layers'];
    }

    public function add($json_dict, $scheme_type)
    {
        $json_dict['id'] = 'a'.$json_dict['id'];
        $json_dict['predicate'] = (string) ((($scheme_type === 'mtproto' && $json_dict['predicate'] === 'message') ? 'MT' : '').$json_dict['predicate']);
        $json_dict['predicate'] .= $scheme_type === 'secret' ? $json_dict['layer'] : '';
        $this->id[$json_dict['predicate']] = $json_dict['id'];
        $this->predicate[$json_dict['id']] = $json_dict['predicate'];
        $this->type[$json_dict['id']] = (($scheme_type === 'mtproto' && $json_dict['type'] === 'Message') ? 'MT' : '').$json_dict['type'];
        $this->params[$json_dict['id']] = $json_dict['params'];
        $this->parse_params($json_dict['id'], $scheme_type === 'mtproto');
        if ($scheme_type === 'secret') {
            $this->layers[$json_dict['layer']] = $json_dict['layer'];
        }
    }

    public function find_by_type($type)
    {
        $id = array_search($type, (array) $this->type, true);
        return ($id === false) ? false : [
            'id'        => substr($id, 1),
            'predicate' => $this->predicate[$id],
            'type'      => $this->type[$id],
            'params'    => $this->array_cast_recursive($this->params[$id]),
        ];
    }

    public function find_by_predicate($predicate, $layer = '')
    {
        if ($layer !== '' && !isset($this->id[$predicate.$layer])) {
            foreach ($this->layers as $newlayer) {
                if (isset($this->id[$predicate.$newlayer]) || $newlayer > $layer) break;
                $nlayer = $newlayer;
            }
            $layer = $nlayer;
        }
        if (!isset($this->id[$predicate.$layer])) return false;
        $id = $this->id[$predicate.$layer];
        return [
            'id'        => substr($id, 1),
            'predicate' => $this->predicate[$id],
            'type'      => $this->type[$id],
            'params'    => $this->array_cast_recursive($this->params[$id]),
        ];
    }

    public function find_by_id($oid)
    {
        $id = 'a'.$oid;
        if (!isset($this->predicate[$id])) return false;
        return [
            'id'        => $oid,
            'predicate' => $this->predicate[$id],
            'type'      => $this->type[$id],
            'params'    => $this->array_cast_recursive($this->params[$id]),
        ];
    }
}
